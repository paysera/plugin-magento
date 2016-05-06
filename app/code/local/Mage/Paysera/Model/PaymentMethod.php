<?php

class Mage_Paysera_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract {
    
    
    protected $_isGateway = true;  
    protected $_canAuthorize = true;  
    
    protected $_code = 'paysera';
    protected $_formBlockType = 'paysera/form';

    //tomas
    public function assignData($data)
    {  
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('mok_bud', $data->getMokBudas());
     
        return $this;
    }
 
 
    public function validate()
    {
        parent::validate();
 
        $info = $this->getInfoInstance();
 
        $mok = $info->getMokBudas();

        return $this;
    }

    //end tomas

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('paysera/pay/redirect');
    }

    public function getRequest() {

        require_once(Mage::getBaseDir() . '/app/code/local/Mage/Paysera/libwebtopay/WebToPay.php');

        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        $pData    = Mage::getStoreConfig('payment/paysera');
        $language = substr(Mage::app()->getLocale()->getLocaleCode(), 3, 5);

      //print_r($session);
        //$address = $this->getCustomerAddressInfo($order);
  $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
if ($customerAddressId){
       $address_tmp = Mage::getModel('customer/address')->load($customerAddressId);
       $address = $address_tmp->getData();
} 
        
        
$payment = $order->getPayment();
$mok_bud = $payment->getAdditionalInformation('mok_bud');
        
        
        
         echo Mage::getSingleton('checkout/session')->getQuote()->getPayment(); 
         
         $lng = array('LT'=>'LIT', 'LV'=>'LAV', 'EE'=>'EST', 'RU'=>'RUS', 'DE'=>'GER', 'PL'=>'POL');
         
        try {
            $request = WebToPay::buildRequest(array(
                'projectid'     => $pData['api_key'],
                'sign_password' => $pData['api_secret'],

                'orderid'       => $order->increment_id,
                'amount'        => intval(number_format(($order->grand_total * 100), 0, '', '')),
                'currency'      => $order->order_currency_code,
                'lang'          => ($lng[$language] ? $lng[$language] : 'ENG'),

                'accepturl'     => Mage::getUrl('paysera/pay/success'),
                'cancelurl'     => Mage::getUrl('paysera/pay/cancel'),
                'callbackurl'   => Mage::getUrl('paysera/pay/callback'),
                'payment'       => "$mok_bud",
                'country'       => $address['country_id'],

                'p_firstname'   => $order->customer_firstname,
                'p_lastname'    => $order->customer_lastname,
                'p_email'       => $order->customer_email,
                'p_street'      => $address['Street'],
                'p_city'        => $address['City'],
                'p_state'       => '',
                'p_zip'         => $address['Postcode'],

                'test'          => $pData['test'],
            ));
        } catch (WebToPayException $e) {
            echo get_class($e) . ': ' . $e->getMessage();
        } 
        return $request;
    }

    public function getPayUrl() {
        require_once(Mage::getBaseDir() . '/app/code/local/Mage/Paysera/libwebtopay/WebToPay.php');
        return WebToPay::PAY_URL;
    }

    public function validateCallback() {
        include_once(Mage::getBaseDir() . '/app/code/local/Mage/Paysera/libwebtopay/WebToPay.php');
        
       $pData = Mage::getStoreConfig('payment/paysera');   
        
        $response = WebToPay::checkResponse($_REQUEST, array(
        'projectid'     => $pData['api_key'],
        'sign_password' => $pData['api_secret'],
    ));
            
    
     
        if ($response['status'] == 1) {


           
             $order = Mage::getModel('sales/order')->loadByIncrementId($response['orderid']);

            $response_amount = intval(number_format($response['amount'], 0, '', ''));
            $system_amount   = intval(number_format(($order->grand_total * 100), 0, '', ''));

            if ($response_amount < $system_amount) {
                return 'Bad amount: ' . $response_amount . ' Should be: ' . $system_amount;
            }

            if ($response['currency'] != $order->order_currency_code) {
                return 'Bad currency: ' . $response['currency'];
            }

            if ($order->increment_id) {
                try {
               
                    $order->sendNewOrderEmail();
                   

                    $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING)->save();
                    exit('OK');
                    
                    
                    

                } catch (Exception $e) {
                    return get_class($e) . ': ' . $e->getMessage();
                }
            } else {
                return 'No such order!';
            }
        }
    }

    public function getCustomerAddressInfo($orderObject) {
        $addressInfo = array(
            'City'     => '',
            'Street'   => '',
            'Postcode' => '',
        );

        if (!$orderObject->customer_is_guest) {
            $address = $orderObject->getBillingAddress();

            $addressInfo['City']     = $address->city;
            $addressInfo['Street']   = $address->street;
            $addressInfo['Postcode'] = $address->postcode;
        }

        return $addressInfo;
    }

    public function d($var) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }
}
