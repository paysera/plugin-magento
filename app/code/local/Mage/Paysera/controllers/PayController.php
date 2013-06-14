<?php

class Mage_Paysera_PayController extends Mage_Core_Controller_Front_Action
{

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function redirectAction()
    {
		$session = Mage::getSingleton('checkout/session');
		$session->setPayQuoteId($session->getQuoteId());
		$session->unsQuoteId();
        $this->getResponse()->setBody($this->getLayout()->createBlock('paysera/redirect')->toHtml());
    }

    public function callbackAction()
    {
    	$this->getResponse()->setBody($this->getLayout()->createBlock('paysera/callback')->toHtml());
	}

    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getPayQuoteId(true));
        $this->_redirect('checkout/cart');
	}

    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session -> setQuoteId($session -> getPayQuoteId(true));
        Mage::getSingleton('checkout/session') -> getQuote() -> setIsActive(false) -> save();
        $this -> _redirect('checkout/onepage/success', array('_secure' => true));
    }

    public function failAction()
    {
        $order = Mage::getModel('sales/order');
        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        if($order->getId())
        {
            $order->setStatus('canceled')->save();
        }

        $this->_redirect('checkout/onepage/failure');
    }
}
?>
