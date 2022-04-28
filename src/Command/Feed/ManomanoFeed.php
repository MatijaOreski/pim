<?php
// src/Command/CreateUserCommand.php
namespace App\Command\Feed;

use PharIo\Version\Exception;
use Pimcore\Db;
use Pimcore\File;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Symfony\Component\HttpFoundation\Response;
use function Symfony\Component\String\b;

class ManomanoFeed extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:manomanoFeeds';

    protected function configure(): void
    {
        //$this->addArgument('path', InputArgument::REQUIRED, 'The CSV-File with import data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$path = $input->getArgument('path');
        //$this->removeAllAssets();
        $this->importNewAssets();

        $output->writeln("Success");
        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function importNewAssets()
    {
        $productDataObject = DataObject\Product::getList();
        $productDataObjectArray = $productDataObject->load();
        $productsFeedmanomano = [];

        foreach ($productDataObjectArray as $productDataObjectResult){
            if($productDataObjectResult->getPublished() == true){
                $productsFeedmanomano[] = [
                    "sku" => $productDataObjectResult->getEan(),
                    "sku_manufacturer" => $productDataObjectResult->getMpn(),
                    "manufacturer" => $productDataObjectResult->getManufacturer()->getName(),
                    "ean" => $productDataObjectResult->getEan(),
                    "title" => $productDataObjectResult->getProductsName(),
                    "description" => $productDataObjectResult->getDescription(),
                    "product_price_vat_inc" => $productDataObjectResult->getPriceBrutto(),
                    //"shipping_price_vat_inc" => $productDataObjectResult->getPriceBrutto(),
                    //"quantity" => $productDataObjectResult->getPriceBrutto(),
                    "brand" => $productDataObjectResult->getManufacturer()->getName(),
                    "merchant_category" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    //"product_url" => $productDataObjectResult->getPriceBrutto(),
                    //"image_1" => $productDataObjectResult->getPriceBrutto(),
                    //"image_2" => $productDataObjectResult->getPriceBrutto(),
                    //"image_3" => $productDataObjectResult->getPriceBrutto(),
                    //"image_4" => $productDataObjectResult->getPriceBrutto(),
                    //"image_5" => $productDataObjectResult->getPriceBrutto(),retail_price_vat_inc
                    //"retail_price_vat_inc" => $productDataObjectResult->getPriceBrutto(),
                    //"manufacturer_pdf" => $productDataObjectResult->getPriceBrutto(),
                    //"ParentSKU" => $productDataObjectResult->getPriceBrutto(),
                    //"Cross_Sell_SKU" => $productDataObjectResult->getPriceBrutto(),
                    //"ManufacturerWarrantyTime" => $productDataObjectResult->getPriceBrutto(),
                    "carrier" => "DHL",
                    "shipping_time" => "3#5",
                    "use_grid" => 0,
                    //"carrier_grid_1" => $productDataObjectResult->getPriceBrutto(),
                    //"shipping_time_carrier_grid_1" => $productDataObjectResult->getPriceBrutto(),
                    //"DisplayWeight" => $productDataObjectResult->getPriceBrutto(),
                    //"free_return" => $productDataObjectResult->getPriceBrutto(),
                    //"eco_participation" => $productDataObjectResult->getPriceBrutto(),
                    //"shipping_price_supplement_vat_inc" => $productDataObjectResult->getPriceBrutto(),
                ];
            }

        }

        $headers = array("sku", "sku_manufacturer", "manufacturer", "ean", "title", "description", "product_price_vat_inc", "shipping_price_vat_inc", "quantity", "brand", "merchant_category", "product_url", "image_1", "image_2", "image_3", "image_4", "image_5", "retail_price_vat_inc", "manufacturer_pdf", "ParentSKU", "Cross_Sell_SKU", "ManufacturerWarrantyTime", "carrier", "shipping_time", "use_grid", "carrier_grid_1", "shipping_time_carrier_grid_1", "DisplayWeight", "free_return", "eco_participation", "shipping_price_supplement_vat_inc");
        $fileHandler = fopen("public/var/feeds/manomano-temp.csv", "w");
        fputcsv($fileHandler, $headers, "\t");
        foreach ($productsFeedmanomano as $row){
            fputcsv($fileHandler, $row, "\t");
        }
        fclose($fileHandler);

        copy("public/var/feeds/manomano-temp.csv", "public/var/feeds/manomano.csv");

    }

}
