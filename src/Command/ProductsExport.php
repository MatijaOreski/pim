<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use PharIo\Version\Exception;
use Pimcore\Db;
use Pimcore\File;
use Pimcore\Model\Asset;
use SimpleXMLElement;
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

class ProductsExport extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'productExport';

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'The CSV-File with import data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        //$this->removeAllAssets();
        $this->importNewAssets($path);

        $output->writeln("Success");
        return Command::SUCCESS;
    }

    /**
     * @param string $csvFile
     * @return array
     */
    private function readCSV(string $csvFile)
    {
        $lines = [];
        $handle = fopen($csvFile, 'r');
        $row = 1;
        $columnNames = [];
        while (!feof($handle)) {
            $line = fgetcsv($handle, 20000, ",");
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
        return $lines;
    }

    private function removeAllAssets()
    {
        $assets = Asset::getList();
        /**
         * @var Asset $asset
         */
        foreach ($assets as $asset) {
            if ($asset->getId() != 1) {
                $asset->delete();
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function importNewAssets($path)
    {
        $csv = $this->readCSV($path);

        $object = DataObject::getList();
        $objects = $object->load();
        $now = date("Y-m-d");
        $productModificationObjectArray = [];
        $productNewCreatedObjectArray = [];

        foreach ($objects as $obj){
            $modificationDate = $obj->getModificationDate();
            $convertModificationDate = date("Y-m-d H:i:s", $modificationDate);
            $newModificationDate = explode(" ", $convertModificationDate);

            $newCreatedDate = $obj->getCreationDate();
            $convertNewCreatedDate = date("Y-m-d H:i:s", $newCreatedDate);
            $newCreatedNewDate = explode(" ", $convertNewCreatedDate);

            if($newModificationDate[0] == $now && $newCreatedNewDate[0] != $now){
                $productModificationObjectArray[] = $obj->getId();
            }

            if($newCreatedNewDate[0] == $now){
                $productNewCreatedObjectArray[] = $obj->getId();
            }
        }

        $productDataObject = DataObject\Product::getList();
        $productDataObjectArray = $productDataObject->load();
        $productsModificationArray = [];
        $productsNewCreatedArray = [];
        foreach ($productDataObjectArray as $data){
            $dataId = $data->getId();

            foreach ($productModificationObjectArray as $productObjectArrayResult){
                if($productObjectArrayResult == $dataId){
                    $productsModificationArray[] = [
                        "ProductName" => $data->getProductsName(),
                        "ProductDescription" => $data->getDescription(),
                        "EAN" => $data->getEan(),
                        "Manufacturer" => $data->getManufacturer()->getName(),
                        "MPN" => $data->getMpn(),
                        "ShopwareId" => $data->getShopwareId(),
                        "PriceBrutto" => $data->getPriceBrutto(),
                        "PriceNetto" => $data->getPriceNetto(),
                        "Category" => $data->getCategories()[0]->getCategoryName()
                    ];
                }
            }

            foreach ($productNewCreatedObjectArray as $productObjectArrayResultNew){
                if($productObjectArrayResultNew == $dataId){
                    $productsNewCreatedArray[] = [
                        "ProductName" => $data->getProductsName(),
                        "ProductDescription" => $data->getDescription(),
                        "EAN" => $data->getEan(),
                        "Manufacturer" => $data->getManufacturer()->getName(),
                        "MPN" => $data->getMpn(),
                        "ShopwareId" => $data->getShopwareId(),
                        "PriceBrutto" => $data->getPriceBrutto(),
                        "PriceNetto" => $data->getPriceNetto(),
                        "Category" => $data->getCategories()[0]->getCategoryName()
                    ];
                }
            }

        }

        $headers = array("ProductName", "ProductDescription", "EAN", "Manufacturer", "MPN", "ShopwareId", "PriceBrutto", "PriceNetto" ,"Category");
        $fh = fopen($now . "_modification_data.csv", "w");
        fputcsv($fh, $headers, ";");
        foreach ($productsModificationArray as $row){
            fputcsv($fh, $row, ";");
        }
        fclose($fh);

        $fileHandler = fopen($now . "_new_created_data.csv", "w");
        fputcsv($fileHandler, $headers, ";");
        foreach ($productsNewCreatedArray as $rows){
            fputcsv($fileHandler, $rows, ";");
        }
        fclose($fileHandler);

    }

}
