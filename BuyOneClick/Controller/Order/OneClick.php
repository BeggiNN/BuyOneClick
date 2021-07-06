<?php

namespace Perspective\BuyOneClick\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Perspective\BuyOneClick\Helper\Data;

class OneClick extends Action
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerFactory;

    /**
     * @var Data $helper
     */
    protected $_helper;

    /**
     * Product constructor.
     * @param Data $helper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param Context $context
     */

    public function __construct(
        Data $helper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\RequestInterface $request,
        Context $context
    ) {
        $this->_helper = $helper;
        $this->_request = $request;
        $this->_customerFactory = $customerFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context);
    }

    public function getFullCustomerData($email, $name, $firstName, $id, $qty, $number, $type)
    {
        if ($type != 'configurable') {
            $order =  [
                'currency_id'  => 'USD',
                'email'        => $email,
                'address' =>[
                    'firstname'    => $firstName,
                    'lastname'     => $name,
                    'prefix' => '',
                    'suffix' => '',
                    'street' => 'B1 Abcd street',
                    'city' => 'Los Angeles',
                    'country_id' => 'US',
                    'region' => 'California',
                    'region_id' => '12',
                    'postcode' => '45454',
                    'telephone' => $number,
                    'fax' => $number,
                    'save_in_address_book' => 1
                ],
                'items'=>
                    [
                        ['product_id'=>$id,'qty'=>$qty],
                    ]
            ];
            return $order;
        } else {
            $arr = $this->_helper->getOptionsProduct();
            $size = $arr[1];
            $color = $arr[0];
            $order = [
                'currency_id' => 'USD',
                'email' => $email,
                'address' => [
                    'firstname' => $firstName,
                    'lastname' => $name,
                    'prefix' => '',
                    'suffix' => '',
                    'street' => 'B1 Abcd street',
                    'city' => 'Los Angeles',
                    'country_id' => 'US',
                    'region' => 'California',
                    'region_id' => '12',
                    'postcode' => '45454',
                    'telephone' => $number,
                    'fax' => $number,
                    'save_in_address_book' => 1
                ],
                'items' =>
                    [
                        ['product_id'=>$id, 'qty'=>$qty, 'super_attribute' => [93=>$size,144=>$color]]
                    ]
            ];
            return $order;
        }
    }

    public function execute()
    {
        $email = $this->_request->getParam('email');
        $name = $this->_request->getParam('name');
        $firstName = $this->_request->getParam('firstname');
        if (empty($name)) {
            $name = $firstName;
        } elseif (empty($firstName)) {
            $firstName = $name;
        }
        $number = $this->_request->getParam('number');
        $sku = $this->_request->getParam('sku');
        $product = $this->_productRepository->get($sku);
        $qty = $this->_request->getParam('qty');
        $id = $product->getId();
        $type = $product->getTypeId();
        if ($type != 'configurable') {
            $orderInfo = $this->getFullCustomerData($email, $name, $firstName, $id, $qty, $number, $type);
        } else {
            $size = $this->_request->getParam('array')[5]['value'];
            $color = $this->_request->getParam('array')[6]['value'];
            if (empty($color) && empty($size)) {
                $this->messageManager->addError("Виберіть будь ласка параметри товару!");
            } else {
                $orderInfo = $this->getFullCustomerData($email, $name, $firstName, $id, $qty, $number, $type);
            }
        }
        $order = $this->_helper->createOrder($orderInfo);
        if (!empty($order['success'])) {
            $idOrder = $order['success'];
            $this->messageManager->addSuccess("Вітаємо ви зробили замовлення! № вашого замовлення: $idOrder");
        } else {
            $this->messageManager->addError("Вибачте, на жаль не вдалося зробити замовлення!");
        }
    }
}
