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

class ImportBmeHikoki extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'importHikoki';

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
        $xml = new SimpleXMLElement('BmeHikoki.xml', 0, TRUE);
        $counter = 0;

        foreach ($xml->T_NEW_CATALOG->PRODUCT as $result) {
            if ($counter <= 10) {

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

                        $highlights = null;
                        $lieferumfang = null;
                        $technicalData = null;
                        $technicalDataSwitch = null;

                        foreach ($result->PRODUCT_FEATURES->FEATURE as $tech){
                            if(!str_contains($tech->FNAME, "Highlights") && !str_contains($tech->FNAME, "Lieferumfang")){
                                $technicalDataSwitch = true;
                            }
                        }

                        foreach ($result->PRODUCT_FEATURES->FEATURE as $items){
                            if(!str_contains($items->FNAME, "Highlights_PK")){
                                if(str_contains($items->FNAME, "Highlights")){
                                    $highlights .= "<p>Highlights</p>";
                                    $highlights .= "<ul>";
                                    foreach ($items->FVALUE as $item){
                                        $highlights .= "<li>" . $item . "</li>";
                                    }
                                    $highlights .= "</ul>";
                                }elseif (str_contains($items->FNAME, "Lieferumfang")){
                                    $lieferumfang .= "<p>Lieferumfang</p>";
                                    $lieferumfang .= "<ul>";
                                    foreach ($items->FVALUE as $item){
                                        $lieferumfang .= "<li>" . $item . "</li>";
                                    }
                                    $lieferumfang .= "</ul>";
                                }else{
                                    $technicalData .= "<li>";
                                    $technicalData .= $items->FNAME;
                                    foreach ($items->FVALUE as $item){
                                        $technicalData .= $item . " ";
                                    }
                                    $technicalData .= $items->FUNIT . "</li>";
                                }
                            }
                        }

                        $xmlTechnicalData .= $highlights;
                        $xmlTechnicalData .= $lieferumfang;
                        if($technicalDataSwitch == true){
                            $xmlTechnicalData .= "<p>Technical Data</p>";
                            $xmlTechnicalData .= "<ul>";
                            $xmlTechnicalData .= $technicalData;
                            $xmlTechnicalData .= "</ul>";
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
                                if ($productPackage->FNAME == "Netto-Gewicht (kg)") {
                                    $nettoWeight = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "Länge") {
                                    $dimensionLength = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "PackagingWidth") {
                                    $dimensionWidth = $productPackage->FVALUE;
                                }
                                if ($productPackage->FNAME == "PackagingHeight") {
                                    $dimensionHeight = $productPackage->FVALUE;
                                }
                            }
                        }

                        // BME technical data part
                        $blockElementsTechnical = [];
                        $blockElementsOther = [];
                        $arrayProductPackage = null;

                        $articleFeaturesList = [];
                        foreach ($result->PRODUCT_FEATURES->FEATURE as $productPackage){
                            if(!str_contains($productPackage->FNAME, "Highlights") && !str_contains($productPackage->FNAME, "Lieferumfang")){
                                foreach ($productPackage->FVALUE as $productPackag){
                                    $arrayProductPackage .= $productPackag . " ";
                                }
                                $blockElementsTechnical[] = [
                                    "name" => new BlockElement('name', 'input', strval($productPackage->FNAME)),
                                    "val" => new BlockElement('val', 'input', strval($arrayProductPackage)),
                                    "unit" => new BlockElement('unit', 'input', strval($productPackage->FUNIT))
                                ];
                            }
                            $arrayProductPackage = null;
                        }

                        // end BME technical data part

                        // images part

                        /*$udx_edxf_mime_info = "UDX.EDXF.MIME_INFO";
                        $udx_edxf_mime = "UDX.EDXF.MIME";
                        $udx_edxf_mime_filename = "UDX.EDXF.MIME_FILENAME";
                        $udx_edxf_mime_code = "UDX.EDXF.MIME_CODE";

                        $mainImage = null;
                        $mainImageObjectPath = null;
                        $galleryImage = [];
                        $setGalleryImages = [];
                        $setGalleryImagesObjects = [];
                        foreach ($result->USER_DEFINED_EXTENSIONS->$udx_edxf_mime_info->$udx_edxf_mime as $articleImage) {
                            if ($articleImage->$udx_edxf_mime_code == "Produktbild" && !str_contains($articleImage->FNAME, "pdf")) {
                                $mainImage = $articleImage->$udx_edxf_mime_filename;
                                array_push($galleryImage, $mainImage);
                            }
                            if ($articleImage->$udx_edxf_mime_code != "Produktbild" && !str_contains($articleImage->FNAME, "pdf")) {
                                $gallery = $articleImage->$udx_edxf_mime_filename;
                                array_push($galleryImage, $gallery);
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
