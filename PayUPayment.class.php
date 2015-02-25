<?php

/**
 * 
 *  Copyright (C) 2013 PayU Hungary Kft.
 *
 *  This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @copyright   Copyright (c) 2013 PayU Hungary Kft. (http://www.payu.hu)
 * @link        http://www.payu.hu 
 * @license     http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 *
 * @package  	PayU SDK 
 * 
 */
 
require_once('PayUBase.class.php');


/**
 * PayU LiveUpdate
 *
 * Sending orders via HTTP request
 * 
 */
class PayULiveUpdate extends PayUBase{
    /*
     * Constructor of PayULiveUpdate class
     * 
     * @param mixed $config Configuration array or filename
     * @param boolean $debug Debug mode
     */
    public function __construct($config, $debug = false){
        $this->hashData = array();
        $this->formData = array();
        $this->logger = false;

        $this->validFields = array(
			//order
            "MERCHANT" => array("type" => "single", "paramName" => "merchantId", "required" => true),
            "ORDER_REF" => array("type" => "single", "required" => true),
            "ORDER_DATE" => array("type" => "single", "required" => true),
            "ORDER_PNAME" => array("type" => "product", "paramName" => "name", "required" => true),
            "ORDER_PCODE" => array("type" => "product", "paramName" => "code", "required" => true),
			"ORDER_MPLACE_MERCHANT" => array("type" => "product", "paramName" => "mplace", "required" => false),
            "ORDER_PINFO" => array("type" => "product", "paramName" => "info"),
            "ORDER_PRICE" => array("type" => "product", "paramName" => "price", "required" => true),
            "ORDER_QTY" => array("type" => "product", "paramName" => "qty", "required" => true),
            "ORDER_VAT" => array("type" => "product", "default" => "0", "paramName" => "vat", "required" => true),         
            "PRICES_CURRENCY" => array("type" => "single", "default" => "HUF","required" => true),
            "ORDER_SHIPPING" => array("type" => "single", "default" => "0"),           
            "DISCOUNT" => array("type" => "single", "default" => "0"),            
            "PAY_METHOD" => array("type" => "single", "default" => ""),
            "LANGUAGE" => array("type" => "single", "default" => "HU"),            
            "AUTOMODE" => array("type" => "single", "default" => "1"),  
            "ORDER_TIMEOUT" => array("type" => "single"),
            "TIMEOUT_URL" => array("type" => "single"),
            "BACK_REF" => array("type" => "single"),
            "LU_ENABLE_TOKEN" => array("type" => "single", "required" => false),
            "LU_TOKEN_TYPE" => array("type" => "single", "required" => false),
            
			//billing
            "BILL_FNAME" => array("type" => "single", "required" => true),
            "BILL_LNAME" => array("type" => "single", "required" => true),
            "BILL_COMPANY" => array("type" => "single"),
            "BILL_FISCALCODE" => array("type" => "single"),
            "BILL_EMAIL" => array("type" => "single", "required" => true),
            "BILL_PHONE" => array("type" => "single", "required" => true),
            "BILL_FAX" => array("type" => "single"),
            "BILL_ADDRESS" => array("type" => "single", "required" => true),
            "BILL_ADDRESS2" => array("type" => "single"),
            "BILL_ZIPCODE" => array("type" => "single", "required" => true),
            "BILL_CITY" => array("type" => "single", "required" => true),
            "BILL_STATE" => array("type" => "single", "required" => true),
            "BILL_COUNTRYCODE" => array("type" => "single", "required" => true),
            
			//delivery
            "DELIVERY_FNAME" => array("type" => "single", "required" => true),
            "DELIVERY_LNAME" => array("type" => "single", "required" => true),
            "DELIVERY_COMPANY" => array("type" => "single"),
            "DELIVERY_PHONE" => array("type" => "single", "required" => true),
            "DELIVERY_ADDRESS" => array("type" => "single", "required" => true),
            "DELIVERY_ADDRESS2" => array("type" => "single"),
            "DELIVERY_ZIPCODE" => array("type" => "single", "required" => true),
            "DELIVERY_CITY" => array("type" => "single", "required" => true),
            "DELIVERY_STATE" => array("type" => "single", "required" => true),
            "DELIVERY_COUNTRYCODE" => array("type" => "single", "required" => true),
        );
        
		//hash fields
        $this->hashFields = array(
            "MERCHANT",
            "ORDER_REF",
            "ORDER_DATE",
            "ORDER_PNAME",
            "ORDER_PCODE",
            "ORDER_PINFO",
            "ORDER_PRICE",
            "ORDER_QTY",
            "ORDER_VAT",
            "ORDER_SHIPPING",
            "PRICES_CURRENCY",
            "DISCOUNT",
            "PAY_METHOD",
			"ORDER_MPLACE_MERCHANT"
        );

        $this->setDefaults(array(
            $this->validFields
        ));

        parent::__construct($config, $debug);
        $this->fieldData['MERCHANT'] = $this->merchantId;
        $this->targetUrl = $this->luUrl;
		
    }

