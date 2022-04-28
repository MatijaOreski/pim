<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject\Categories;
use Pimcore\Model\DataObject\ClassDefinition\Data\Language;
use Pimcore\Model\DataObject\Manufacturers;
use Pimcore\Model\DataObject\Product;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends FrontendController
{
    /**
     * @Template
     * @param Request $request
     * @return array
     */
    public function exportAction(Request $request)
    {

        $request->setLocale("de");

        $allLanguages = [
            "de","fr","it","en","nl","hr"
        ];
        $allCategories = Categories::getList()->load();
        $allManufacturers = Manufacturers::getList()->load();

        return [
            "allCategories"=>$allCategories,
            "allManufacturers"=>$allManufacturers,
            "allLanguages"=>$allLanguages,
        ];
    }

    /**
     * @param Request $request
     * @Route ("/makeExport")
     */
    public function makeExport(Request $request)
    {

        $exportFormat = $request->get("exportformat");
        $language = $request->get("language");
        $manufacturers = $request->get("manufacturer")?$request->get("manufacturer"):[];
        $categories = $request->get("category")?$request->get("category"):[];
        $categoriesCount = count($categories);

        if(!$language){
            $language = "de";
        }

        $request->setLocale($language);

        $productList = Product::getList();


        //filters
        if(count($manufacturers)>0){
            $productList->addConditionParam("manufacturer__id in (?)",[$manufacturers]);
        }
        if(count($categories)>0){
            $categoriesStringQuery = "";
            $categoriesQuery = [];
            $counter = 0;
            foreach ($categories as $category){
                if($counter == ($categoriesCount-1)){
                    $categoriesStringQuery.="categories LIKE ?";
                }
                else{
                    $categoriesStringQuery.="categories LIKE ? OR ";
                }

                $categoriesQuery[] = "%".$category."%";
                $counter++;
            }
            $productList->addConditionParam($categoriesStringQuery,$categoriesQuery);
        }


        $products = $productList->load();
        $productsArray = [];

        foreach ($products as $product){
            $productsArray[] = [
                "Product name" => $product->getProductsName(),
                "Product description" => $product->getDescription(),
                "EAN" => $product->getEan(),
                //"Manufacturer" => $product->getManufacturer()->
                "MPN" => $product->getMpn(),
                "Shopware Id" => $product->getShopwareId(),
                "Price brutto" => $product->getPriceBrutto(),
                "Price netto" => $product->getPriceNetto(),
                // "Product category" => $product->getCategories()[0]->getCategoryName()
            ];
        }

        if($exportFormat=="csv"){
            $this->download_send_headers_csv("data_export_" . date("Y-m-d") . ".csv");
            return new Response($this->makeCSV($productsArray));
        }
        else{
            $finalJson = ([
                "products" => $productsArray
            ]);
            $finalJson = json_encode($finalJson);
            $this->download_send_headers_json("data_export_" . date("Y-m-d") . ".json");
            return new Response($this->makeJSON($finalJson));
        }

    }

    function makeCSV(array &$array)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }

    function download_send_headers_csv($filename) {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }


    function makeJSON($json)
    {
        if (!$json) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');

        fwrite($df,$json);

        fclose($df);
        return ob_get_clean();
    }

    function download_send_headers_json($filename) {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/json");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }

}
