<?php

namespace RG\Tiktok\Plugin\Model;
use Magento\Quote\Api\CartManagementInterface;
use RG\Tiktok\Logger\RGLogger;
use RG\Tiktok\Helper\Data;

class GuestPaymentInformation
{

    protected $helper;
    private $cartManagement;
    private $logger;

 
    protected $_checkoutSession;


    protected $orderRepository;


    public function __construct(
        CartManagementInterface $cartManagement,
        Data $helper,
        RGLogger $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface)
    {
        $this->cartManagement = $cartManagement;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepositoryInterface;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @return int Order ID.
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        foreach($order->getAllItems() as $_item) {
           $product_get = $objectManager->create('Magento\Catalog\Model\Product')->load($_item->getProductId());
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
