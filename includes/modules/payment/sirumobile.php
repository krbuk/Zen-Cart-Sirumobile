<?php
/**
 * www.sirumobile.com (Finland)
 * REQUIRES PHP version >= 8.0
 * @package payment
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Nida Verkkopalvelu (www.nida.fi) / krbuk 2024 Nov 7 Modified in v1.0.2
 */

/**
* Service group
Num. 	Countries 	FICORA title
1 		Finland 	Non-profit services
2 		Finland 	Online services
3 		Finland 	Entertainment services
4 		Finland 	Adult entertainment services

* Tax class
Num. 	Countries 	VAT %
0 		Finland 	0%
1 		Finland 	10%
2 		Finland 	14%
3 		Finland 	25.5%
*/

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Client as GuzzleHttpClient;
use Siru\Signature;
use Siru\API;
use Siru\Exception\ApiException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
// Mobile Detect
use Detection\MobileDetect;
// Sirumobile module use vendor
require DIR_FS_CATALOG .DIR_WS_CLASSES . 'vendors/sirumobile/autoload.php';

include_once DIR_FS_CATALOG .DIR_WS_CLASSES . 'Mobile_Detect.php';

  class sirumobile extends base { 

    /**
     * $_check is used to check the configuration key set up
     * @var int
     */
    protected $_check;
    /**
     * $code determines the internal 'code' name used to designate "this" payment module
     * @var string
     */
    public $code;
    /**
     * $description is a soft name for this payment method
     * @var string 
     */
    public $description;
    /**
     * $email_footer is the text to me placed in the footer of the email
     * @var string
     */
    public $email_footer;
    /**
     * $enabled determines whether this module shows or not... during checkout.
     * @var boolean
     */
    public $enabled;
    /**
     * $order_status is the order status to set after processing the payment
     * @var int
     */
    public $order_status;
    /**
     * $title is the displayed name for this order total method
     * @var string
     */
    public $title;
    /**
     * $sort_order is the order priority of this payment module when displayed
     * @var int
     */
    public $sort_order;
    /**
     * The Your merchant id provided by Siru Mobile
     * @var int
     */
    protected $merchantId;
    /**
     * Your merchant secret provided by Siru Mobile
     * @var string
     */
    protected $merchantSecret;
    /**
     * 
     */
    public $return_address;
    /**
     * 
     */
    public $cancel_address;	  
    /**
     * sirumolibe module version
     */
    public $moduleVersion = '1.0.2';
    /**
     * $total_amount is the total cost of the order
     * @var float
     */
    public $amount;	  
    /**
     * $max_amount for the order
     *
     */
	public $max_amount;
    /**
     * @var array
     */
    public $countries = 'FI';
    /**
     * Locale
     */	  
	public $customerlocale = 'fi_FI';
     /**
     *  
     * 
     */		  
	public $country;
     /**
     *  
     * 
     */		  
	//public $variant = 'variant2';
	 public $variant;
     /**
     *  
     * 
     */		  
	public $signature;
     /**
     *  
     * 
     */		  
	public $customerreference;  
    /**
     * $allowed_currencies is the valid Sirumobile currency to use default EUR
     * @var string
     */
    private $allowed_currencies = array('EUR'); 	  
    /**
     * Platform name for the API.
     * @var string
     */
    protected $platformName = 'zencart-2.1.0';
    /**
    * $form_action_url is the URL to process the payment or not set for local processing
    * @var string
    */  
	public $form_action_url ;
	public $paymentvalid_url;
	public $ordernumber;
	public $servicegroup;
	public $taxclass;  

// class constructor
    function __construct() {
      global $order, $db;
      $this->code = 'sirumobile';
      $this->title = MODULE_PAYMENT_SIRUMOBILE_TEXT_TITLE;
      $this->description = '<strong>SiruMobile -v' . $this->moduleVersion . '</strong><br><br>' .MODULE_PAYMENT_SIRUMOBILE_TEXT_DESCRIPTION;
      $this->sort_order = defined('MODULE_PAYMENT_SIRUMOBILE_SORT_ORDER') ? MODULE_PAYMENT_SIRUMOBILE_SORT_ORDER : null;
      $this->enabled = (defined('MODULE_PAYMENT_SIRUMOBILE_STATUS') && MODULE_PAYMENT_SIRUMOBILE_STATUS == 'True');
      $this->merchantId = defined('MODULE_PAYMENT_SIRUMOBILE_MERCHAND_ID') ? MODULE_PAYMENT_SIRUMOBILE_MERCHAND_ID : null;
      $this->merchantSecret = defined('MODULE_PAYMENT_SIRUMOBILE_MERCHAND_SECRET') ? MODULE_PAYMENT_SIRUMOBILE_MERCHAND_SECRET : null;
      $this->return_address = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
      $this->cancel_address = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
	  $this->servicegroup = defined('MODULE_PAYMENT_SIRUMOBILE_SERVICE_GROUP') ? MODULE_PAYMENT_SIRUMOBILE_SERVICE_GROUP : 2;
	  $this->taxclass = defined('MODULE_PAYMENT_SIRUMOBILE_TAX_CLASS') ? MODULE_PAYMENT_SIRUMOBILE_TAX_CLASS : 2; 
		
      if (null === $this->sort_order) return false;
      if (IS_ADMIN_FLAG === true 
              && ((defined('MODULE_PAYMENT_SIRUMOBILE_MERCHAND_ID') && MODULE_PAYMENT_SIRUMOBILE_MERCHAND_ID == '264') 
              || (defined('MODULE_PAYMENT_SIRUMOBILE_MERCHAND_SECRET')  && MODULE_PAYMENT_SIRUMOBILE_MERCHAND_SECRET == '7f4d9fa4bfe1495682e2bd92d25a5d802a045187'))
         ) 
      	 $this->title .= '<span class="alert">' .MODULE_PAYMENT_SIRUMOBILE_ALERT_TEST .'</span>';		

      if ((int)MODULE_PAYMENT_SIRUMOBILE_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_SIRUMOBILE_ORDER_STATUS_ID;
      }
		
	 $_SESSION['layoutType'] = ''	;
     // Set maximum payment amount allowed for mobile payments
	 $max_amount = defined('MODULE_PAYMENT_SIRUMOBILE_MAX_PRICE') ? MODULE_PAYMENT_SIRUMOBILE_MAX_PRICE : 40;	
     $this->max_amount = number_format((float)$max_amount, 2);
	
	 $detect = new Detection\MobileDetect;
	 $isMobile = $detect->isMobile();
	 $isTablet = $detect->isTablet();	
		
	 if ($isMobile || $_SESSION['layoutType'] == 'mobile' ) 
	 {
		$this->variant = 'variant2';
	 } else if ($isTablet || $_SESSION['layoutType'] == 'tablet' )
	 {
		$this->variant = 'variant3';
	  } else {
		 $this->variant = 'variant3';
	  }			


//	  if (!isset($layoutType)) $layoutType = ($isMobile ? ($isTablet ? 'tablet' : 'mobile') : 'default');	
//
//	  // Variant type selected
//	  if ($layoutType === $isMobile) {$this->variant = 'variant2'; } 
//		elseif ($layoutType === $isTablet) {$this->variant = 'variant3'; }
//	  else $this->variant = 'variant4';
//		
//	 $this->variant = 'variant2';	
		
      if (is_object($order)) $this->update_status();
    }

	// class methods
    function update_status() {
      global $db, $order;
	  // Zone check
      if ($this->enabled && (int)MODULE_PAYMENT_SIRUMOBILE_ZONE > 0 && isset($order->billing['country']['id'])) {
        $check_flag = false;
        $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SIRUMOBILE_ZONE . "' and zone_country_id = '" . (int)$order->billing['country']['id'] . "' order by zone_id");
        while (!$check->EOF) {
          if ($check->fields['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
          $check->MoveNext();
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
		
	  // disable the module if the order only contains euro. Only EUR orders accepted
      if ($this->enabled == true) 
      {
        if(!(in_array($order->info['currency'], $this->allowed_currencies))) $this->enabled = false;
      }
		  
	  // disable if amount more than maximum amount
      if ($this->enabled == true) 
      {	
		if ($this->amount >= $this->max_amount) $this->enabled = false;	  
      }	

	  // disable User is not currently using mobile internet connection. Hiding payment method from checkout.
      if ($this->enabled == true) 
      {	
		if ($this->isIpAllowed() === false)  $this->enabled = false;	  
      }		  

      // other status checks?
      if ($this->enabled) {
        // other checks here
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return array('title' => MODULE_PAYMENT_SIRUMOBILE_TEXT_DESCRIPTION);
    }

    function process_button() {
    global $order, $currencies, $db, $order_totals; 
    // ********************************************
    // *******         sirumobile           *******
    // ********************************************	
		
    //Create a randomized order number and order stamp'
    $number_rand = time().rand(1,9);
    $order_number = str_pad($number_rand, 12, "1", STR_PAD_RIGHT);
	$this->ordernumber = $order_number;
	$this->amount = number_format($order->info['total'], 2, '.', '');	
	
	//	
	if (!isset($order->delivery['country']['iso_code_2']) || isset($order->billing['country']['iso_code_2'])) 
	{
		$this->country = $order->customer['country']['iso_code_2'];   
	} else { $this->country = $order->delivery['country']['iso_code_2']; }
		
	//
	$this->customerreference = $_SESSION['customer_id'] .'- ' .$order->customer['email_address'];	 
		
	$api = $this->getSiruAPI();	

	// You can set default values for all payment requests (not required)
	$api->setDefaults([
	  'merchantId'	=> $this->merchantId,
	  'variant' => $this->variant,
	  'purchaseCountry' => $this->country,
	  'taxClass' => $this->taxclass,
	  'serviceGroup' => $this->servicegroup,
	  'redirectAfterSuccess' => $this->return_address,
	  'redirectAfterFailure' => $this->cancel_address,
	  'redirectAfterCancel'  => $this->cancel_address,
	 // 'signature'  => $this->getSignature()		
	]);	
	
	try 
	{
		// Create transaction to Siru API
		$transaction = $api->getPaymentApi()
			->set('basePrice', $this->amount)
			->set('notifyAfterSuccess', $this->return_address)
			->set('notifyAfterFailure', $this->cancel_address)
			->set('notifyAfterCancel', $this->cancel_address)
			->set('customerFirstName', $order->customer['firstname'])
			->set('customerLastName', $order->customer['lastname'])
			->set('customerEmail', $order->customer['email_address'])
			->set('customerNumber', $order->customer['telephone'])
			->set('customerLocale', $this->customerlocale)
			->set('purchaseReference',$this->ordernumber)
			->set('customerReference', $this->customerreference)
			->set('instantPay', 1)
			->set('description', $this->platformName .' Module -Version: ' .$this->moduleVersion)
			->createPayment();
		//header('location: ' . $transaction['redirect']); exit;
	} 
		catch(\Siru\Exception\TransportException $e) 
	  	{
			//echo "Unable to contact Payment API. Error was: " . $e->getMessage();
           	echo "Unable to contact Payment API. Error was: : {$e->getMessage()}\n\n";
           	echo "<div style='color:red'>" .MODULE_PAYMENT_SIRUMOBILE_PAYMENT_ERROR;
           	echo " <strong>" .MODULE_PAYMENT_SIRUMOBILE_SELECET_OTHER  ."</strong><br>";
           	echo " <div class='btn btn-warning'>" .zen_back_link() . zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) . "</a></div>";
		  	echo "</div>";
			echo "<style>.p-2, vr {  display: none; } </style>"; 
		  return;	
	  	}
		
	  	catch(\Siru\Exception\ApiException $e) 
		{
			echo "API request failed with error code " . $e->getCode() . ": " . $e->getMessage();
			foreach($e->getErrorStack() as $error) 
			{
			  echo "<div style='color:red'>" .MODULE_PAYMENT_SIRUMOBILE_PAYMENT_ERROR;
			  echo " <br><strong>" .MODULE_PAYMENT_SIRUMOBILE_SELECET_OTHER  ."</strong>";
			  echo $error . "<br />";	
			}
			  echo " <div class='btn btn-warning'>" .zen_back_link() . zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) . "</a></div>";
			  echo "</div>";
			  echo "<style>.p-2, vr {  display: none; } </style>"; 
			return;
		}
		
		/**
		*  NIDA VERKKOPALVELU
		** Error control	
		** Erase " // " and check to sending request data.
		**/	
		//print_r($api); exit; 
		//echo $transaction['redirect']; exit;
		//echo $transaction['uuid']; exit;
		//print_r($transaction); exit;
		//echo '<br><pre>'; print_r($transaction); exit;
		
		// Payment Button
		echo "<style>.p-2, vr {  display: none; } </style>"; 		
		echo '<a class="btn btn-success" href="'.$transaction['redirect'] .'">'.BUTTON_CONFIRM_ORDER_ALT .'</a>';	

    }

    function before_process() {
      return false;
    }

    function after_process() {
		global  $messageStack, $insert_id, $db, $order;
		
		// This is for order number $this->ordernumber
		$purchasereference = $_GET['siru_purchaseReference'];

		if ($_GET['siru_event'] == 'failure' || $_GET['siru_event'] == 'cancel')
		{		
			$error_message = MODULE_PAYMENT_SIRUMOBILE_PAYMENT_ERROR;
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
			$messageStack->add_session('checkout_payment', $error_message, 'error');		
		}	
			else if ($_GET['siru_event'] == 'success')				
			{
				// User was redirected from Siru payment page and query parameters are authentic and update order history
				$commentString = zen_db_prepare_input(MODULE_PAYMENT_SIRUMOBILE_TITLE_STATUS .' *success* ' .MODULE_PAYMENT_SIRUMOBILE_REFERENCE_NUMBER .$purchasereference  . ".");
				// Writing payment information to order status history	
				zen_update_orders_history($insert_id, $commentString, null, $order->info['order_status'], 3);	
			}		
		else
		{
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));  
		}			
    }

    function get_error() {
      return false;
    }

    function check() {
      global $db;
      if (!isset($this->_check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SIRUMOBILE_STATUS'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;
    }

    function install() {
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_SIRUMOBILE_STATUS')) {
        $messageStack->add_session('Sirumobile module already installed.', 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=sirumobile', 'SSL'));
        return 'failed';
      }
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Free Charge Module', 'MODULE_PAYMENT_SIRUMOBILE_STATUS', 'True', 'Do you want to accept Free Charge payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now());");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_SIRUMOBILE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mercand ID', 'MODULE_PAYMENT_SIRUMOBILE_MERCHAND_ID', '264', 'TEST merchant Id : 264', '6', '3', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mercand Secret', 'MODULE_PAYMENT_SIRUMOBILE_MERCHAND_SECRET', '7f4d9fa4bfe1495682e2bd92d25a5d802a045187', 'TEST merchant Secret: 7f4d9fa4bfe1495682e2bd92d25a5d802a045187', '6', '4', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Submerchant Reference', 'MODULE_PAYMENT_SIRUMOBILE_SUBMERCHAND_REFERENCE', '', 'Optional store identifier if you have more than one store using the same merchant Id.', '6', '5', now())");	
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Purchase Country', 'MODULE_PAYMENT_SIRUMOBILE_COUNTRY', '0', 'Purchase country FI / SE / DE', '6', '6', 'zen_get_zone_class_title', 'zen_cfg_pull_down_country_list(', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Tax Class', 'MODULE_PAYMENT_SIRUMOBILE_TAX_CLASS', '2', 'The VAT class sent to mobile operator. You can only select one tax class for mobile payments. Tax class 3 is the general rate, classes 1 and 2 are reduced rates. For more information, please see Siru Mobile API documentation.<br>0 = no tax vat 0%<br>1 = tax class 1 vat 10%<br>2 = tax class 2 vat 14%<br>3 = tax class 3 vat 25.5%<br>  For Finland use tax class 2', '6', '7', 'zen_cfg_select_option(array(\'0\', \'1\', \'2\', \'3\'), ', now())");			
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Service Group', 'MODULE_PAYMENT_SIRUMOBILE_SERVICE_GROUP', '2', 'The VAT class sent to mobile operator. You can only select one tax class for mobile payments. Tax class 3 is the general rate, classes 1 and 2 are reduced rates. For more information, please see Siru Mobile API documentation.<br>1 = Non-profit services<br>2 = Online service<br>3 = Entertainment services<br>4 = Adult entertainment service<br>  For Finland use tax class 2', '6', '7', 'zen_cfg_select_option(array(\'1\', \'2\', \'3\', \'4\'), ', now())");		

      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Max price', 'MODULE_PAYMENT_SIRUMOBILE_MAX_PRICE', '36.00', 'Max Price', '6', '9', now())");		
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_SIRUMOBILE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_SIRUMOBILE_ORDER_STATUS_ID', '3', 'Set the status of orders made with this payment module to this value', '6', '10', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_SIRUMOBILE_STATUS', 
				   'MODULE_PAYMENT_SIRUMOBILE_SORT_ORDER',
				   'MODULE_PAYMENT_SIRUMOBILE_MERCHAND_ID', 
                   'MODULE_PAYMENT_SIRUMOBILE_MERCHAND_SECRET',
				   'MODULE_PAYMENT_SIRUMOBILE_SUBMERCHAND_REFERENCE',
				   'MODULE_PAYMENT_SIRUMOBILE_COUNTRY',
				   'MODULE_PAYMENT_SIRUMOBILE_TAX_CLASS',
				   'MODULE_PAYMENT_SIRUMOBILE_SERVICE_GROUP',
				   'MODULE_PAYMENT_SIRUMOBILE_MAX_PRICE',
				   'MODULE_PAYMENT_SIRUMOBILE_ZONE', 
				   'MODULE_PAYMENT_SIRUMOBILE_ORDER_STATUS_ID');
    }

    /**
     * Creates instance of \Siru\Signature using merchant id and secret from settings.
     * @return \Siru\Signature
     */
    private function getSignature()
    {
        $merchantId = $this->merchantId;
        $secret = $this->merchantSecret;

        return new \Siru\Signature($merchantId, $secret);
    }

    /**
     * @return \Siru\API
     */
    private function getSiruAPI()
    {
        $signature = $this->getSignature();
		
        $api = new \Siru\API($signature);
        $sandbox = 'yes';
        // Use sandbox endpoint if configured by admin
        if($sandbox === 'yes'){
            $api->useStagingEndpoint();
        } else {
            $api->useProductionEndpoint();
        }
        return $api;
    }
	  
    /**
     * Checks from Siru API if mobile payments are available for end users IP-address.
     * Results are cached for one hour.
     * @return bool
     */
    private function isIpAllowed()
    {
		global  $messageStack;
        $ip = zen_get_ip_address();
		
		$customers_ip_address = $_SERVER['REMOTE_ADDR'];
		if (!isset($_SESSION['customers_ip_address'])) {
		  $_SESSION['customers_ip_address'] = $customers_ip_address;
		}	
		
        $cache = $_SESSION['customers_ip_address'];

        // We keep IP verification results in cache to avoid API call on each pageload
        if(isset($cache)) {
            return $cache;
        }

        $api = $this->getSiruAPI();

        try {
            $allowed = $api->getFeaturePhoneApi()->isFeaturePhoneIP($ip);

            // Cache result for one houre
            $cache = $allowed;

            return $allowed;

        } catch (\Exception $e) {
			$error_message = 'Exception: Unable to verify if %s is allowed to use mobile payments. %s (code %s) : ' .$ip .$e->getMessage() .$e->getCode(). 'error'.'\n\n';
			$messageStack->add_session('sirumobile', $error_message, 'error');
            return false;
        }
    }	 
	  
  }  // end class