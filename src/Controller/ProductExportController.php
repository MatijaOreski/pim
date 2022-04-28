<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject\Categories;
use Pimcore\Model\DataObject\ClassDefinition\Data\Language;
use Pimcore\Model\DataObject\Manufacturers;
use Pimcore\Model\DataObject\Product;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductExportController extends FrontendController
{
    /**
     * @Template
     * @param Request $request
     * @return array
     */
    public function productExportAction(Request $request)
    {

        $request->setLocale("de");

        $allLanguages = [
            "de","fr","it","en","nl","hr"
        ];
        $allCategories = Categories::getList()->load();
        $allManufacturers = Manufacturers::getList();
        $allManufacturers->addConditionParam("name = (?)","Bacho");

        $products = $allManufacturers->load();
        $productsArray = [];

        foreach ($products as $product){
            $productsArray[] = [
                "name" => $product->getName()
            ];
        }


        return [
            "allCategories"=>$allCategories,
            "allManufacturers"=>$allManufacturers,
            "allLanguages"=>$allLanguages,
            "name"=>$productsArray
        ];
    }


}
