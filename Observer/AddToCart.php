<?php

namespace RG\Tiktok\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use RG\Tiktok\Logger\RGLogger;
use RG\Tiktok\Helper\Data;

class AddToCart implements ObserverInterface
{
    public function __construct(
        RGLogger $logger,
        Data $helper
     ) {
        $this->logger = $logger;
        $this->helper = $helper;
     }
    public function execute(Observer $observer)
    {
       $this->helper->storedebug("hello observer add to cart");
       if (!$this->helper->isEnabled()) {
        return $this;
     }
        $product = $observer->getEvent()->getProduct();
        $data['properties'] = array(
                'contents' => [array(
                    'price'=> $product->getPrice(),
                    'quantity'=> 1,
                    'content_type'=> 'product',
                    'content_name'=>$product->getName(),
                    'content_id'=> $product->getId(),
                    'description' => stripslashes($product->getDescription())
                )],
                'currency'=> $this->helper->getCurrentStoreCurrency(),
                'value'=> $product->getPrice()
        );
        $data = $this->helper->createData("AddToCart",$data);
        $response = $this->helper->curlRequest(json_encode($data),'POST');
        $this->helper->storedebug($response);

        return $this;
    }
}
