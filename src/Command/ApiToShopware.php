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
use function Symfony\Component\String\b;

class ApiToShopware extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'apiToShopware';

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
        if (file_exists("public/var/unsorted/productNumbers.csv")){

            $productCounter = 0;
            //foreach ($csv as $cur){
                if($productCounter == 0){

                    /*$eanListObj = DataObject\Product::getList();
                    //$ean->addConditionParam("ean=(?) and or ",[ean]);
                    //$ean->addConditionParam("ean=(?) and or ",[ean]);
                    //$db = Db::get();
                    //$db->createQueryBuilder()->
                    $eanList = $eanListObj->load();
                    foreach ($eanList as $list){
                        if($list->getEan() == "3165140027892"){
                            $apiname = $list->getProductsName("hr");
                            $apidescription = $list->getDescription("hr");
                        }
                    }*/
                    $url = "https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles?filter[0][property]=mainDetail.ean&filter[0][value]=3165140027892";

                    $options = array(CURLOPT_RETURNTRANSFER => true);
                    $curl_handle = curl_init($url);
                    curl_setopt_array($curl_handle, $options);
                    $content = curl_exec($curl_handle);
                    if(strlen($content) > 100){
                        $decodedData = json_decode($content, true);
                        $data = $decodedData['data'];
                        foreach ($data as $mainDetail){
                            $productNumber = $mainDetail['mainDetail']['number'];
                            $urlSecond = 'https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles/'.$productNumber.'?useNumberAsId=true';
                            $option = array(CURLOPT_RETURNTRANSFER => true);
                            $curl_handle_second = curl_init($urlSecond);
                            curl_setopt_array($curl_handle_second, $option);
                            $productContent = curl_exec($curl_handle_second);
                            if(strlen($productContent) > 100){
                                $productContentDecode = json_decode($productContent, true);
                                $productData = $productContentDecode['data']['metaTitle'];
                                $name = $productContentDecode['data']['name'];
                                $ean = $productContentDecode['data']['mainDetail']['ean'];

                                $name_hr = $productContentDecode['data']['translations']['9']['name'];
                                $description_hr = $productContentDecode['data']['translations']['9']['descriptionLong'];

                                //if(strlen($productData) > 84){
                                    //if($ean == $cur['EAN']){
                                        /*$data_array = array(
                                            'translations' => array(
                                                '9' => array(
                                                    'metaTitle' => 'nekaj'//'Bosch Professional Svrdlo za bušaći čekić SDS-plus-5, 12 x 100 x 165 mm'
                                                )
                                            )
                                        );*/
                                        //Bosch Professional Hammerbohrer SDS-plus-5, 8 x 50 x 115 mm
                                        //'Bosch Professional Svrdlo za bušaći čekić SDS-plus-5, 12 x 100 x 165 mm'

                                        $data_array = array('metaTitle' => 'Bosch Professional Hammerbohrer SDS-plus-5, 12 x 100 x 165 mm - 1618596181');
                                        $dataMetaRes = json_encode($data_array);//Bosch Professional Hammerbohrer SDS-plus-5, 12 x 100 x 165 mm
                                        $ch = curl_init();

                                        curl_setopt($ch, CURLOPT_URL, $urlSecond);
                                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataMetaRes);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                                        $resp = curl_exec($ch);
                                        if($e = curl_error($ch)){
                                            echo $e;
                                        }else{
                                            $decoded = json_decode($resp);
                                            var_dump($decoded);
                                            //echo $dataMetaRes;
                                        }
                                    //}
                                //}
                            }

                        }
                    }
                }
                $productCounter++;
            //}

        }
    }

}
