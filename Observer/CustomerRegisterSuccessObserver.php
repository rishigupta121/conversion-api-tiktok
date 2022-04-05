<?php

namespace RG\Tiktok\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use RG\Tiktok\Logger\RGLogger;
use RG\Tiktok\Helper\Data;

class CustomerRegisterSuccessObserver implements ObserverInterface
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
     
       $this->helper->storedebug("Customer register Success");
       if (!$this->helper->isEnabled()) {
        return $this;
     }
        $data=[];
        $data = $this->helper->createData("CompleteRegistration",$data);
        $this->helper->storedebug(["request"=>$data]);
        $response = $this->helper->curlRequest(json_encode($data),'POST');
        $this->helper->storedebug($response);

        return $this;
    }
}
