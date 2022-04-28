<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Categories;
use Pimcore\Model\DataObject\ClassDefinition\Data\Language;
use Pimcore\Model\DataObject\Manufacturers;
use Pimcore\Model\DataObject\OutputProfile;
use Pimcore\Model\DataObject\Product;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class OutputProfileController extends FrontendController
{
    /**
     * @Template
     * @param Request $request
     */
    public function exportAction(Request $request)
    {
        $profileId = (int)$request->get("selectedOutputProfile");
        if ($profileId) {
            $manufacturers = [];
            $categories = [];
            $attrConfig = [];
            $selectedProfile = OutputProfile::getById($profileId);
            $exportLanguage = $selectedProfile->getExportLanguage();
            if (!$exportLanguage) {
                $exportLanguage = "de";
            }
            $exportFormat = $selectedProfile->getExportType();
            foreach ($selectedProfile->getManufacturers() as $manufacturer) {
                $manufacturers[] = $manufacturer->getId();
            }
            foreach ($selectedProfile->getCategories() as $category) {
                $categories[] = $category->getId();
            }
            $attrConfig = explode(";", Asset::getById($selectedProfile->getExportAttributesConfig()->getId())->getData());
            return $this->makeExport(
                $request,
                $manufacturers,
                $categories,
                $exportFormat,
                $exportLanguage,
                $selectedProfile->getExportSortBy(),
                $selectedProfile->getExportOrder(),
                $attrConfig,
                $selectedProfile->getDownloadMainImage()
            );
        }
        $profiles = [];
        $profiles = OutputProfile::getList()->load();
        return [
            "profiles" => $profiles
        ];
    }

    /**
     * @param Request $request
     * @Route ("/makeExport")
     */
    public function makeExport(Request $request, $manufacturers, $categories, $exportFormat, $language, $sortKey, $sortType, $attrConfig, $downloadImage)
    {
        $categoriesCount = count($categories);
        $files = [];
        $zipName = "imagesPim.zip";
        $zip = new \ZipArchive();
        if (!$language) {
            $language = "de";
        }
        $request->setLocale($language);
        $productList = Product::getList();
        //filters
        if (count($manufacturers) > 0) {
            $productList->addConditionParam("manufacturer__id in (?)", [$manufacturers]);
        }
        if (count($categories) > 0) {
            $categoriesStringQuery = "";
            $categoriesQuery = [];
            $counter = 0;
            foreach ($categories as $category) {
                if ($counter == ($categoriesCount - 1)) {
                    $categoriesStringQuery .= "categories LIKE ?";
                } else {
                    $categoriesStringQuery .= "categories LIKE ? OR ";
                }
                $categoriesQuery[] = "%" . $category . "%";
                $counter++;
            }
            $productList->addConditionParam($categoriesStringQuery, $categoriesQuery);
        }
        $productList->setOrderKey($sortKey);
        $productList->setOrder($sortType);
        $products = $productList->load();
        $productsArray = [];
        if ($downloadImage) {
            $zip->open($zipName, ZipArchive::OVERWRITE | ZipArchive::CREATE);
        }
        foreach ($products as $product) {
            if (!$downloadImage) {
                if ($attrConfig) {
                    $productRow = [];
                    foreach ($attrConfig as $item) {
                        $productRow[$item] = $product->get($item);
                    }
                    $productsArray[] = $productRow;
                } else {
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
            } else {
                if ($product->getImage()) {
                    $zip->addFromString($product->getCategories()[0]->getCategoryName() . "/" . $product->getImage()->getFilename(), $product->getImage()->getData());
                }
            }
        }
        if ($downloadImage) {
            $zip->close();
        }
        if (!$downloadImage) {
            if ($exportFormat == "csv") {
                $this->download_send_headers_csv("data_export_" . date("Y-m-d") . ".csv");
                return new Response($this->makeCSV($productsArray));
            } else {
                $finalJson = ([
                    "products" => $productsArray
                ]);
                $finalJson = json_encode($finalJson);
                $this->download_send_headers_json("data_export_" . date("Y-m-d") . ".json");
                return new Response($this->makeJSON($finalJson));
            }
        } else {
            header('Content-Type: application/zip');
            header('Content-Length: ' . filesize($zipName));
            header('Content-Disposition: attachment; filename="PimImages_' . date("d_m_Y") . '.zip');
            readfile($zipName);
            unlink($zipName);
            return new Response();
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

    function download_send_headers_csv($filename)
    {
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
        fwrite($df, $json);
        fclose($df);
        return ob_get_clean();
    }

    function download_send_headers_json($filename)
    {
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

    function zipFilesAndDownload($file_names, $archive_file_name, $file_path)
    {
        $zip = new ZipArchive();
        //create the file and throw the error if unsuccessful
        if ($zip->open($file_path . $archive_file_name, ZipArchive::OVERWRITE) !== TRUE) {
            exit("cannot open <$archive_file_name>\n");
        }
        //add each files of $file_name array to archive
        foreach ($file_names as $files) {
            $zip->addFile($file_path . $files, $files);
            /*if(file_exists($file_path.$files)) //." ".$files."<br>";
                echo "yes";*/
        }
        $zip->close();
        //then send the headers to foce download the zip file
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$archive_file_name");
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($file_path . $archive_file_name);
        exit;
    }
}
