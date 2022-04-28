<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

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

class ApiCroatia extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'mts:apiCroatia';

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
    private function importNewAssets()
    {
        //$csv = $this->readCSV($path);

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
        //$productsModificationArray = [];
        $productsNewCreatedArray = [];
        $productNumber = null;
        $productNumber1 = null;

        foreach ($productDataObjectArray as $data){
            $dataId = $data->getId();
            $ean = $data->getEan();



            foreach ($productModificationObjectArray as $productObjectArrayResult){
                if($productObjectArrayResult == $dataId){
                    $url = "https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles?filter[0][property]=mainDetail.ean&filter[0][value]=" . $ean;
                    $options = array(CURLOPT_RETURNTRANSFER => true);
                    $curl_handle = curl_init($url);
                    curl_setopt_array($curl_handle, $options);
                    $content = curl_exec($curl_handle);
                    if(strlen($content) > 100){
                        $decodedData = json_decode($content, true);
                        $jsonData = $decodedData['data'];
                        foreach ($jsonData as $mainDetail){
                            $productNumber = $mainDetail['mainDetail']['number'];
                        }
                    }
                    $urlSec = 'https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles/'.$productNumber.'?useNumberAsId=true';
                    $data_array = array(
                        "translations" => array(
                            "9" => array(
                                "metaTitle" => $data->getManufacturer()->getName() . " " . $data->getMetaTitle("hr"),
                                "name" => $data->getManufacturer()->getName() . " " . $data->getProductsName("hr"),
                                "descriptionLong" => trim($data->getDescription("hr")),
                                "shopId" => 9
                            )
                        )
                    );

                    $jsonDataRes = json_encode($data_array);
                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, $urlSec);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataRes);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $resp = curl_exec($ch);
                    if($e = curl_error($ch)){
                        echo $e;
                    }else{
                        $decoded = json_decode($resp);
                        if($decoded->success == false){
                            date_default_timezone_set("Europe/Zagreb");
                            $date = date("d-m-Y") . " - " . date("h:i:Sa");
                            $write = $date . " - " . $resp . "\n";
                            $files = fopen("error_file.txt", "a");
                            fwrite($files, $write);
                        }

                    }

                }
            }

            foreach ($productNewCreatedObjectArray as $productObjectArrayResultNew){
                if($productObjectArrayResultNew == $dataId){
                    $url1 = "https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles?filter[0][property]=mainDetail.ean&filter[0][value]=" . $ean;
                    $options1 = array(CURLOPT_RETURNTRANSFER => true);
                    $curl_handle1 = curl_init($url1);
                    curl_setopt_array($curl_handle1, $options1);
                    $content1 = curl_exec($curl_handle1);
                    if(strlen($content1) > 100){
                        $decodedData1 = json_decode($content1, true);
                        $jsonData1 = $decodedData1['data'];
                        foreach ($jsonData1 as $mainDetail1){
                            $productNumber1 = $mainDetail1['mainDetail']['number'];
                        }
                    }
                    $productsNewCreatedArray[] = [
                        "articleNumber" => (int)$productNumber1,
                        "languageId" => 9,
                        "productName" => $data->getManufacturer()->getName() . " " . $data->getProductsName("hr"),
                        "description" => trim($data->getDescription("hr")),
                        "metaTitle" => $data->getManufacturer()->getName() . " " . $data->getMetaTitle("hr")
                    ];
                }
            }

        }

    }

}
