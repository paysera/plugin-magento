<?php
namespace Paysera\Paysera\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Callback extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->pageFactory = $pageFactory;
        $this->scopeConfig  = $scopeConfig;
        return parent::__construct($context);
    }

    public function execute()
    {
        require_once('WebToPay.php');

        $paysera_config = $this->scopeConfig->getValue('payment/paysera', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $callback = \WebToPay::checkResponse($_GET, $paysera_config);

        if($callback['status'] == 1){
            $env = (include './app/etc/env.php');
            $db = $env['db']['connection']['default'];

            $con = mysqli_connect($db['host'], $db['username'], $db['password'], $db['dbname']);
            if (!$con) {
                die("Database connection failed: " . mysqli_connect_error());
            }

            $i = 0;

            if (mysqli_query($con, "UPDATE sales_order SET state='pending' WHERE entity_id=".$callback['orderid'])) {
                $i++;
            } else {
                echo "Error updating record: " . mysqli_error($con);
            }

            if (mysqli_query($con, "UPDATE sales_order SET status='pending' WHERE entity_id=".$callback['orderid'])) {
                $i++;
            } else {
                echo "Error updating record: " . mysqli_error($con);
            }

            if (mysqli_query($con, "UPDATE sales_order_grid SET status='pending' WHERE entity_id=".$callback['orderid'])) {
                $i++;
            } else {
                echo "Error updating record: " . mysqli_error($con);
            }

            if($i == 3){
                echo 'OK';
            }else{
                echo 'NOT OK';
            }
        }else{
            echo 'NOT OK';
        }
    }
}