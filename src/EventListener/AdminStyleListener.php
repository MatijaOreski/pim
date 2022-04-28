<?php

namespace App\EventListener;


use App\Model\Product\AdminStyle\AdminStyle as aStyle;
use Pimcore\Model\Asset;

use Pimcore\Model\Element\AdminStyle;

class AdminStyleListener
{
    public function onResolveElementAdminStyle(\Pimcore\Event\Admin\ElementAdminStyleEvent $event)
    {
        $element = $event->getElement();
        // decide which default styles you want to override
        if ($element instanceof Asset\Image) {
            $event->setAdminStyle(new aStyle($element));
        }
    }
}
