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

class ImportBmeStabila extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'importStabila';

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
        $xml = new SimpleXMLElement('BmeStabila.xml', 0, TRUE);
        $counter = 0;

        foreach ($xml->T_NEW_CATALOG->PRODUCT as $result) {
            if ($counter <= 40) {

                $xmlEan = $result->PRODUCT_DETAILS->INTERNATIONAL_PID;

                $eanListObj = DataObject\Product::getList();
                $eanList = $eanListObj->load();
                foreach ($eanList as $list) {
                    if ($list->getEan() == $xmlEan) {
                        $id = $list->getId();
                        $path = $list->getPath();
                        $productName = $list->getKey();

                        $xmlName = $result->PRODUCT_DETAILS->DESCRIPTION_SHORT;
                        echo $xmlName . " - " . $xmlEan . "\n";

                        // description part
                        $xmlTechnicalData = "<p>" . $result->PRODUCT_DETAILS->DESCRIPTION_LONG . "</p>";

                        $technicalData = null;

                        $technicalData .= "<p><strong>Technische Daten:</strong></p>";
                        $technicalData .= "<ul>";
                        foreach ($result->PRODUCT_FEATURES as $items){
                            if($items->REFERENCE_FEATURE_GROUP_NAME == "Technische Daten"){
                                foreach ($items->FEATURE as $item){
                                    $technicalData .= "<li>" . $item->FNAME . " " . $item->FVALUE . " " . $item->FUNIT . "</li>";
                                }
                            }
                        }
                        $technicalData .= "</ul>";

                        $xmlTechnicalData .= $technicalData;

                        // end of description part

                        $bruttoWeight = null;
                        $nettoWeight = null;
                        $dimensionLength = null;
                        $dimensionWidth = null;
                        $dimensionHeight = null;
                        foreach ($result->PRODUCT_LOGISTIC_DETAILS->PRODUCT_DIMENSIONS as $dimension){
                            $nettoWeight = $dimension->WEIGHT;
                            $dimensionLength = $dimension->LENGTH * 10;
                            $dimensionWidth = $dimension->WIDTH * 10;
                            $dimensionHeight = $dimension->DEPTH * 10;
                        }

                        // meta part
                        $xmlMetaTitle = null;
                        $shortDescriptionVar = $result->PRODUCT_DETAILS->DESCRIPTION_SHORT;
                        $checkFirstSixCharacters = substr($shortDescriptionVar, 0, 7);
                        if($checkFirstSixCharacters == "STABILA"){
                            $shortDescriptionVar = substr($shortDescriptionVar, 7);
                            $shortDescriptionVar = "STABILA " . $shortDescriptionVar;
                        }else{
                            $shortDescriptionVar = "STABILA " . $shortDescriptionVar;
                        }

                        if(strlen($shortDescriptionVar) <= 75){
                            $xmlMetaTitle = $shortDescriptionVar;
                        }
                        // end meta part

                        // BME technical data part
                        $blockElementsTechnical = [];
                        $blockElementsOther = [];

                        $articleFeaturesList = [];
                        foreach ($result->PRODUCT_FEATURES as $productPackages) {
                            if($productPackages->REFERENCE_FEATURE_GROUP_NAME == "Technische Daten"){
                                foreach ($productPackages->FEATURE as $productPackage){
                                    $blockElementsTechnical[] = [
                                        "name" => new BlockElement('name', 'input', strval($productPackage->FNAME)),
                                        "val" => new BlockElement('val', 'input', strval($productPackage->FVALUE)),
                                        "unit" => new BlockElement('unit', 'input', strval($productPackage->FUNIT))
                                    ];
                                }
                            }

                        }

                        // end BME technical data part

                        // images part
                        /*$mainImage = null;
                        $mainImageObjectPath = null;
                        $galleryImage = [];
                        $setGalleryImages = [];
                        $setGalleryImagesObjects = [];
                        foreach ($result->MIME_INFO as $articleImages) {
                            foreach ($articleImages as $articleImage) {
                                if ($articleImage->MIME_TYPE == "image/jpeg" && $articleImage->MIME_PURPOSE == "normal" && $articleImage->MIME_ORDER == "1") {
                                    $mainImage = $articleImage->MIME_SOURCE;
                                    array_push($galleryImage, $mainImage);
                                }
                                if ($articleImage->MIME_TYPE == "image/jpeg" && $articleImage->MIME_PURPOSE != "normal") {
                                    $gallery = $articleImage->MIME_SOURCE;
                                    array_push($galleryImage, $gallery);
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

                        foreach ($setGalleryImages as $setGalleryImage) {
                            if (str_contains($setGalleryImage, "_")) {
                                $galleryImgObjects = Asset::getByPath($setGalleryImage);
                                array_push($setGalleryImagesObjects, $galleryImgObjects);
                            } else {
                                $mainImageObjectPath = $setGalleryImage;
                            }
                        }

                        $finalImageGalleryArray = [];
                        foreach ($setGalleryImagesObjects as $img) {
                            $advancedImage = new DataObject\Data\Hotspotimage();
                            $advancedImage->setImage($img);
                            $finalImageGalleryArray[] = $advancedImage;
                        }*/

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
                        $product->setMetaTitle($xmlMetaTitle);
                        //$mainImageObject = Asset\Image::getByPath($mainImageObjectPath);
                        //$imageGallery = new DataObject\Data\ImageGallery($finalImageGalleryArray);
                        //$product->setImage($mainImageObject);
                        //$product->setImageGallery($imageGallery);
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