    /*
     * Generates a ready-to-insert HTML FORM
     * 
     * @param string $formName The ID parameter of the form
     * @param string $submitElement The type of the submit element ('button' or 'link')
     * @param string $submitElementText The lebel for the submit element
     * @param boolean $tags Display open/close TAGs
     */
    public function createHtmlForm($formName, $submitElement=false, $submitElementText=false, $data = array(), $tags=true, $iframe= false){
        if(!$this->prepareFields("ORDER_HASH")){
            return false;
        }
		$this->logFunc("LiveUpdate",$this->formData, $this->formData['ORDER_REF']);
        return parent::createHtmlForm($formName, $submitElement, $submitElementText, $this->formData, $tags, $iframe = false);
    }
    
    /*
     * Sends notification via cURL
     * 
     * @param array $data (Optional) Data array to be sent
     */
    public function requestCurl($data = false, $target = '', $method = ''){
        if(!$data){
            if (!$this->prepareFields("ORDER_HASH")) {
                return false;
            }
            $data = $this->formData;
        }
        return parent::requestBaseCurl($this->targetUrl, "POST", $this->formData);
    }      
    
    /*
     * Returns a list of missing required fields
     * 
     */
    public function getMissing(){
        return $this->missing;
    }
	
	public function log(){
		return $this->log();
	}
}


/**
 * PayU BACK_REF
 * 
 * Processes information sent via HTTP GET on the returning site after a payment
 *
 */
class PayUBackRef extends PayUBase{
    
    private $backref;
    private $request;
    private $error;
    private $returnVars = array("RC", "RT", "3dsecure", "date", "payrefno", "ctrl");
	public $backStatusArray = array();
	public $successful_status = array(
		"IN_PROGRESS",			//card authorized on backref
		"PAYMENT_AUTHORIZED",	//IPN
		"COMPLETE",				//IDN
		"WAITING_PAYMENT",		//waiting for WIRE 
		"CASH"					//payed on delivery
	);
	public 	$unsuccessful_status = array(
		"CARD_NOTAUTHORIZED",	//unsuccessful transaction 
		"FRAUD",
		"TEST"
	);

	
    /*
     * Constructor of PayUBackRef class
     * 
     * @param mixed $config Configuration array or filename
     * @param string $backref BACK_REF URL sent
     * @param boolean $debug Debug mode
     */
    public function __construct($config, $backref=false, $debug=false){
        parent::__construct($config, $debug);
        $this->backref = $backref;
        
        if($backref){
            $this->createRequestUriGiven();
        } else {
            $this->createRequestUriNotGiven();
        }
	
    }
    
    /*
     * Creates request URI from the given BACK_REF
     * Useful when using special URLs (ie.: https://something.com:4848/)
     * 
     */
    private function createRequestUriGiven(){
	    $this->request = $this->backref;
        if (count($_GET) > 6) {
            $this->request .= "&";
        } else {
            $this->request .= "?";
        }
        foreach ($this->returnVars as $var) {
            $this->request .= $var . "=" . urlencode($_GET[$var]) . "&";
        }
        $this->request = substr($this->request, 0, -1);
    }
    
