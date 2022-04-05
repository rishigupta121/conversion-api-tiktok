<?php

namespace RG\Tiktok\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use RG\Tiktok\Logger\RGLogger;
use RG\Tiktok\Helper\Data;

class CheckoutPredispatch implements ObserverInterface
{
    public function __construct(
        RGLogger $logger,
        Data $helper,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Checkout\Model\Session $checkoutSession
     ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->quote = $quote;
        $this->_checkoutSession = $checkoutSession;
     }
    public function execute(Observer $observer)
    {
       $this->helper->storedebug("checkout predispatch start");
       if (!$this->helper->isEnabled()) {
        return $this;
     }
        $quote = $this->_checkoutSession->getQuote();
        $products =[];
        $totalPrice=0;
        foreach($quote->getAllVisibleItems() as $_item) {
            $product['price'] = $_item->getPrice();
            $product['quantity'] = $_item->getQty();
            $product['content_type'] = 'product';
            $product['content_name'] = $_item->getName();
            $product['sku'] = $_item->getSku();
            $product['content_id'] = $_item->getProductId();
            $product['description'] = stripslashes($_item->getDescription());
            array_push($products,$product);
            $totalPrice += ($_item->getPrice()*$_item->getQty())-$_item->getDiscountAmount();
            
        }
        $data['properties'] = array(
                "contents" => $products,
                "currency"=> $this->helper->getCurrentStoreCurrency(),
                "value"=> $totalPrice
        );
        $data = $this->helper->createData("InitiateCheckout",$data);
        $this->helper->storedebug(["request"=>$data]);
        if(!empty($data)){
        $response = $this->helper->curlRequest(json_encode($data),'POST');
        $this->helper->storedebug($response);
        }

        return $this;
    }
}
