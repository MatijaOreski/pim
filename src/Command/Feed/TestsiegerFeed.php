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

class TestsiegerFeed extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:testsiegerFeeds';

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
        $productsFeedArray = [];

        foreach ($productDataObjectArray as $productDataObjectResult){
            if($productDataObjectResult->getPublished() == true){
                $productsFeedArray[] = [
                    "id" => $productDataObjectResult->getEan(),
                    "manufacturer" => $productDataObjectResult->getManufacturer()->getName(),
                    "mpnr" => $productDataObjectResult->getMpn(),
                    "ean" => $productDataObjectResult->getEan(),
                    "name" => $productDataObjectResult->getProductsName(),
                    "description" => $productDataObjectResult->getDescription(),
                    "category_path" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    "price" => $productDataObjectResult->getPriceNetto(),
                    //"price_per_unit" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    //"link" => trim($productDataObjectResult->getDescription()),
                    //"image_url" => $productDataObjectResult->getPriceBrutto(),
                    //"delivery_time" => $productDataObjectResult->getWeightNetto(),
                    //"delivery_costs" => $productDataObjectResult->getEan(),
                    //"pnz" => $productDataObjectResult->getProductsName(),
                    //"stock" => $productDataObjectResult->getManufacturer()->getName(),
                    "weight" => $productDataObjectResult->getWeightNetto(),
                    //"fulfillmentType" => $productDataObjectResult->getWeightNetto()
                ];

            }

        }

        $headers = array("id", "manufacturer", "mpnr", "ean", "name", "description", "category_path", "price", "price_per_unit", "link", "image_url", "delivery_time", "delivery_costs", "pnz", "stock", "weight", "fulfillmentType");
        $fh = fopen("public/var/feeds/testsieger-temp.csv", "w");
        fputcsv($fh, $headers, "\t");
        foreach ($productsFeedArray as $row){
            fputcsv($fh, $row, "\t");
        }
        fclose($fh);

        copy("public/var/feeds/testsieger-temp.csv", "public/var/feeds/testsieger.csv");

    }

}
