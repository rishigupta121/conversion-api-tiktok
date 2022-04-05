<?php
namespace RG\Tiktok\Observer;

use Magento\Framework\Event\ObserverInterface;
use RG\Tiktok\Logger\RGLogger;
use RG\Tiktok\Helper\Data;

class SalesQuoteRemoveItemObserver implements ObserverInterface
{
    /**
     * @var \RG\Tiktok\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;


    /**
     * @param \RG\Tiktok\Helper\Data $helper
     * @param \Magento\Catalog\Model\ProductRepository $productRepository,
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     */
    public function __construct(
        Data $helper,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Checkout\Model\Session $_checkoutSession,
        RGLogger $logger
        )
    {
        $this->helper = $helper;
        $this->_checkoutSession = $_checkoutSession;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }
    
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
       
        if (!$this->helper->isEnabled()) {
            return $this;
        }
        $quoteItem = $observer->getData('quote_item');
        $productId = $quoteItem->getData('product_id');

        if (!$productId) {
            return $this;
        }

        $product = $this->productRepository->getById($productId);
        $qty = $quoteItem->getData('qty');

        $data['properties'] = array(
            "contents" => array(
                "price"=> $product->getPrice(),
                "quantity"=> $qty,
                "content_type"=> 'product',
                "content_name"=>$product->getName(),
                "content_id"=> $product->getId(),
                "description" => stripslashes($product->getDescription())
            ),
            "currency"=> $this->helper->getCurrentStoreCurrency(),
            "value"=> $product->getPrice()
    );
    $data = $this->helper->createData("RemoveFromCart",$data);
    $response = $this->helper->curlRequest(json_encode($data),'POST');
    $this->helper->storedebug($response);
        return $this;
    }
}