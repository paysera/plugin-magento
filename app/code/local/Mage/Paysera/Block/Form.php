<?php

class Mage_Paysera_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
    	$this->setTemplate('paysera/form.phtml');
    	
        parent::_construct();
    }
}

?>