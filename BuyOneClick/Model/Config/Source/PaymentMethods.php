<?php

namespace Perspective\BuyOneClick\Model\Config\Source;

class PaymentMethods implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'cashondelivery', 'label' => __('Cash on Delivery')],
            ['value' => 'banktransfer', 'label' => __('Bank Transfer Payment')],
            ['value' => 'checkmo', 'label' => __('Check/Money order')],

        ];
    }
}
