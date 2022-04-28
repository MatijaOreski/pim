<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Categories;
use Pimcore\Model\DataObject\ClassDefinition\Data\Language;
use Pimcore\Model\DataObject\Manufacturers;
use Pimcore\Model\DataObject\Product;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportJsonController extends FrontendController
{



    /**
     * @Template
     * @param Request $request
     * @return array
     */
    public function importJsonAction(Request $request)
    {
        $columnNames = [];
        $lines = [];


        if(isset($_POST['upload'])){
            $tmpFile = $_FILES["file"]["tmp_name"];

            $handle = fopen($tmpFile, 'r');
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
            foreach ($columnNames as $name){
                echo $name['Product name'] . "<br>";
            }

        }

        if(isset($_POST['import'])){
            $selectedAtributesPaired = $request->get("listOfPairedAttributes");
            $folderId = $request->get("folderId");

            $manufacturerObjectBool = false;
            $categoriesObjectBool = false;
            if(in_array("Manufacturer;manufacturer", $selectedAtributesPaired)){
                $manufacturerKey = array_search('Manufacturer;manufacturer',$selectedAtributesPaired);
                unset($selectedAtributesPaired[$manufacturerKey]);
                $manufacturerObjectBool = true;
            }
            if(in_array("Category;categories", $selectedAtributesPaired)){
                $categoriesKey = array_search('Category;categories',$selectedAtributesPaired);
                unset($selectedAtributesPaired[$categoriesKey]);
                $categoriesObjectBool = true;
            }

            $tmpFile = $_FILES["importFile"]["tmp_name"];
            $handle = fopen($tmpFile, 'r');
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

            $selectedColumnNames = [];
            $selectedValues = [];
            foreach ($selectedAtributesPaired as $selectedAtributesPairedResult){
                $selectedArray = explode(";", $selectedAtributesPairedResult);
                $selectedColumnNames[] = $selectedArray[0];
                $selectedValues[] = $selectedArray[1];
            }

            $productKey = null;
            $manufacturerObj = null;
            $newProductsList = [];

            foreach ($lines as $data){
                $product = new Product();

                if($manufacturerObjectBool == true){
                    $manufacturerObj = null;
                    $manufacturerListObj = DataObject\Manufacturers::getList();
                    $manufacturersList = $manufacturerListObj->load();
                    $manufacturer_name =$data['Manufacturer'];

                    foreach ($manufacturersList as $manufacturer){
                        if($manufacturer->getName() == $manufacturer_name){
                            $manufacturerObj = $manufacturer;
                        }
                    }
                }

                if($categoriesObjectBool == true){
                    $categoriesObj = [];
                    $categoriesListObj = DataObject\Categories::getList();
                    $categoriesList = $categoriesListObj->load();

                    foreach ($categoriesList as $category){

                        if($category->getCategoryName("de") == $data['Category']){
                            array_push($categoriesObj, $category);
                        }
                    }
                }

                foreach ($selectedColumnNames as $key => $value){

                    if($selectedValues[$key] == "productsName"){
                        $productKey = $data[$selectedColumnNames[$key]];
                    }

                    $product->set($selectedValues[$key], $data[$selectedColumnNames[$key]], "de");

                }

                $product->setParentId((int)$folderId);
                $product->setPublished(true);
                $product->setKey($productKey);
                if($manufacturerObj != null){
                    $product->setManufacturer($manufacturerObj);
                }
                if($categoriesObjectBool == true){
                    $product->setCategories($categoriesObj);
                }
                $newProductsList[] = $product;

            }

            foreach ($newProductsList as $product){
                $product->save();
            }


        }

        return ["columnNames" => $columnNames];



        /*$newProductsList = [];

        $attributes = [];
        $selectedAtributesPaired = $request->get("listOfPairedAttributes")?$request->get("listOfPairedAttributes"):[];
        $rowsOfFile = [];


        if($request->get("rowsOfFile")){
            $rowsOfFile = $request->get("rowsOfFile");

            $attrsFromFile = explode(";",$rowsOfFile[0]);
            $selectedIndexes = [];

            $counter = 0;
            foreach ($attrsFromFile as $attrFromFile){
                foreach ($selectedAtributesPaired as $selectedAttrPair){
                    if(explode(";",$selectedAttrPair)[0] == $attrFromFile){
                        $selectedIndexes[] = $counter.";".explode(";",$selectedAttrPair)[1];
                    }
                }
                $counter++;
            }

            $counter = 0;

            foreach ($rowsOfFile as $row){

                $folder = DataObject\Service::createFolderByPath("/NewProducts");

                $product = new Product();

                if($counter!=0){
                    $values = explode(";",$row);

                    $productKey = "";

                    foreach ($selectedIndexes as $selectedIndex){

                        if(explode(";",$selectedIndex)[1] == "productsName"){
                            $productKey = $values[intval(explode(";",$selectedIndex)[0])];
                        }

                        $product->set(explode(";",$selectedIndex)[1],$values[intval(explode(";",$selectedIndex)[0])],"de");


                    }


                    $product->setParent($folder);
                    $product->setPublished(true);
                    $product->setKey($productKey);
                    $newProductsList[] = $product;

                }
                $counter++;

            }


        }

        if(array_key_exists("file",$_FILES)){
            $tmpFile = $_FILES["file"]["tmp_name"];
            $row = 0;
            if (($handle = fopen($tmpFile, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $num = count($data);

                    //$row++;
                    for ($c=0; $c < $num; $c++) {

                        $attrList =  explode(";",$data[$c]);
                        if($row == 0) {
                            foreach ($attrList as $item) {
                                $attributes[] = $item;
                            }

                        }
                        $rowsOfFile[] = $data[$c];


                    }
                    $row++;
                }
                fclose($handle);
            }
        }


        foreach ($newProductsList as $product){
            $product->save();
        }


        return [
            "csvAttrList" => $attributes,
            "rowsOfFile" => $rowsOfFile
        ];*/
    }



}
