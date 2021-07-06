<?php

namespace Perspective\BuyOneClick\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH = 'buy/';

    protected $customerSession;

    protected $groupRepository;

    protected $_storeManager;

    protected $_customerFactory;

    protected $_request;

    protected $_productRepository;

    protected $_customerRepository;

    protected $_orderFactory;

    protected $_checkoutSession;

    protected $_quote;

    protected $_quoteManagement;

    protected $_orderSender;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_productRepository = $productRepository;
        $this->_customerRepository = $customerRepository;
        $this->_quote = $quote;
        $this->_request = $request;
        $this->customerSession = $customerSession;
        $this->groupRepository = $groupRepository;
        $this->_orderFactory = $orderFactory;
        $this->_quoteManagement = $quoteManagement;
        $this->_orderSender = $orderSender;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . 'general/' . $code, $storeId);
    }

    public function getRealOrderId()
    {
        $lastorderId = $this->_checkoutSession->getLastOrderId();
        return $lastorderId;
    }

    public function getOrder()
    {
        if ($this->_checkoutSession->getLastRealOrderId()) {
            $order = $this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
            return $order;
        }
        return false;
    }

    public function getOrders()
    {
        $customerEmail = $this->_request->getParam('email');
        $order = $this->_orderFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_email', $customerEmail)->getLastItem();
        return $order;
    }

    /**
     * Create Order
     *
     * @param array $orderData
     * @return array
     *
     */
    public function createOrder($orderInfo)
    {
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderInfo['email']);
        if (!$customer->getId()) {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderInfo['address']['firstname'])
                ->setLastname($orderInfo['address']['lastname'])
                ->setEmail($orderInfo['email'])
                ->setPassword($orderInfo['email']);
            $customer->save();
        }
        $quote = $this->_quote->create();
        $quote->setStore($store);
        $customer = $this->_customerRepository->getById($customer->getId());
        $quote->setCurrency();
        $quote->assignCustomer($customer);
        foreach ($orderInfo['items'] as $item) {
            $product = $this->_productRepository->getById($item['product_id']);
            if (!empty($item['super_attribute'])) {
                $buyRequest = new \Magento\Framework\DataObject($item);
                $quote->addProduct($product, $buyRequest);
            } else {
                $quote->addProduct($product, $item['qty']);
            }
        }
        $lastOrder = $this->getOrders();
        if (!empty($lastOrder->getData())) {
            $shipingMethod = $lastOrder->getData('shipping_method');
            $paymentMethod = $lastOrder->getPayment()->getMethod();
        } else {
            $shipingMethod = $this->getGeneralConfig('shiping');
            $paymentMethod = $this->getGeneralConfig('payment');
        }
        $quote->getBillingAddress()->addData($orderInfo['address']);
        $quote->getShippingAddress()->addData($orderInfo['address']);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($shipingMethod);
        $quote->setPaymentMethod($paymentMethod);
        $quote->setInventoryProcessed(false);
        $quote->save();
        $quote->getPayment()->importData(['method' => $paymentMethod]);
        $quote->collectTotals()->save();
        $order = $this->_quoteManagement->submit($quote);
        $this->_orderSender->send($order);
        $orderId = $order->getIncrementId();
        if ($orderId) {
            $result['success'] = $orderId;
        } else {
            $result = ['error' => true, 'msg' => 'Error occurs for Order placed'];
        }
        return $result;
    }

    public function getOptionsProduct()
    {
        $size = $this->_request->getParam('array')[5]['value'];
        $color = $this->_request->getParam('array')[6]['value'];
        return [$size, $color];
    }
}
