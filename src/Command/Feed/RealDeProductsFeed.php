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

class RealDeProductsFeed extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:realDeProductsFeeds';

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
                    "ean" => $productDataObjectResult->getEan(),
                    //"condition" => $productDataObjectResult->getProductsName(),
                    "price" => $productDataObjectResult->getPriceNetto(),
                    //"comment" => $productDataObjectResult->getMpn(),
                    "offer_id" => $productDataObjectResult->getEan(),
                    "location" => "DE",
                    //"count" => to do,
                    //"delivery_time_min" => $productDataObjectResult->getPriceNetto(),
                    //"delivery_time_max" => $productDataObjectResult->getProductsName(),
                    //"minimum_price" => $productDataObjectResult->getManufacturer()->getName(),
                    //"price_es" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    //"minimum_price_es" => $productDataObjectResult->getDescription(),
                ];

            }

        }

        $headers = array("ean", "condition", "price", "comment", "offer_id", "location", "count", "delivery_time_min", "delivery_time_max", "minimum_price", "price_es", "minimum_price_es");
        $fh = fopen("public/var/feeds/real-de_products-temp.csv", "w");
        fputcsv($fh, $headers, "\t");
        foreach ($productsFeedArray as $row){
            fputcsv($fh, $row, "\t");
        }
        fclose($fh);

        copy("public/var/feeds/real-de_products-temp.csv", "public/var/feeds/real-de_products.csv");

    }

}
