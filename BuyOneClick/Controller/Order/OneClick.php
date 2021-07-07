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
    protected $_customer;

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
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Framework\App\RequestInterface $request,
        Context $context
    ) {
        $this->_helper = $helper;
        $this->_customer = $customer;
        $this->_request = $request;
        $this->_customerFactory = $customerFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context);
    }

    public function getFullCustomerData($email, $name, $lastName, $id, $qty, $number, $type)
    {
        if ($type != 'configurable') {
            $order =  [
                'currency_id'  => 'USD',
                'email'        => $email,
                'address' =>[
                    'firstname'    => $lastName,
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
                    'firstname' => $lastName,
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
        $customer = $this->_customer->create();
        $customer->setWebsiteId('1');
        $customer->loadByEmail($email);
        if (empty($this->_request->getParam('name'))) {
            if (empty($customer->getFirstname())) {
                $name = 'Customer';
                $lastName = $name;
            } else {
                $name = $customer->getFirstname();
                $lastName = $customer->getLastname();
            }
        } else {
            $name = $this->_request->getParam('name');
            $lastName = $this->_request->getParam('firstname');
        }
        if (empty($name)) {
            $name = $lastName;
        } elseif (empty($lastName)) {
            if (empty($customer->getLastname())) {
                $lastName = $name;
            } else {
                $lastName = $customer->getLastname();
            }
        }
        if (empty($this->_request->getParam('number'))) {
            if (!empty($customer->getDefaultShippingAddress())) {
                $number = $customer->getDefaultShippingAddress()->getTelephone();
            } else {
                $number = '+380999999999';
            }
        } else {
            $number = $this->_request->getParam('number');
        }
        if (!empty($customer->getFirstname()) && $name != $customer->getFirstname()) {
                $this->messageManager->addError("Вибачте, але ви ввели не свою пошту!");
            } else {
            $sku = $this->_request->getParam('sku');
            $product = $this->_productRepository->get($sku);
            $qty = $this->_request->getParam('qty');
            $id = $product->getId();
            $type = $product->getTypeId();
            if ($type != 'configurable') {
                $orderInfo = $this->getFullCustomerData($email, $name, $lastName, $id, $qty, $number, $type);
            } else {
                $size = $this->_request->getParam('array')[5]['value'];
                $color = $this->_request->getParam('array')[6]['value'];
                if (empty($color) && empty($size)) {
                    $this->messageManager->addError("Виберіть будь ласка параметри товару!");
                } else {
                    $orderInfo = $this->getFullCustomerData($email, $name, $lastName, $id, $qty, $number, $type);
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
}
