<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use PharIo\Version\Exception;
use Pimcore\File;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\ExternalImage;
use function Symfony\Component\String\b;

class Test extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'test';

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
            $line = fgetcsv($handle, 20000, ";");
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

    private function removeAllAssets(){
        $assets = Asset::getList();
        /**
         * @var Asset $asset
         */
        foreach ($assets as $asset){
            if ($asset->getId() != 1){
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
        if (file_exists("public/var/unsorted/test.csv")){
        $counter = 9;
        $productCounter = 0;

            $urlSecond = 'https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles/242523?useNumberAsId=true';
            $option = array(CURLOPT_RETURNTRANSFER => true);
            $curl_handle_second = curl_init($urlSecond);
            curl_setopt_array($curl_handle_second, $option);
            $productContent = curl_exec($curl_handle_second);
            if(strlen($productContent) > 100){
                $productContentDecode = json_decode($productContent, true);
                $productData = $productContentDecode['data']['metaTitle'];
                $name = $productContentDecode['data']['name'];
                $ean = $productContentDecode['data']['mainDetail']['ean'];
                if(strlen($productData) > 84){
                    //if($ean == "0088381518420"){
                        $data_array = array(
                            'metaTitle' => "Makita Sägeblatt 165x1,45x20, 56Z EFFICUT"// Makita Sägeblatt 165x1,45x20, 56Z EFFICUT - B-57336
                        );

                        $dataMetaRes = json_encode($data_array);
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
                            //var_dump($decoded);
                            echo $name . " - " . $ean . "\n";
                        }
                    //}
                }
            }

        /*foreach ($csv as $cur){

            if($productCounter <= 1000){

                $url = 'https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles/'.$cur['mainnumber'].'?useNumberAsId=true';
                $data = file_get_contents($url);
                $decodedData = json_decode($data);

                $ean = $decodedData->data->mainDetail->ean;
                $productNumber = $decodedData->data->mainDetail->number;
                $manufacturer = $decodedData->data->supplier->name;

                $metaTitleRes = null;
                $metaTitleEsRes = null;
                $metaTitleFrRes = null;
                $metaTitleItRes = null;
                $metaTitleNlRes = null;
                $metaTitleHrRes = null;

                $metaTitle = (isset($decodedData->data->metaTitle)) ? $metaTitle = $decodedData->data->metaTitle : $metaTitle = null;
                $metaTitleEs = (isset($decodedData->data->translations->{"6"}->metaTitle)) ? $metaTitleEs = $decodedData->data->translations->{"6"}->metaTitle : $metaTitleEs = null;
                $metaTitleFr = (isset($decodedData->data->translations->{"4"}->metaTitle)) ? $metaTitleFr = $decodedData->data->translations->{"4"}->metaTitle : $metaTitleFr = null;
                $metaTitleIt = (isset($decodedData->data->translations->{"5"}->metaTitle)) ? $metaTitleIt = $decodedData->data->translations->{"5"}->metaTitle : $metaTitleIt = null;
                $metaTitleNl = (isset($decodedData->data->translations->{"7"}->metaTitle)) ? $metaTitleNl = $decodedData->data->translations->{"7"}->metaTitle : $metaTitleNl = null;
                $metaTitleHr = (isset($decodedData->data->translations->{"9"}->metaTitle)) ? $metaTitleHr = $decodedData->data->translations->{"9"}->metaTitle : $metaTitleHr = null;

                if($metaTitle != null){
                    $metaTitleRes = (strlen($metaTitle) > 84) ? $metaTitleRes = $metaTitle : $metaTitleRes = "OK";
                }else{
                    $metaTitleRes = "does not exist";
                }

                if($metaTitleEs != null){
                    $metaTitleEsRes = (strlen($metaTitleEs) > 84) ? $metaTitleEsRes = $metaTitleEs : $metaTitleEsRes = "OK";
                }else{
                    $metaTitleEsRes = "does not exist";
                }

                if($metaTitleFr != null){
                    $metaTitleFrRes = (strlen($metaTitleFr) > 84) ? $metaTitleFrRes = $metaTitleFr : $metaTitleFrRes = "OK";
                }else{
                    $metaTitleFrRes = "does not exist";
                }

                if($metaTitleIt != null){
                    $metaTitleItRes = (strlen($metaTitleIt) > 84) ? $metaTitleItRes = $metaTitleIt : $metaTitleItRes = "OK";
                }else{
                    $metaTitleItRes = "does not exist";
                }

                if($metaTitleNl != null){
                    $metaTitleNlRes = (strlen($metaTitleNl) > 84) ? $metaTitleNlRes = $metaTitleNl : $metaTitleNlRes = "OK";
                }else{
                    $metaTitleNlRes = "does not exist";
                }

                if($metaTitleHr != null){
                    $metaTitleHrRes = (strlen($metaTitleHr) > 84) ? $metaTitleHrRes = $metaTitleHr : $metaTitleHrRes = "OK";
                }else{
                    $metaTitleHrRes = "does not exist";
                }

                if(strlen($metaTitleRes) > 84 || strlen($metaTitleEsRes) > 84 || strlen($metaTitleFrRes) > 84 || strlen($metaTitleItRes) > 84 || strlen($metaTitleNlRes) > 84 || strlen($metaTitleHrRes) > 84){
                    echo $content = "(".$counter.",'".$ean."','".$manufacturer."','".$productNumber."','".$metaTitleRes."','".$metaTitleFrRes."','".$metaTitleEsRes."','".$metaTitleItRes."','".$metaTitleNlRes."','".$metaTitleHrRes."', NULL, NULL,1,NULL,0),\n";
                    //echo $content = '('.$counter.',"'.$ean.'","'.$manufacturer.'","'.$productNumber.'","'.$metaJson.'",0),' . "\n";
                    $files = fopen("meta_title.txt", "a");
                    fwrite($files, $content);
                    $counter++;
                }

            }
            $productCounter++;
        }*/

        }
    }




    /*public function testImport($path){
        $csv = $this->readCSV($path);
        if (file_exists("public/var/unsorted/productNumbers.csv")){

            $counter = 2498;
            $result = [];
            foreach ($csv as $cur){
                if($cur['mainnumber'] == "294433"){ // 294433
                    die;
                }
                $url = 'https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/articles/'.$cur['mainnumber'].'?useNumberAsId=true';
                $data = file_get_contents($url);
                $decodedData = json_decode($data);

                $img = $decodedData->data->images[0]->mediaId;
                $ean = $decodedData->data->mainDetail->ean;
                $name = $decodedData->data->name;
                $productNumber = $decodedData->data->mainDetail->number;
                $manufacturer = $decodedData->data->supplier->name;
                $active = $decodedData->data->active;

                $url_media = 'https://moreski:h1QDUh3JPsiXRt9hIBCpZ5hHeFHkNcTFfgkeB60f@www.mytoolstore.de/api/media/'.$img;
                $dataMedia = file_get_contents($url_media);
                $resMedia = json_decode($dataMedia);

                $imageWidth = $resMedia->data->width;
                $imageHeight = $resMedia->data->height;
                $imagePath = $resMedia->data->path;


                if($active == true){
                    if($imageWidth != $imageHeight || $imageWidth < 500 || $imageHeight < 500){
                        echo $content = "(".$counter.",'".$name."','".$ean."','".$manufacturer."','".$imageWidth."','".$imageHeight."','".$imagePath."',0),\n";
                        //echo "(".$counter.",'".$name."','".$ean."','".$manufacturer."','".$imageWidth."','".$imageHeight."','".$imagePath."',0),\n";
                        $files = fopen("images_false.txt", "a");
                        fwrite($files, $content);
                        $counter++;
                    }
                }

            }

        }

        $this->testImport("public/var/unsorted/productNumbers.csv");
    }*/

}