    /*
     * Creates request URI from HTTP SERVER VARS.
     * Handles http and https
     * 
     */
    private function createRequestUriNotGiven(){
		$https = 0;
        if (isset($_SERVER['HTTP_FRONT_END_HTTPS']) and $_SERVER['HTTP_FRONT_END_HTTPS'] == "On") {
            $https++;
        } 	
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $https++;
        } 		
		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
			$https++;
		}

		if ($https>0) {
            $this->request = "https://";
        } elseif ($https==0) {
            $this->request = "http://";
        }
        $this->request .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    /*
     * Validates CTRL variable
     * 
     */
    private function checkCtrl(){

        $requestURL = substr($this->request, 0, -38); //the last 38 characters are the CTRL param
        $hashInput = strlen($requestURL).$requestURL;
	
		//optional debug info, no need for live payment
		/*if(!$this->debug){
			echo "\n<br />".$this->request;
			echo "\n<br />".$requestURL;
			echo "\n<br />".$this->hmac($this->secretKey, $hashInput);
			echo "\n<br />".$_GET['ctrl'];
		}*/
		
        if (isset($_GET['ctrl']) && $_GET['ctrl'] == $this->hmac($this->secretKey, $hashInput)) {
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Check card authorization response
	 *
	 * 1. check ctrl
     * 2. check RC & RT 
	 * 3. check IOS status
     * 
     */
    public function checkResponse(){

		parent::logFunc("BackRef",$_REQUEST,$this->order_ref);
	
        if(!$this->checkCtrl()){	
            $this->error = "INVALID CTRL";
			$this->backStatusArray = array(
				'BACKREF_DATE' => (isset($_REQUEST['date'])) ? $_REQUEST['date'] : 'N/A',
				'REFNOEXT' => (isset($_REQUEST['order_ref'])) ? $_REQUEST['order_ref'] : 'N/A',
				'PAYREFNO' => (isset($_REQUEST['payrefno'])) ? $_REQUEST['payrefno'] : 'N/A',
				'ORDER_STATUS' => 'N/A',
				'PAYMETHOD' => 'N/A'
			);  
			return false;
        }

		if (!isset($this->order_ref)) {
			$this->error = "Missing order_ref variable";
			return false;
		}
		
		// Start IOS in the background to confirm "checkResponse"	
		$ios = new PayUIos(array('MERCHANT' => $this->merchantId, 'SECRET_KEY' => $this->secretKey), $this->order_ref);

		$this->backStatusArray = array(
			'BACKREF_DATE' => (isset($_REQUEST['date'])) ? $_REQUEST['date'] : 'N/A',
			'REFNOEXT' => (is_object($ios)) ? $this->order_ref : 'N/A' ,
			'PAYREFNO' => (is_object($ios)) ? $ios->status['REFNO'] : 'N/A',
			'ORDER_STATUS' => (is_object($ios)) ? $ios->status['ORDER_STATUS'] : 'IOS_ERROR',
			'PAYMETHOD' => (is_object($ios)) ? $ios->status['PAYMETHOD'] : 'N/A'
		);  	
	
		parent::logFunc("BackStatus",$this->backStatusArray,$this->order_ref);
		
		//check IOS ORDER_STATUS
		if (is_object($ios)) {
			if (in_array(trim($ios->status['ORDER_STATUS']),$this->successful_status)) {
				$this->backStatusArray['RESULT'] = true;
				return true;
			} elseif (in_array(trim($ios->status['ORDER_STATUS']),$this->unsuccessful_status)) {
				$this->backStatusArray['RESULT'] = false;
				return false;
			} else {	
				$this->backStatusArray['RESULT'] = false;
				return false;
			}
		} else {
			$this->backStatusArray['RESULT'] = false;
			return false;		
		}		

		//ordinary browser
		if (isset($_GET['RT'])) {
			
			//000 and 001 is successful, unsuccessful otherwise
			if(in_array(substr($_GET['RT'], 0, 3),array("000","001","App"))) {
				$this->backStatusArray['RESULT'] = true;
				return true; 
			
			//empty return data	
			} elseif ($_GET['RT'] ==""){
				
				//check IOS ORDER_STATUS
				if (in_array(trim($ios->status['ORDER_STATUS']),$this->successful_status)) {
					$this->backStatusArray['RESULT'] = true;
					return true;
				} elseif (in_array(trim($ios->status['ORDER_STATUS']),$this->unsuccessful_status)) {
					$this->backStatusArray['RESULT'] = false;
					return false;
				} else {	
					$this->backStatusArray['RESULT'] = false;
					return false;
				}		
				
			} elseif ($_GET['RT'] =="No Response" || $_GET['RC']=="NR"){
				$this->backStatusArray['RESULT'] = true;
				return true;
			} else {
				$this->backStatusArray['RESULT'] = false;
				return false;
			}
			
		}else {
		
			$this->error = "MISSING VARIABLES";
			$this->backStatusArray['RESULT'] = false;
			return false;	
			
		}

	}
    
    /*
     * Get PayU reference number after chaeckResponse()
     * 
     */
    public function getPayURefNo(){
        return $_GET['payrefno'];
    }

    /*
     * Returns a list of errors if there was any
     * 
     */
    public function getError(){
        return $this->error;
    }
}


/**
 * PayU Instant Payment Notification
 *
 * Processes notifications sent via HTTP POST request
 * 
 */
class PayUIpn extends PayUBase{
	public $successful_status = array(
		"PAYMENT_AUTHORIZED",	//IPN
		"COMPLETE",				//IDN
		"REFUND",				//IRN
		"PAYMENT_RECEIVED",		//WIRE
		"CASH",					//CASH
	);

    /*
     * Constructor of PayUIpn class
     * 
     * @param mixed $config Configuration array or filename
     * @param boolean $debug Debug mode
     */
    public function __construct($config, $debug=false){
        parent::__construct($config, $debug);
    }
    
    /*
     * Validate recceived data against HMAC HASH
     * 
     */
    public function validateReceived(){
		
		parent::logFunc("IPN",$_REQUEST,$_REQUEST['REFNOEXT']);

		if (!in_array(trim($_REQUEST['ORDERSTATUS']),$this->successful_status)) {	
			return false;
		}

        if($this->createHashString($this->flatArray($_POST, array("HASH"))) == $_POST['HASH']){
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Creates INLINE string for corfirmation
     * 
     * @param boolean $echo Displays strings when true
     */
    public function confirmReceived($echo=false){
        $serverDate = date("YmdHis");
        $hashArray = array(
            $_POST['IPN_PID'][0],
            $_POST['IPN_PNAME'][0],
            $_POST['IPN_DATE'],
            $serverDate
        );
        $hash = $this->createHashString($hashArray);
		
		$string = "<EPAYMENT>".$serverDate."|".$hash."</EPAYMENT>";
			
        if($echo){
            echo $string;
        }

        return $string;
    }
}


/**
 * PayUIOS
 * 
 * Helper object containing information about a product
 *
 */
class PayUIos extends PayUBase{

     var $orderNumber;
	 var $hash;
	 var $merchantId;
	 var $orderStatus;
	 var $status = Array();
	
	public function __construct($config, $orderNumber = 0){
        parent::__construct($config, false);
		
		$hashArray = array(
            $this->merchantId,
            $orderNumber
        );
		$this->orderNumber = $orderNumber;
		$hash = $this->createHashString($hashArray);
		$this->hash = $hash;
		
		$this->runIos();
		
    }
	
	public function runIos(){
		
		$iosArray =array(
			'MERCHANT'=>$this->merchantId,
			'REFNOEXT'=>$this->orderNumber,
			'HASH'=>$this->hash
		);
		
        $this->status = array();
        if (in_array("curl",  get_loaded_extensions())) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://secure.payu.hu/order/ios.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($iosArray));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);	
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            $result = curl_exec($ch);
            curl_close($ch);           
            $dom = new DOMDocument;
            $dom->loadXML($result);           
            $order = $dom->getElementsByTagName("Order");
            foreach ($order->item(0)->childNodes as $item) {
                if ($item->nodeType == 1) {
                    $this->status[$item->tagName] = $item->nodeValue;
                }
            }
        } elseif (in_array("libxml",  get_loaded_extensions())) {
            $options = array(
              'http' => array(
                'method' => "POST",
                'header' =>
                  "Accept-language: en\r\n".
                  "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($iosArray, '', '&')
            ));
            $context = stream_context_create($options);
            $result = file_get_contents('https://secure.payu.hu/order/ios.php', false, $context);   
            $xml = new SimpleXMLElement($result);        
            $this->status = (array) $xml ;
        }  
		
	}
	
	/*
     * Getter method for ORDER_STATUS
     * 
     * @param string $orderNumber Order number in merchants system
     */
    public function getOrderStatus(){
        return $this->status["ORDER_STATUS"];
    }
	
	/*
     * Getter method for PAYMETHOD
     * 
     * @param string $orderNumber Order number in merchants system
     */
    public function getOrderPaymethod(){
        return $this->status["PAYMETHOD"];
    }
    
    /*
     * Getter method for ORDER_DATE
     * 
     * @param string $orderNumber Order number in merchants system
     */
    public function getOrderDate(){
        return $this->status["ORDER_DATE"];
    }

	/*
     * Getter method for REFNO
     * 
     * @param string $orderNumber Order number in merchants system
     */
    public function getOrderPayUNumber(){
        return $this->status["REFNO"];
    }
	
}


/**
 * PayUProduct
 * 
 * Helper object containing information about a product
 *
 */
class PayUProduct {
    public $name;
    public $group;
    public $code;
    public $info;
    public $price;
    public $qty;
    public $vat;
    public $ver;
    public $mplace;
    
    /*
     * Constructor for PayUProduct
     * 
     * Creates an object for a product for later processing
     * 
     * @param array $productParams Sets object properties according to variables passed in this array
     */
    public function __construct($productParams = array()){
        foreach ($productParams as $var=>$param){
            if (property_exists($this, $var)) {
                $this->$var = $param;
            }
        }
    }
}


?>
