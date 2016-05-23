<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paysera\Paysera\Model;

/**
 * Pay In Store payment method model
 */
class Paysera extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'paysera';
    protected $_title = 'Paysera';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
