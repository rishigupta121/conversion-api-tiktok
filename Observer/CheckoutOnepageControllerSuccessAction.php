<?php

namespace RG\Tiktok\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use RG\Tiktok\Logger\RGLogger;
use RG\Tiktok\Helper\Data;

class CheckoutOnepageControllerSuccessAction implements ObserverInterface
{

    public function __construct(
        RGLogger $logger,
        Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Sales\Api\Data\OrderInterface $orderinterface
     ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->order = $orderinterface;
        $this->_objectManager = $objectmanager;
        
     }
    public function execute(Observer $observer)
    {
       $this->helper->storedebug("helloonepage Success controller");
       if (!$this->helper->isEnabled()) {
        return $this;
     }
     $orderId = $observer->getEvent()->getOrderIds();
     $order = $this->order->load($orderId);
     $customerId = $order->getCustomerId();
     $customerData = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
        $products =[];
        $totalPrice=0;
        foreach($order->getItemsCollection() as $_item) {
            $this->helper->storedebug($_item);
            $product_get = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($_item->getProductId());
            $product['price'] = $_item->getPrice();
            $product['quantity'] = (int)$_item->getQtyOrdered();
            $product['content_type'] = 'product';
            $product['content_name'] = $_item->getName();
            $product['sku'] = $_item->getSku();
            $product['content_id'] = $_item->getProductId();
            $product['description'] = $product_get->getDescription();
            array_push($products,$product);
            $totalPrice += ($_item->getPrice()*$_item->getQtyOrdered())-$_item->getDiscountAmount();
            
        }
        $user['phone_number']=hash('sha256', $customerData ->getTelephone());
        $user['external_id']=hash('sha256',"123456_".$customerId);
        $user['email']=hash('sha256', $customerData->getEmail());
        $data['properties'] = array(
                "contents" => $products,
                "currency"=> $this->helper->getCurrentStoreCurrency(),
                "value"=> $totalPrice
        );
        $data = $this->helper->createData("CompletePayment",$data,$user);
        $this->helper->storedebug(["request"=>$data]);
        $response = $this->helper->curlRequest(json_encode($data),'POST');
        $this->helper->storedebug($response);

        return $this;
    }
}
