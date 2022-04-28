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

class IdealoAtFeed extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:idealoAtFeeds';

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
                    //"productId" => $productDataObjectResult->getProductsName(),
                    "name" => $productDataObjectResult->getProductsName(),
                    "brand" => $productDataObjectResult->getManufacturer()->getName(),
                    "offreid" => $productDataObjectResult->getMpn(),
                    "category" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    "description" => $productDataObjectResult->getDescription(),
                    "price" => $productDataObjectResult->getPriceNetto(),
                    //"image" => $productDataObjectResult->getProductsName(),
                    //"url" => $productDataObjectResult->getManufacturer()->getName(),
                    //"availability" => $productDataObjectResult->getCategories()[0]->getCategoryName(),
                    //"Versandkosten" => trim($productDataObjectResult->getDescription()),
                    //"Vorkasse" => $productDataObjectResult->getPriceBrutto(),
                    //"Versandkosten" => $productDataObjectResult->getWeightNetto(),
                    //"Kreditkarte" => $productDataObjectResult->getEan(),
                    //"Versandkosten" => $productDataObjectResult->getProductsName(),
                    //"PayPal" => $productDataObjectResult->getManufacturer()->getName(),
                    "weight" => $productDataObjectResult->getWeightNetto(),
                    "ean" => $productDataObjectResult->getEan()
                ];

            }

        }

        $headers = array("prodductId", "name", "brand", "offreid", "category", "description", "price", "image", "url", "availability", "Versandkosten", "Vorkasse", "Versandkosten", "Kreditkarte", "Versandkosten", "PayPal", "weight", "ean");
        $fh = fopen("public/var/feeds/idealo-at-temp.csv", "w");
        fputcsv($fh, $headers, "\t");
        foreach ($productsFeedArray as $row){
            fputcsv($fh, $row, "\t");
        }
        fclose($fh);

        copy("public/var/feeds/idealo-at-temp.csv", "public/var/feeds/idealo-at.csv");

    }

}
