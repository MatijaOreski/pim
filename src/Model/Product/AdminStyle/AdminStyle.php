<?php

namespace App\Model\Product\AdminStyle;

use Pimcore\Model\Asset;
use Pimcore\Model\Element\AdminStyle as AdminStyleAlias;

class AdminStyle extends AdminStyleAlias
{
    public function __construct($element)

    {
        parent::__construct($element);

        if ($element instanceof Asset\Image) {

                $this->elementIconClass = null;
                $this->elementIcon = '/bundles/pimcoreadmin/img/flat-color-icons/asset.svg';

        }
    }
}
