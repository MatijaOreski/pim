<?php

namespace App\Controller;

use PHPUnit\Exception;
use Pimcore\Controller\FrontendController;
use Pimcore\File;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\HeadProduct;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\ProductSeries;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class TestController extends FrontendController
{
    /**
     * @Template
     * @param Request $request
     * @Route ("/test",name="test")
     * @return array
     */
    public function testAction(Request $request)
    {


        /*$category_number = $request->request->get("category_number") ? $request->request->get("category_number") : "";
        var_dump($csv = $request->request->get("csv") ? $request->request->get("csv") : "");*/
        $csv = "Ovo je test";

        //return ["category_number" => $category_number, "csv" => $csv];
        return ["csv" => $csv];
    }


}
