<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use PharIo\Version\Exception;
use Pimcore\File;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\ExternalImage;
use function Symfony\Component\String\b;

class ImportBaseAssetsCommand extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'qw:import:base-assets';

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

	//function for importing images
    function importImages(){
        $productsDataObjects = DataObject\Product::getList();
        $productsDataObjectsList = $productsDataObjects->load();

        foreach ($productsDataObjectsList as $product){
            echo $product->getPath()."\n";
            $files = [];

            $manufacturerAssetsFolder = Asset\Service::createFolderByPath($product->getPath());

            $counter=0;
            while(file_exists($counter == 0 ? "public/var/unsorted/productsPics/" . $product->getEan() . ".jpg" : "public/var/unsorted/productsPics/" . $product->getEan() . "_" . strval($counter) . ".jpg") && file_get_contents($counter == 0 ? "public/var/unsorted/productsPics/" . $product->getEan() . ".jpg" : "public/var/unsorted/productsPics/" . $product->getEan() . "_" . strval($counter) . ".jpg")){
               if($counter == 0){
                   $fileData = file_get_contents("public/var/unsorted/productsPics/" . $product->getEan().".jpg");
                   array_push($files,$fileData);
               }
               else{
                   $fileData = file_get_contents("public/var/unsorted/productsPics/" . $product->getEan()."_".strval($counter).".jpg");
                   array_push($files,$fileData);
               }
               $counter++;
            }

            $counter = 0;
            $imagesForGallery = [];

            foreach ($files as $fileData){
                $assetKey = $counter == 0 ? File::getValidFilename($product->getKey()) : File::getValidFilename($product->getKey()."_".strval($counter));
                $assetPath = $manufacturerAssetsFolder->getFullPath()."/".$assetKey;
                $asset = Asset::getByPath($assetPath);
                if(!$asset){
                    $asset = new Asset\Image();
                    $asset->setParentId($manufacturerAssetsFolder->getId());
                    $asset->setKey($assetKey);
                }
                $asset->setData($fileData);
                try {
                    if($counter == 0){
                        $product->setImage($asset);
                    }
                    else{
                        $advImage = new DataObject\Data\Hotspotimage();
                        $advImage->setImage($asset);
                        array_push($imagesForGallery,$advImage);
                    }
                    $asset->save();
                } catch (\Exception $e) {
                    //Exception
                }
                $counter++;
            }

            $imageGallery = new DataObject\Data\ImageGallery($imagesForGallery);
            $product->setImageGallery($imageGallery);
            try {
                $product->save();
            } catch (\Exception $e) {
            }

        }

    }

    private function removeAllAssets(){
        $assets = Asset::getList();
        /**
         * @var Asset $asset
         */
        foreach ($assets as $asset){
            if ($asset->getId() != 1){
                $asset->delete();
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function importNewAssets($path)
    {
        //$downloadAssetFolder = Asset\Service::createFolderByPath("/Bosch");
        //$downloadObjectFolder = DataObject\Service::createFolderByPath("/Elektrowerkzeuge");

        // importer for images (Assets)
        //$csv = $this->readCSV("public/var/unsorted/".$path);
        //$this->importImages();

        // importer for category tree - folders
        //$csv = $this->readCSV("public/var/unsorted/".$path);
        //$this->recursiveImport("public/var/unsorted/".$path);

        // importer for category tree - objects
        //$csv = $this->readCSV("public/var/unsorted/".$path);
        //$this->recursiveImportCategoryObjects("public/var/unsorted/".$path);

        // importer for manufacturers
        //$csv = $this->readCSV("public/var/unsorted/".$path);
        //$this->importManufacturers("public/var/unsorted/".$path);

        // importer for localized products fields

        $csv = $this->readCSV($path);
        $jsonArray = \Safe\json_decode(file_get_contents($path),true);

        foreach ($jsonArray as $json){

            if(file_exists("public/var/unsorted/produkte_nl.json")){

                $id = null;
                $ean = null;
                $eanListObj = DataObject\Product::getList();
                $eanList = $eanListObj->load();

                foreach ($eanList as $list){
                    if($list->getEan() == $json['products_upc']){
                        $id = $list->getId();
                        $ean = $list->getEan();
						if($ean == $json['products_upc']){
							$products = DataObject\Product::getById($id);
							$products->setPublished(true);
							$products->setProductsName($json['products_name'], "nl_NL");
							$products->setDescription($json['products_description'], "nl_NL");
							$products->save();

							echo $json['products_name'] . " - " . $json['products_upc'] . "\n";
						}
                    }
                }

            }else{
                echo "File does not exists!";
            }

        }

        //importer for cros sale

        /*$csv = $this->readCSV($path);
        $jsonArray = \Safe\json_decode(file_get_contents($path),true);
        $crossSaleList = [];
        //$counter = 1;
        $currentEan = "";
        $arrayOfCrossSalesOfEan = [];
        foreach ($jsonArray as $row){

                if (file_exists("public/var/unsorted/cros_sale.json")) {

                    if($currentEan == ""){
                        $currentEan = $row["product"];
                    }

                    if($currentEan != "" && $currentEan != $row["product"]){
                        $arrayOfCrossSalesOfEan = [];
                        $currentEan = $row["product"];
                    }

                    $arrayOfCrossSalesOfEan[] = $row["crosSale"];
                    $arrayOfCrossSalesOfEan = array_unique($arrayOfCrossSalesOfEan);
                    $crossSaleList[$row["product"]] = $arrayOfCrossSalesOfEan;


                    $crossSaleListKeys = array_keys($crossSaleList);
                    $counterkey = 0;

                    foreach ($crossSaleList as $crosSale){

                        $crosSaleProductObject = $this->getProductObjectByEan($crossSaleListKeys[$counterkey]);

                        if ($crosSaleProductObject != null) {
                            $crosSalesValues = array_values($crosSale);
                            $newCrosSaleValuesObjectsList = [];
                            foreach ($crosSalesValues as $value) {

                                $newCrosSaleValueObject = $this->getProductObjectByEan($value);
                                if ($newCrosSaleValueObject != null) {
                                    $newCrosSaleValuesObjectsList[] = $newCrosSaleValueObject;
                                }
                            }
                            $crosSaleProductObject->setAccessories($newCrosSaleValuesObjectsList);
                            $crosSaleProductObject->save();
                            var_dump($newCrosSaleValuesObjectsList);
                            $newCrosSaleValuesObjectsList = [];
                            $crosSalesValues = null;
                        }
                        $counterkey++;


                    }

                } else {
                    echo "File does not exists!";
                }
                //$counter++;
                //if($counter==50) break;
        }*/

        // Importer for products

        //$csv = $this->readCSV($path);
        //$jsonArray = \Safe\json_decode(file_get_contents($path),true);
        //$productsCount = DataObject::getList()->getTotalCount();

        //echo count($jsonArray);
        //echo $productsCount;


       /*foreach ($jsonArray as $cur) {

            if (file_exists("public/var/unsorted/categories_description.json") ) {

                    $download = new DataObject\Product();

                    // get right category folder by categories in csv file
                    $folder = null;
                    $folderListObj = DataObject\Categories::getList();
                    $folderList = $folderListObj->load();

                    foreach($folderList as $folderName){
                            if($folderName->getCategoryName() == $cur['categories_name']){
                                    echo $cur['categories_name'];
                                    $folder = $cur['categories_name'];
                        }
                    }

                    // get right category folder id
                    $id = null;
                    $test = DataObject\Folder::getList();
                    $list = $test->load();

                    foreach($list as $i){
                        if($i->getKey() == $folder){
                            $id = $i->getId();
                        }
                    }

                    // get and save right manufacturer for product
                    $manufacturerObj = null;
                    $manufacturerListObj = DataObject\Manufacturers::getList();
                    $manufacturersList = $manufacturerListObj->load();
                    $manufacturer_name =$cur['manufacturers_name'];

                    foreach ($manufacturersList as $manufacturer){
                        if($manufacturer->getName() == $manufacturer_name){
                            $manufacturerObj = $manufacturer;
                        }
                    }

                    //get and save right category for product
                    $categoriesObj = [];
                    $categoriesListObj = DataObject\Categories::getList();
                    $categoriesList = $categoriesListObj->load();

                    foreach ($categoriesList as $category){

                            if($category->getCategoryName("de") == $cur['categories_name']){
                                array_push($categoriesObj, $category);
                            }


                    }

                    $download->setPublished(true);
                        $download->setParentId($id);
                        $productFolder = DataObject\Folder::getById($id);
                        if($productFolder) {
                            $productFolderPath = $productFolder->getFullPath();
                            $productObjectCheck = DataObject::getByPath($productFolderPath . "/" . File::getValidFilename($cur["products_name"]));
                            if (array_key_exists("products_name", $cur) && !$productObjectCheck) {
                                $download->setKey(File::getValidFilename($cur["products_name"]));
                                $newAkuBateryObjectBrick = new DataObject\Objectbrick\Data\AkkuBattery($download);
                                $newAkuBateryObjectBrick->setBatteryCapacity()
                                $download->getAttributesProperties()->setakkuBattery($newAkuBateryObjectBrick);
                                $download->setProductsName($cur['products_name']);
                                $download->setDescription(array_key_exists("products_description", $cur) ? $cur['products_description'] : "");
                                $download->setManufacturer($manufacturerObj);
                                $download->setEan(array_key_exists("products_upc", $cur) ? $cur['products_upc'] : "");
                                $download->setMpn(array_key_exists("products_model", $cur) ? $cur['products_model'] : "");
                                $download->setPriceNetto(array_key_exists("products_price", $cur) ? floatval($cur['products_price']) : 0);
                                $download->setPriceBrutto(array_key_exists("products_price", $cur) ? floatval($cur['products_price']) * 1.19 : 0);
                                $download->setProductBasePrice(array_key_exists("products_base_price", $cur) ? floatval($cur['products_base_price']) : 0);
                                $download->setProductBasePriceCheck(array_key_exists("products_base_price_check", $cur) ? floatval($cur['products_base_price_check']) : 0);
                                $download->setEBayPrice(array_key_exists("ebay_price", $cur) ? floatval($cur['ebay_price']) : 0);
                                $download->setQuantity(array_key_exists("products_quantity", $cur) ? floatval($cur['products_quantity']) : 0);
                                $download->setWeight(array_key_exists("products_weight", $cur) ? floatval($cur['products_weight']) : 0);
                                $download->setShopwareId(array_key_exists("categories_id", $cur) ? $cur['categories_id'] : "");
                                $download->setCategories($categoriesObj);
                                //$download->setAttributesProperties($test);
                                $download->setSpecifics(array_key_exists("products_eigenschaften", $cur) ? $cur['products_eigenschaften'] : "");
                                $download->save();
                            }
                        }

            }

       }*/

    }

    function getProductObjectByEan($ean){
        $allProductList = DataObject\Product::getList();
        $allProductObjectsList = $allProductList->load();
        foreach ($allProductObjectsList as $product){
            if($product->getEan() == strval($ean)){
                return $product;
            }
        }
        return null;

    }


    //recursive function for categories tree import

    public function recursiveImport($path){
        $newCurrentParents = [0];
        $newPaths = [0];
        $csv = $this->readCSV($path);
        if (file_exists("public/var/unsorted/categories.CSV")) {
            $counter = 1;
            foreach ($csv as $cur) {

                foreach ($newCurrentParents as $id){

                    if($cur["parent_id"] == $id){

                        if($id == 0) {
                            DataObject\Service::createFolderByPath("/Pim/Products/" . $cur["categories_name"]);
                            Asset\Service::createFolderByPath("/Pim/Products/" . $cur["categories_name"]);
                            array_push($newCurrentParents, $cur["categories_id"]);
                            array_push($newPaths, "/Pim/Products/" . $cur["categories_name"]);

                            foreach($csv as $cur1){
                                if($cur1['parent_id'] == $newCurrentParents[$counter]){
                                    $categories_id = $cur1['categories_id'];
                                    $secondLevel = DataObject\Service::createFolderByPath($newPaths[$counter] . "/" . $cur1['categories_name']);
                                    DataObject\Service::createFolderByPath($newPaths[$counter] . "/" . $cur1['categories_name']);
                                    Asset\Service::createFolderByPath($newPaths[$counter] . "/" . $cur1['categories_name']);
                                    //$secondLevel = $newPaths[$counter] . "/" . $cur1['categories_name'];

                                    foreach ($csv as $cur2){
                                        if($categories_id == $cur2['parent_id']){
                                            $thirdLevel = DataObject\Service::createFolderByPath($secondLevel . "/" . $cur2['categories_name']);
                                            DataObject\Service::createFolderByPath($secondLevel . "/" . $cur2['categories_name']);
                                            Asset\Service::createFolderByPath($secondLevel . "/" . $cur2['categories_name']);
                                            //$thirdLevel = $secondLevel . "/" . $cur2['categories_name']; // here starts new

                                            $array = [];
                                            array_push($array, $cur2['parent_id']);

                                            foreach ($array as $par_id){
                                                // give good parent id and correct number
                                                if($par_id == $cur2['parent_id']){
                                                    $cat_id = $cur2['categories_id'];
                                                    foreach ($csv as $cur3){
                                                        if($cur3['parent_id'] == $cat_id){
                                                            $cat_id_3 = $cur3['categories_id'];
                                                            $fourthLevel = DataObject\Service::createFolderByPath($thirdLevel . "/" . $cur3['categories_name']);
                                                            DataObject\Service::createFolderByPath($thirdLevel . "/" . $cur3['categories_name']);
                                                            Asset\Service::createFolderByPath($thirdLevel . "/" . $cur3['categories_name']);

                                                            foreach ($csv as $cur4){
                                                                if($cur4['parent_id'] == $cat_id_3){
                                                                    $fifthLevel = DataObject\Service::createFolderByPath($fourthLevel . "/" . $cur4['categories_name']);
                                                                    DataObject\Service::createFolderByPath($fourthLevel . "/" . $cur4['categories_name']);
                                                                    Asset\Service::createFolderByPath($fourthLevel . "/" . $cur4['categories_name']);

                                                                    $array1 = [];
                                                                    array_push($array1, $cur4['parent_id']);

                                                                    foreach ($array1 as $par_id1){
                                                                        if($par_id1 == $cur4['parent_id']){
                                                                            $cat_id_4 = $cur4['categories_id'];
                                                                            foreach ($csv as $cur5){
                                                                                if($cur5['parent_id'] == $cat_id_4){
                                                                                    $cat_id_5 = $cur5['categories_id'];
                                                                                    $sixthLevel = DataObject\Service::createFolderByPath($fifthLevel . "/" . $cur5['categories_name']);
                                                                                    DataObject\Service::createFolderByPath($fifthLevel . "/" . $cur5['categories_name']);
                                                                                    Asset\Service::createFolderByPath($fifthLevel . "/" . $cur5['categories_name']);

                                                                                    foreach ($csv as $cur6){
                                                                                        if($cur6['parent_id'] == $cat_id_5){
                                                                                            $seventhLevel = DataObject\Service::createFolderByPath($sixthLevel . "/" . $cur6['categories_name']);
                                                                                            DataObject\Service::createFolderByPath($sixthLevel . "/" . $cur6['categories_name']);
                                                                                            Asset\Service::createFolderByPath($sixthLevel . "/" . $cur6['categories_name']);
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                }

                                            }

                                        }

                                    }

                                }

                            }
                            $newCurrentParents = [0];
                            $newPaths = [0];
                        }

                    }

                }

            }
        }else{
            echo "File does not exists!";
        }

        $this->recursiveImport("public/var/unsorted/categories.CSV");
    }

    public function recursiveImportCategoryObjects($path){
        $csv = $this->readCSV($path);
        if (file_exists("public/var/unsorted/categories.CSV")){
            foreach ($csv as $cur){
                            if(array_key_exists("categories_name",$cur)){
                                $categoryRootFolder = DataObject\Service::createFolderByPath("/Pim/Categories");
                                $checkIfExistsObj = DataObject\Categories::getByPath($categoryRootFolder ."/". File::getValidFilename($cur["categories_name"]));
                                if($checkIfExistsObj==null){

                                    $category = new DataObject\Categories();
                                    $category->setPublished(true);
                                    $category->setParent($categoryRootFolder);
                                    $category->setKey(File::getValidFilename($cur["categories_name"]));
                                    $category->setCategoryName($cur['categories_name'], "de");
                                    $category->setCategoryName($cur['categories_name_fr'], "fr");
                                    $category->setCategoryName($this->replaceCroatianLetters($cur['categories_name_hr']), "hr");
                                    $category->setCategoryName($cur['categories_name_es'], "es");
                                    $category->setCategoryName($cur['categories_name_it'], "it");
                                    $category->setCategoryName($cur['categories_name_nl'], "nl_NL");
                                    $category->setCategoryId($cur['categories_id']);
                                    $category->setDescription($cur['categories_htc_description']);
                                    $category->save();


                                }

                            }
            }
        }

        $this->recursiveImportCategoryObjects("public/var/unsorted/categories.CSV");
    }

    function replaceCroatianLetters($word){
        $word = str_replace("&#268;","Č",$word);
        $word = str_replace("&#269;","č",$word);
        $word = str_replace("&#262;","Ć",$word);
        $word = str_replace("&#263;","ć",$word);
        $word = str_replace("&#381;","Ž",$word);
        $word = str_replace("&#382;","ž",$word);
        $word = str_replace("&#352;","Š",$word);
        $word = str_replace("&#353;","š",$word);
		$word = str_replace("&#273;","đ",$word);
		$word = str_replace("&amp#272;","Đ",$word);

        return $word;
    }

    // import for manufacturers

    public function importManufacturers($path)
    {
        $csv = $this->readCSV($path);
        $csvPicturesNames = $this->readCSV("public/var/unsorted/manufacPics/images.CSV");
        foreach ($csv as $cur){
            $manufacturerObjectFolder = DataObject\Service::createFolderByPath("/Pim/Manufacturers/");
            if(file_exists("public/var/unsorted/manufacturers.CSV")){
                $manufacturer = new DataObject\Manufacturers();
                $manufacturer->setPublished(true);
                $manufacturer->setParent($manufacturerObjectFolder);
                $manufacturer->setKey(File::getValidFilename($cur["manufacturers_name"]));
                $manufacturer->setName($cur['manufacturers_name']);
                $assetPic = Asset\Image::getByPath("/Pim/Manufacturers/".$cur['manufacturers_name'].".jpg");
                $manufacturer->setImage($assetPic);
                $manufacturer->save();
            }else{
                echo "File does not exists!";
            }
        }

       /* foreach ($csvPicturesNames as $cur){
            $manufacturerAssetsFolder = Asset\Service::createFolderByPath("/Pim/Manufacturers/");
            $fileData = file_get_contents("public/var/unsorted/manufacPics/" . $cur["Name"]);
            $assetKey = File::getValidFilename($cur["Name"]);
            $assetPath = $manufacturerAssetsFolder->getFullPath()."/".$assetKey;
            $asset = Asset::getByPath($assetPath);
            if(!$asset){
                $asset = new Asset();
                $asset->setParentId($manufacturerAssetsFolder->getId());
                $asset->setKey($assetKey);

            }
            $asset->setData($fileData);
            try {
                $asset->save();
            } catch (Exception $e) {
                //Exception
            }
        }*/



        $this->importManufacturers("public/var/unsorted/manufacturers.CSV");
    }

}
