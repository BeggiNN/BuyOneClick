<?php

namespace Perspective\BuyOneClick\Model\Config\Source;

class ShipingMethods implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $ret = [
            ['value' => 'freeshipping_freeshipping', 'label' => __('Free Shipping')],
            ['value' => 'flatrate_flatrate', 'label' => __('Flat rate')],
            ['value' => 'tablerate_bestway', 'label' => __('Best Way')],
        ];
        return $ret;
    }
}

