<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use PharIo\Version\Exception;
use Pimcore\File;
use Pimcore\Model\Asset;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\ExternalImage;
use function Symfony\Component\String\b;

class ImportBmeBricks extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'import';

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'The CSV-File with import data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        //$this->removeAllAssets();
        $this->importNewAssets($path);

        $output->writeln("Success");
        return Command::SUCCESS;
    }

    /**
     * @param string $csvFile
     * @return array
     */
    private function readCSV(string $csvFile)
    {
        $lines = [];
        $handle = fopen($csvFile, 'r');
        $row = 1;
        $columnNames = [];
        while (!feof($handle)) {
            $line = fgetcsv($handle, 20000, ";");
            if ($row == 1) {
                $columnNames = $line;
            }
            if ($row > 1) {
                if ($line) {
                    if (count($line) == count($columnNames)) {
                        $lines[] = array_combine($columnNames, $line);
                    } else {
                        $lines[] = $line;
                    }
                }
            }
            $row++;
        }
        fclose($handle);
        return $lines;
    }

    private function removeAllAssets()
    {
        $assets = Asset::getList();
        /**
         * @var Asset $asset
         */
        foreach ($assets as $asset) {
            if ($asset->getId() != 1) {
                $asset->delete();
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function importNewAssets($path)
    {
        $csv = $this->readCSV($path);
        $xml = new SimpleXMLElement('BmeBosch.xml', 0, TRUE);
        $counter = 0;

        foreach ($xml->T_NEW_CATALOG->ARTICLE as $result) {
            if ($counter <= 10) {

                $xmlEan = $result->ARTICLE_DETAILS->EAN;

                $eanListObj = DataObject\Product::getList();
                $eanList = $eanListObj->load();
                foreach ($eanList as $list) {
                    if ($list->getEan() == $xmlEan) {
                        $id = $list->getId();
                        $path = $list->getPath();
                        $productName = $list->getKey();

                        $xmlName = $result->ARTICLE_DETAILS->DESCRIPTION_SHORT;
                        echo $xmlName . " - " . $xmlEan . "\n";

                        // description part
                        $xmlTechnicalData = "<p>" . $result->ARTICLE_DETAILS->DESCRIPTION_LONG . "</p>";

                        $lieferumfangData = null;
                        foreach ($result->ARTICLE_FEATURES as $productFeatures) {
                            foreach ($productFeatures as $productFeature) {
                                if ($productFeature->FNAME == "Lieferumfang") {
                                    $lieferumfangData .= "<p><strong>" . $productFeature->FNAME . "</strong></p>";
                                    $lieferumfangData .= "<ul>";
                                    foreach ($productFeature->FVALUE as $lieferumfangValue) {
                                        $lieferumfangData .= "<li>" . $lieferumfangValue . "</li>";
                                    }
                                    $lieferumfangData .= "</ul>";
                                }
                            }
                        }

                        $topProductData = null;
                        foreach ($result->ARTICLE_FEATURES as $productTops) {
                            foreach ($productTops as $productTop) {
                                if ($productTop->FNAME == "TOP-Produktmerkmale") {
                                    $topProductData .= "<p><strong>" . $productTop->FNAME . "</strong></p>";
                                    $topProductData .= "<ul>";
                                    foreach ($productTop->FVALUE as $productTopValue) {
                                        $topProductData .= "<li>" . $productTopValue . "</li>";
                                    }
                                    $topProductData .= "</ul>";
                                }
                            }
                        }

                        $technicalData = null;
                        if (str_contains($result->ARTICLE_FEATURES[1]->FEATURE->FDESCR, "Technical Data")) {
                            $technicalData .= "<p><strong>" . strtok($result->ARTICLE_FEATURES[1]->FEATURE->FDESCR, ",") . "</strong></p>";
                            $technicalData .= "<ul>";
                            foreach ($result->ARTICLE_FEATURES[1]->FEATURE as $technicalFeatures) {
                                $technicalData .= "<li>" . $technicalFeatures->FNAME . " " . $technicalFeatures->FVALUE . " " . $technicalFeatures->FUNIT . "</li>";
                            }
                            $technicalData .= "</ul>";
                        }

                        // end of description part

                        $bruttoWeight = null;
                        $nettoWeight = null;
                        $dimensionLength = null;
                        $dimensionWidth = null;
                        $dimensionHeight = null;
                        foreach ($result->ARTICLE_FEATURES as $productPackages) {
                            foreach ($productPackages as $productPackage) {
                                if ($productPackage->FNAME == "Bruttogewicht") {
                                    $bruttoWeight = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "Nettogewicht") {
                                    $nettoWeight = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "Abmessung Länge") {
                                    $dimensionLength = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "Abmessung Breite") {
                                    $dimensionWidth = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "Abmessung Höhe") {
                                    $dimensionHeight = $productPackage->FVALUE;
                                }
                            }
                        }

                        // BME technical data part
                        $blockElementsTechnical = [];
                        $blockElementsOther = [];

                        $technicalCounter = 0;
                        foreach ($result->ARTICLE_FEATURES as $articleFeatures) {
                            $articleFeaturesList = [];
                            foreach ($articleFeatures as $articleFeature) {
                                if ($technicalCounter == 1 && str_contains($articleFeature->FDESCR, "Technical Data")) {
                                    $blockElementsTechnical[] = [
                                        "name" => new BlockElement('name', 'input', strval($articleFeature->FNAME)),
                                        "val" => new BlockElement('val', 'input', strval($articleFeature->FVALUE)),
                                        "unit" => new BlockElement('unit', 'input', strval($articleFeature->FUNIT))
                                    ];
                                } /*else {
                                    $blockElementsOther[] = [
                                        "name" => new BlockElement('name', 'input', strval($articleFeature->FNAME)),
                                        "val" => new BlockElement('val', 'input', strval($articleFeature->FVALUE))
                                    ];
                                }*/
                            }
                            $technicalCounter++;
                        }
                        // end BME technical data part

                        // images part
                        $mainImage = null;
                        $mainImageObjectPath = null;
                        $galleryImage = [];
                        $galleryImageIcon = [];
                        $setGalleryImages = [];
                        $setGalleryImagesIcons = [];
                        $setGalleryImagesObjects = [];
                        $setGalleryIconObjects = [];
                        foreach ($result->MIME_INFO as $articleImages) {
                            foreach ($articleImages as $articleImage) {
                                if ($articleImage->MIME_DESCR == "Produktbild" && $articleImage->MIME_PURPOSE == "normal" && $articleImage->MIME_TYPE == "image/jpeg") {
                                    $mainImage = $articleImage->MIME_SOURCE;
                                    array_push($galleryImage, $mainImage);
                                }else if ($articleImage->MIME_DESCR == "Produktbild" && $articleImage->MIME_PURPOSE == "others" && $articleImage->MIME_TYPE == "image/jpeg") {
                                    $gallery = $articleImage->MIME_SOURCE;
                                    array_push($galleryImage, $gallery);
                                }else{
                                    $galleryIcon = $articleImage->MIME_SOURCE;
                                    array_push($galleryImageIcon, $galleryIcon);
                                }
                            }
                        }

                        $imageGalleryCounter = 0;
                        foreach ($galleryImage as $galleryImages) {
                            $assetsFolder = Asset\Service::createFolderByPath($path);
                            $fileData = file_get_contents("public/var/unsorted/mime/" . $galleryImages);
                            if ($imageGalleryCounter == 0) {
                                $assetKey = File::getValidFilename($productName . ".jpg");
                            }else {
                                $assetKey = File::getValidFilename($productName . "_" . strval($imageGalleryCounter) . ".jpg");
                            }

                            $assetPath = $assetsFolder->getFullPath()."/".$assetKey;
                            array_push($setGalleryImages, $assetPath);
                            $asset = Asset::getByPath($assetPath);
                            if(!$asset){
                                $asset = new Asset();
                                $asset->setParentId($assetsFolder->getId());
                                $asset->setKey($assetKey);

                            }
                            $asset->setData($fileData);
                            try {
                                $asset->save();
                            } catch (Exception $e) {
                                //Exception
                            }
                            $imageGalleryCounter++;
                        }
                        $imageGalleryCounter = 0;
                        // end images part

                        foreach ($galleryImageIcon as $galleryImageIcons){ // icon gallery
                            $assetsFolder = Asset\Service::createFolderByPath($path);
                            $fileData = file_get_contents("public/var/unsorted/mime/" . $galleryImageIcons);
                            $assetKey = File::getValidFilename($productName . ".jpg");

                            $assetPath = $assetsFolder->getFullPath()."/".$assetKey;
                            array_push($setGalleryImagesIcons, $assetPath);
                            $asset = Asset::getByPath($assetPath);
                            if(!$asset){
                                $asset = new Asset();
                                $asset->setParentId($assetsFolder->getId());
                                $asset->setKey($assetKey);

                            }
                            $asset->setData($fileData);
                            try {
                                $asset->save();
                            } catch (Exception $e) {
                                //Exception
                            }
                        }

                        if ($lieferumfangData == null) {
                            $lieferumfangData = "&nbsp;";
                        }
                        if ($technicalData == null) {
                            $technicalData = "&nbsp;";
                        }
                        if ($topProductData == null) {
                            $topProductData = "&nbsp;";
                        }
                        $xmlTechnicalData .= $lieferumfangData;
                        $xmlTechnicalData .= $technicalData;
                        $xmlTechnicalData .= $topProductData;

                        foreach ($setGalleryImages as $setGalleryImage) {
                            if (str_contains($setGalleryImage, "_")) {
                                $galleryImgObjects = Asset::getByPath($setGalleryImage);
                                array_push($setGalleryImagesObjects, $galleryImgObjects);
                            } else {
                                $mainImageObjectPath = $setGalleryImage;
                            }
                        }

                        foreach ($setGalleryImagesIcons as $setGalleryImagesIcon){ // icon gallery
                            $galleryIconObjects = Asset::getByPath($setGalleryImagesIcon);
                            array_push($setGalleryIconObjects, $galleryIconObjects);
                        }

                        $finalImageGalleryArray = [];
                        foreach ($setGalleryImagesObjects as $img) {
                            $advancedImage = new DataObject\Data\Hotspotimage();
                            $advancedImage->setImage($img);
                            $finalImageGalleryArray[] = $advancedImage;
                        }

                        $finalIconGalleryArray = [];
                        foreach ($setGalleryIconObjects as $icon) {
                            $advancedImage = new DataObject\Data\Hotspotimage();
                            $advancedImage->setImage($icon);
                            $finalIconGalleryArray[] = $advancedImage;
                        }

                        $product = DataObject\Product::getById($id);
                        $product->setPublished(true);
                        $product->setProductsName($xmlName);
                        $product->setDescription($xmlTechnicalData);
                        $product->setTax("19");
                        $unitKg = DataObject\QuantityValue\Unit::getByAbbreviation("kg");
                        $unitMm = DataObject\QuantityValue\Unit::getByAbbreviation("mm");
                        $product->setWeightBrutto(new InputQuantityValue((string)$bruttoWeight, $unitKg->getId()));
                        $product->setWeightNetto(new InputQuantityValue((string)$nettoWeight, $unitKg->getId()));
                        $product->setDimensionLength(new InputQuantityValue((string)$dimensionLength, $unitMm->getId()));
                        $product->setDimensionWidth(new InputQuantityValue((string)$dimensionWidth, $unitMm->getId()));
                        $product->setDimensionHeight(new InputQuantityValue((string)$dimensionHeight, $unitMm->getId()));
                        $mainImageObject = Asset\Image::getByPath($mainImageObjectPath);
                        $imageGallery = new DataObject\Data\ImageGallery($finalImageGalleryArray);
                        $iconGallery = new DataObject\Data\ImageGallery($finalIconGalleryArray);
                        $product->setIconGallery($iconGallery);
                        $product->setImage($mainImageObject);
                        $product->setImageGallery($imageGallery);
                        $product->setTechnicalBlock($blockElementsTechnical);
                        //$product->setOtherBlock($blockElementsOther);
                        $product->save();
                    }
                }

            }
            $counter++;
        }

    }


}
