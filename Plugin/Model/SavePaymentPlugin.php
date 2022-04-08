<?php 

namespace RG\Tiktok\Plugin\Model;
 
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Quote\Api\CartManagementInterface;
use RG\Tiktok\Helper\Data;
use RG\Tiktok\Logger\RGLogger;
 
class SavePaymentPlugin
{

    public function __construct(
        \RG\Tiktok\Helper\Data $helper,
        RGLogger $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepoInterface,
        \Magento\Checkout\Model\Session $checkoutSession
        ){
        $this->logger = $logger;
        $this->helper = $helper;
        $this->productRepository = $productRepoInterface;
        $this->_checkoutSession = $checkoutSession;
     }



    public function afterSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        $result
        )
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        $orderId = $result;

        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order->getId()) {
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (\Exception $ex) {
                return $result;
            }
        }
        $paymentMethodTitle ="";
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        if ($additionalInformation && isset($additionalInformation['method_title'])) {
            $paymentMethodTitle = $additionalInformation['method_title'];
        }
        $products =[];
        $totalPrice=0;
        foreach($order->getAllItems() as $_item) {
            $product_repo =   $this->productRepository->getById($_item->getProductId());
            $product['price'] = $_item->getPrice();
            $product['quantity'] = $_item->getQtyOrdered();
            $product['content_type'] = 'product';
            $product['content_name'] = $_item->getName();
            $product['sku'] = $_item->getSku();
            $product['content_id'] = $_item->getProductId();
            $product['description'] = stripslashes($product_repo->getDescription());
            array_push($products,$product);
            $totalPrice += ($_item->getPrice()*$_item->getQtyOrdered())-$_item->getDiscountAmount();
            
        }
           
            $data['properties'] = array(
            "contents" => $products,
            "currency"=> $this->helper->getCurrentStoreCurrency(),
            "value"=> $order->getGrandTotal(),
        );
        $user = [];
         $user['phone_number']=hash('sha256', $order->getShippingAddress()->getTelephone());
        $user['email']=hash('sha256', $order->getCustomerEmail());
        $user['external_id']=hash('sha256',"123456_"."guest");
        $data = $this->helper->createData("AddPaymentInfo",$data,$user);
        
        $data_place = $this->helper->createData("PlaceAnOrder",$data,$user);
        $this->helper->storedebug(["request"=>$data]);
        if(!empty($data)){
        $response = $this->helper->curlRequest(json_encode($data),'POST');
        $this->helper->storedebug($response);
        $response = $this->helper->curlRequest(json_encode($data_place),'POST');
        $this->helper->storedebug($response);
        }
           return $result;
    }
}