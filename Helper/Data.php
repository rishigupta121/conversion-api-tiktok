<?php

namespace RG\Tiktok\Helper;

use Magento\Store\Model\ScopeInterface;
use RG\Tiktok\Logger\RGLogger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $resourceConnection;

    protected $API = "";
    protected $ACCESS_TOKEN = "";
    const CONTENT_TYPE = "application/json";
    protected $PIXEL_CODE = "";
    protected $TEST_EVENT_CODE= "";
    protected $DEBUG=0;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Store\Model\StoreManagerInterface $storeInterface,
        RGLogger $logger
    ) {
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->_storeManager = $storeInterface;
        $this->logger = $logger;
        $this->_tiktokoptions = $this->scopeConfig->getValue('rg_tiktok_section', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->ACCESS_TOKEN=$this->_tiktokoptions['general']['access_token'];
        $this->API=$this->_tiktokoptions['general']['api'];
        $this->PIXEL_CODE=$this->_tiktokoptions['general']['pixelcode'];
        $this->TEST_EVENT_CODE=$this->_tiktokoptions['general']['test_event_code'];
        $this->DEBUG=$this->_tiktokoptions['general']['is_debug'];
    }

    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function curlRequest($data,$method)
    {
        $curl = curl_init();
        $this->storedebug($data);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->API,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "Access-Token: ".$this->ACCESS_TOKEN,
                "Content-Type: ".SELF::CONTENT_TYPE
            ),
        ));
        $response="";
        try {
            $response = curl_exec($curl);
        } catch (\Exception $e) {
            $this->logger->debug(json_encode($e));
        }
        
        curl_close($curl);
        $this->storedebug($response);
        return $response;
    }

    public function getCurrentStoreCurrency(){
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function createData($event,$data,$user=""){
        $request = array(
            'pixel_code' => $this->PIXEL_CODE,
            'event'=> $event,
            'event_id'=>bin2hex(random_bytes(8)).'_'.rand(1,500),
            'timestamp' => date('Y-m-d\TH:i:s', time()).'Z',
            'context' => array(
                'page'=>array(
                    'url'=>'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    'referrer'=>isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : "none",
                ),
                'user'=>(!empty($user))?$user:array(
                    'external_id'=>'',
                    'phone_number'=> '',
                    'email'=> ''
                ),
                'user_agent'=>isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"",
                'ip'=>$_SERVER['REMOTE_ADDR']
                
            ),
            'test_event_code' => $this->TEST_EVENT_CODE,
            'properties' => $data['properties']
        );

        return $request;

    }

    public function storedebug($response){
        if($this->DEBUG){
        $this->logger->debug(json_encode($response));
        }
    }
    

    public function getStoreUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function isEnabled(){
        return $this->_tiktokoptions['general']['enable'];
    }
}
