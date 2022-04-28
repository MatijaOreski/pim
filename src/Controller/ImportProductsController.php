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
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ImportProductsController extends FrontendController
{
    /**
     * @Template
     * @param Request $request
     * @Route ("/importproducts",name="importproducts")
     * @return array
     */
    public function productsAction(Request $request)
    {



        $ref = new ReflectionClass(new Product());
        $ownProps = array_filter($ref->getProperties(), function($property) {
            return $property->class == 'DerivedClass';
        });

        print_r($ownProps);


        //$category_number = $request->request->get("category_number") ? $request->request->get("category_number") : "";
        $csv = $request->request->get("csv") ? $request->request->get("csv") : "";
        $test = "Ovo je test";

        return ["csv" => $csv];
    }


}
