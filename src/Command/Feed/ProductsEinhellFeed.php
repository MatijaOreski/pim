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

class ProductsEinhellFeed extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:einhellFeeds';

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
                    "Einhell Artikelnummer" => $productDataObjectResult->getMpn(),
                    "EAN" => $productDataObjectResult->getEan(),
                    //"Ziel-URL" => $productDataObjectResult->getManufacturer()->getName(),
                    //"Customer-ID" => $productDataObjectResult->getMpn() -------- product id
                ];

            }

        }

        $headers = array("Einhell Artikelnummer", "EAN", "Ziel-URL", "Customer-ID");
        $fh = fopen("public/var/feeds/products_einhell-temp.csv", "w");
        fputcsv($fh, $headers, ";");
        foreach ($productsFeedArray as $row){
            fputcsv($fh, $row, ";");
        }
        fclose($fh);

        copy("public/var/feeds/products_einhell-temp.csv", "public/var/feeds/products_einhell.csv");

    }

}
