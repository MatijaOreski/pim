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

class ProcatoFeed extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:procatoFeeds';

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
        $productsFeedArrayPrice = [];
        $productsFeedArrayStock = [];

        foreach ($productDataObjectArray as $productDataObjectResult){
            if($productDataObjectResult->getPublished() == true){
                $productsFeedArrayPrice[] = [
                    "manufacturer" => $productDataObjectResult->getManufacturer()->getName(),
                    "manufacturer_pid" => $productDataObjectResult->getEan(),
                    "supplier_pid" => $productDataObjectResult->getEan(),
                    //"order_unit" => $productDataObjectResult->getMpn(),
                    //"price" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    //"price_base" => $productDataObjectResult->getDescription(),
                    "currency" => "EUR",
                    //"tax_class" => $productDataObjectResult->getProductsName(),
                    //"saleable" => $productDataObjectResult->getManufacturer()->getName()
                ];
                $productsFeedArrayStock[] = [
                    "manufacturer" => $productDataObjectResult->getManufacturer()->getName(),
                    "manufacturer_pid" => $productDataObjectResult->getEan(),
                    "supplier_pid" => $productDataObjectResult->getEan(),
                    //"order_unit" => $productDataObjectResult->getMpn(),
                    //"quantity" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    //"replenishment_time" => $productDataObjectResult->getDescription(),
                    //"deeplink" => "EUR"
                ];

            }

        }

        $headers = array("manufacturer", "manufacturer_pid", "supplier_pid", "order_unit", "price", "price_base", "currency", "tax_class", "saleable");
        $fh = fopen("public/var/feeds/procato_price-temp.csv", "w");
        fputcsv($fh, $headers, ";");
        foreach ($productsFeedArrayPrice as $row){
            fputcsv($fh, $row, ";");
        }
        fclose($fh);

        $headersStock = array("manufacturer", "manufacturer_pid", "supplier_pid", "order_unit", "quantity", "replenishment_time", "deeplink");
        $fileHandler = fopen("public/var/feeds/procato_stock-temp.csv", "w");
        fputcsv($fileHandler, $headersStock, ";");
        foreach ($productsFeedArrayStock as $res){
            fputcsv($fileHandler, $res, ";");
        }
        fclose($fileHandler);

        copy("public/var/feeds/procato_price-temp.csv", "public/var/feeds/procato_price.csv");
        copy("public/var/feeds/procato_stock-temp.csv", "public/var/feeds/procato_stock.csv");

    }

}
