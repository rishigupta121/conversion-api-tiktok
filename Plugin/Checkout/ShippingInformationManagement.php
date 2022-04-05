<?php
namespace RG\Tiktok\Plugin\Checkout\Model;
/**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */

use RG\Tiktok\Helper\Data;
class ShippingInformationManagement{

    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }
 

    public function afterSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $shipping,
         $result
    )
    {
        $this->helper->storedebug("After Save Address");
        if (!$this->helper->isEnabled()) {
         return $this;
      }
  
       $quote = $this->_checkoutSession->getQuote();
       $products =[];
       $totalPrice=0;
       foreach($quote->getAllVisibleItems() as $_item) {
           $product['price'] = $_item->getPrice();
           $product['quantity'] = $_item->getQty();
           $product['content_type'] = $_item->getName();
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
       $data = $this->helper->createData("Add Payment Info",$data);
       $this->helper->storedebug(["request"=>$data]);
       $response = $this->helper->curlRequest(json_encode($data),'POST');
       $this->helper->storedebug($response);
        return $result;
    }

}
