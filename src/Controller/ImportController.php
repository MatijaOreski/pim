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

class ImportController extends FrontendController
{

    /**
     * @Template
     * @param Request $request
     * @return array
     */
    public function importAction(Request $request)
    {
        $columnNames = [];
        $lines = [];

        if(isset($_POST['upload'])){
            $tmpFile = $_FILES["file"]["tmp_name"];

            $handle = fopen($tmpFile, 'r');
            $row = 1;
            $columnNames = [];
            while (!feof($handle)) {
                $line = fgetcsv($handle, 20000, "\t");
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
        }

        if(isset($_POST['import'])){
            $selectedAtributesPaired = $request->get("listOfPairedAttributes");
            $folderId = $request->get("folderId");
            $countrySelect = $request->get("countySelect");

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
                $line = fgetcsv($handle, 20000, "\t");
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
                    $manufacturer_name = $data['Manufacturer'];

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

                    $product->set($selectedValues[$key], $data[$selectedColumnNames[$key]], $countrySelect);

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

    }

}
