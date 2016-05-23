<?php
namespace Paysera\Paysera\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->pageFactory  = $pageFactory;
        $this->scopeConfig  = $scopeConfig;
        $this->storeManager = $storeManager;
        return parent::__construct($context);
    }

    public function execute()
    {
        $env = (include './app/etc/env.php');
        $db = $env['db']['connection']['default'];

        $con = mysqli_connect($db['host'], $db['username'], $db['password'], $db['dbname']);
        if (!$con) {
            die("Database connection failed: " . mysqli_connect_error());
        }

        $id = mysqli_query($con, "SELECT MAX(entity_id) AS MAX_ID FROM sales_order");
        $id = mysqli_fetch_assoc($id);
        $id = $id['MAX_ID'];

        $order = mysqli_query($con, 'SELECT * FROM sales_order WHERE entity_id='.$id);
        $order = mysqli_fetch_assoc($order);

        require_once('WebToPay.php');

        $paysera_config = $this->scopeConfig->getValue('payment/paysera', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $request = \WebToPay::buildRequest(array(
            'projectid'     => $paysera_config['projectid'],
            'amount'        => $order['grand_total']*100,
            'orderid'       => $order['entity_id'],
            'callbackurl'   => $this->storeManager->getStore()->getBaseUrl().'paysera/index/callback',
            'currency'      => $order['order_currency_code'],
            'country'       => 'LT',
            'p_email'       => $order['customer_email'],
            'sign_password' => $paysera_config['sign_password'],
            'accepturl'    => $this->storeManager->getStore()->getBaseUrl().'checkout/onepage/success',
            'cancelurl'    => $this->storeManager->getStore()->getBaseUrl(),
            'test'          => $paysera_config['test'],
        ));

        echo json_encode(array("url" => \WebToPay::PAYSERA_PAY_URL.'?data='.$request['data'].'&sign='.$request['sign']));

    }
}