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

 
/**
 * PayU Config
 *
 * Modify config data
 * 
 */
class PayUModifyConfig {

    public $config = array();

	/*
     * Constructor of PayUModifyConfig class
     * 
     * @param arry $config Configuration array 
     * @param boolean $debug Debug mode
     */
    public function __construct($config=array(), $debug=false){
		if ($debug) {
			print "<br><pre>CONFIG ARRAY<br>";
			print_r($config);
			print "</pre><br>";
			exit;
		}
		if (count($config)==0) {
			print "Empty config array!";
			exit;
		}
		$this->config = $config;
    }

	public function merchantByCurrency($currency = 'HUF') {      
        $this->config['CURRENCY'] = $currency;   
        $this->config['MERCHANT'] = (isset($this->config[$currency.'_MERCHANT'])) ? $this->config[$currency.'_MERCHANT'] : 'MISSING_MERCHANT' ;
        $this->config['SECRET_KEY'] = (isset($this->config[$currency.'_SECRET_KEY'])) ? $this->config[$currency.'_SECRET_KEY'] : 'MISSING_SECRET_KEY'; 
        return $this->config;       
	}
	
}

 
/**
 * Base class for PayU implementation SDK
 *
 */
class PayUBase {
    protected $merchantId;
    protected $secretKey;
    protected $settingsFile;
	protected $hashCode;
	protected $hashString;
    public $debug;
    public $targetUrl;
	public $baseUrl;
    public $logger = false;
    public $log_path = "log";
    protected $hashData = array();
    protected $validFields = array();
    protected $products = array();
    protected $missing = array();
    public $formData = array();
    public $hashFields = array();
    public $fieldData = array();
	
    public $settings = array(
        'MERCHANT' => 'merchantId',
        'SECRET_KEY' => 'secretKey',
        'BASE_URL' => 'baseUrl',
        'ALU_URL' => 'aluUrl',
        'LU_URL' => 'luUrl',
        'IOS_URL' => 'iosUrl',
        'IDN_URL' => 'idnUrl',
        'IRN_URL' => 'irnUrl',
		'OC_URL' => 'ocUrl',
    );
    
    /*
     * Constructor of Base class
     * Cannot be instantiated
     * 
     * @param mixed $config Configuration array or filename
     * @param boolean $debug Debug mode
     */
    protected function __construct($config, $debug=false){
        $this->debug = $debug;

		$defaults = $this->defaultsData();
		$this->processConfig($defaults);
        
        if(is_array($config)){
            $this->processConfig($config);
        } elseif(is_string($config)){
            if(file_exists($config)){
                require_once($config);
                $this->processConfig($config);
            }
        } else {
            echo "Unable to read settings!";
            exit();
        }
    }
 
    /*
     * Set default URL
     * 
     */ 
	public function defaultsData() {
		$defaults = array(
			'BASE_URL' => "https://secure.payu.hu/", //please add TRAILING SLASH
			'LU_URL' => "order/lu.php",   //relative to BASE_URL
			'ALU_URL' => "order/alu.php", //relative to BASE_URL
			'IDN_URL' => "order/idn.php", //relative to BASE_URL
			'IRN_URL' => "order/irn.php", //relative to BASE_URL
			'IOS_URL' => "order/ios.php", //relative to BASE_URL
			'OC_URL' => "order/tokens/"   //relative to BASE_URL
		);
		return $defaults;
	}
	
    /*
     * Set config options
     * 
     * @param array $config Array with config options
     */
    public function processConfig($config){
        foreach($config as $setting=>$value){
            if(array_key_exists($setting, $this->settings)){
                $prop = $this->settings[$setting];
                $this->$prop = $config[$setting];
            }
        }
    }
    
    /*
     * HMAC HASH creation
     * RFC 2104
     * http://www.ietf.org/rfc/rfc2104.txt
     * 
     * @param string $key Secret key for encryption
     * @param string $data String to encode
     */
    protected function hmac($key, $data) {
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack("H*", md5($k_ipad . $data)));exit;
    }

    /*
     * Creates the strings HASH-ready form
     * 
     * @param string $str String to be modified
     */
    protected function stringWithLength($str){
        $str = strlen(StripSlashes($str)).$str;
        return $str;
    }
    
    /*
     * Create HASH code for an array (1-dimension only)
     * 
     * @param array $hashData Array of ordered fields to be HASH-ed
     */
    protected function createHashString($hashData){

        $hashString = "";
        foreach ($hashData as $field){	
            if(is_array($field)){
                echo "No multi-dimension array allowed!";
                exit();
            }
            $hashString .= self::stringWithLength($field);
        }
		
		$this->hashString = $hashString;
		$this->hashCode = $this->hmac($this->secretKey, $hashString);      
        return $this->hashCode;
    }
    
    /*
     * Creates hidden HTML field
     * 
     * @param string $name Name of the field. ID parameter will be generated without "[]"
     * @param sting $value Value of the field 
     */
    public function createHiddenField($name, $value){
        if(substr($name, -2, 2) == "[]"){
           $id = substr($name, 0, -2);
        } else {
            $id = $name;
        }
        //$value = addslashes($value);	
	
		if($name == "BACK_REF" or $name == "TIMEOUT_URL"){
			if (strpos($value, '?') !== FALSE) {
				$concat = '&';
			} else {
				$concat = '?';
			}		
			$value .= $concat.'order_ref='.$this->fieldData['ORDER_REF'].'&order_currency='.$this->fieldData['PRICES_CURRENCY'];
		}
	
        return "\n<input type='hidden' name='$name' id='$id' value='$value' />";
    }
    
    /*
     * Generates a ready-to-insert HTML FORM
     * 
     * @param string $formName The ID parameter of the form
     * @param array $formData Array of data to be added as hidden fields to the form
     * @param string $submitElement The type of the submit element ('button' or 'link')
     * @param string $submitElementText The lebel for the submit element
     * @param boolean $tags Display open/close TAGs
     */
    public function createHtmlForm($formName, $submitElement=false, $submitElementText=false, $formData=array(), $tags = true, $iframe = true) {
	
		$form = "";
        if($tags){
			if (isset($formData['METHOD'])) {
				$formMethod = 'GET';
			} else {
				$formMethod = 'POST';
			}
			$form .= "\n\n\n<form action='".$this->baseUrl.$this->targetUrl."' method='".$formMethod."' id='$formName'>";	
        }
      
		$logString = "";
	   
        foreach ($formData as $name => $field){
		
            if(is_array($field)){
                foreach ($field as $subField){
                    $form .= $this->createHiddenField($name."[]",$subField);
					$logString .= $name.'='.$subField."\n";
                }
            } else {
                $form .= $this->createHiddenField($name,$field);
				$logString .= $name.'='.$field."\n";
            }
        }

		$form .= $this->createHiddenField("SDK_VERSION",$this->sdkVersion());
		$order_id = isset($formData['ORDER_REF']) ? $formData['ORDER_REF'] : 0;
        		
        if($submitElement && $submitElementText){
            if($submitElement == "link"){
                $form .= "\n<a href='javascript:document.getElementById(\"$formName\").submit()'>".addslashes($submitElementText)."</a>";
            } elseif ($submitElement == "button"){
                $form .= "\n<button type='submit'>".addslashes($submitElementText)."</button>";
            } elseif ($submitElement == "auto"){
                $form .= "\n<button type='submit'>".addslashes($submitElementText)."</button>";
				$form .= "\n<script language=\"javascript\" type=\"text/javascript\">document.getElementById(\"$formName\").submit();</script>";	
            }
        }
        if($tags){
            $form .= "\n</form>";
			
        }

        if($this->debug){
			print "<pre>";
			echo highlight_string($form,true);
			print "<br>HASH FIELDS<br>";
			print "-----------------------------------------------------------------------------------<br>";
            print_r($this->hashFields);
			print "<br>HASH DATA<br>";
			print "-----------------------------------------------------------------------------------<br>";
            print_r($this->hashData);
			print "<br>HASH STRING<br>";
			print "-----------------------------------------------------------------------------------<br>";
            print $this->hashString."<br>";
			print "<br>HASH CODE<br>";
			print "-----------------------------------------------------------------------------------<br>";
			print $this->hashCode."<br>";
			print "<br>HASH CHECK<br>";
			print "-----------------------------------------------------------------------------------<br>";
			print "<a href='http://hash.online-convert.com/md5-generator'>ONLINE HASH CONVERTER</a><br><br>";
			print "LiveUpdate OBJECT<br>";
			print "-----------------------------------------------------------------------------------<br>";
			print_r($this);
			print "</pre>";
			exit;
        }
        
        return $form;
    }

    /*
     * Generates raw data array with HMAC HASH code for custom processing
     * 
     * @param string $hashFieldName Index-name of the generated HASH field in the associative array
     */
    public function createPostArray($hashFieldName = "ORDER_HASH"){
        if(!$this->prepareFields($hashFieldName)){
            return false;
        }
        
        return $this->formData;
    }
    
    /*
     * Sends a HTTP request via cURL or file_get_contents() and returns the response
     *
     * @param string $url Base URL for request
     * @param array $getParams Parameters to send
     */
    protected function createSimpleRequest($url,$getParams=array()){
        if($this->checkCurl()){
            $response = $this->requestCurl($url,"POST",$getParams);
        } else {
            $response = $this->requestSafe($url."?".http_build_query($getParams), "POST");
        }
        
        return $response;
    }
    
    /*
     * Sends a HTTP request via file_get_contents() and returns the response
     * 
     * @param string $url Base URL for request
     * @param string $method Method to use when sending request (ie: GET, POST)
     */
    private function requestSafe($url, $method="GET"){
        $url = $this->baseUrl.$url;
        $aContext = array(
            'http' => array(
                //'proxy' => 'proxy:8080',
                'method' => $method,
                'request_fulluri' => true,
            ),
        );
        $cxContext = stream_context_create($aContext);

        return file_get_contents($url, False, $cxContext);
    }
    
    /*
     * Sends a HTTP request via cURL and returns the response 
     * 
     * @param string $url Base URL for request
     * @param string $method Method to use when sending request (ie: GET, POST)
     * $param array $params Array of data to send
     */
    protected function requestBaseCurl($url, $method = "GET", $params = null){   
            $ch = curl_init();
            $url = $this->baseUrl.$url;
            curl_setopt($ch, CURLOPT_URL, $url);           
            if($method == "POST"){
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, CURLOPT_POST, false);
            }            
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;      
        return false;              
    }
    
    /*
     * Creates a 1-dimension array from a 2-dimension one
     * 
     * @param array $array Array to be processed
     * @param array $skip Array of keys to be skipped when creating the new array
     */
    protected function flatArray($array, $skip = array()){
        $return = array();
        foreach ($array as $name=>$item){
            if(!in_array($name,$skip)){
                if(is_array($item)){
                    foreach ($item as $subItem){
                        $return[] = $subItem;
                    }
                } else {
                    $return[] = $item;
                }
            }
        }
        return $return;
    }
    
    /*
     * Sets default value for a field
     * 
     * @param array $sets Array of fields and its parameters
     */
    protected function setDefaults($sets){
        foreach($sets as $set){
            foreach ($set as $field=>$fieldParams){
                if($fieldParams['type'] == 'single' && isset($fieldParams['default'])){
                    $this->fieldData[$field] = $fieldParams['default'];
                }
            }
        }
    }
    
    /*
     * Checks if all required fields are set.
     * Returns true or array of missing fields list
     */
    protected function checkRequired(){
        $missing = array();
        foreach ($this->validFields as $field=>$params){
            if(isset($params['required']) && $params['required']){
                if($params['type'] == "single"){
                    if(!isset($this->formData[$field])){
                        $missing[] = $field;
                    }
                } else if($params['type'] == "product"){
                    foreach ($this->products as $prod){
                        $paramName = $params['paramName'];
                        if(!isset($prod->$paramName)){
                            $missing[] = $field;
                        }
                    }
                }
            }
        }

        if($missing != array()){
              return $missing;
        }
        return true;
    }
    
    /*
     * Getter method for fields
     * 
     * @param string $fieldName Name of the field
     */
    public function getField($fieldName){
        return $this->fieldData[$fieldName];
    }
    
    /*
     * Setter method for fields
     * 
     * @param string $fieldName Name of the field to be set
     * @param string $fieldValue Value of the field to be set
     */
    public function setField($fieldName, $fieldValue){
        if(in_array($fieldName, array_keys($this->validFields))){

            $this->fieldData[$fieldName] = $fieldValue;
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Adds product to the $this->product array
     * 
     * @param mixed $product Array description of product or Product object
     */
    public function addProduct($product){
	
        if(is_array($product)){
            $product = new PayUProduct($product);
        } elseif (is_object($product)){
            //@todo istypeof  PayUProduct
      
        }else{
			echo "Not a valid product!";
            exit();
		}
		$this->products[] = $product;
        
    }
    
    /*
     * Finalizes and prepares fields for sending
     * 
     * @param string $hashName Name of the field containing HMAC HASH code
     */
    protected function prepareFields($hashName){
        $this->hashData = array();
        foreach ($this->hashFields as $field){
            $params = $this->validFields[$field];
            if($params['type'] == "single"){
                if(isset($this->fieldData[$field])){
                    $this->hashData[] = $this->fieldData[$field];
                }
            } elseif ($params['type'] == "product"){
                foreach($this->products as $product){
                    if(isset($product->$params["paramName"])){
                        $this->hashData[] = $product->$params["paramName"];
                    }
                }
            }
        }
        
        foreach($this->validFields as $field=>$params){
            if(isset($params["rename"])){
                $field = $params["rename"];
            }
            if($params['type'] == "single"){
                if(isset($this->fieldData[$field])){
                    $this->formData[$field] = $this->fieldData[$field];
                }
            } elseif($params['type'] == "product"){
                if(!isset($this->formData[$field])){
                    $this->formData[$field] = array();
                }
                foreach($this->products as $num=>$product){
                    if(isset($product->$params["paramName"])){
                        $this->formData[$field][$num] = $product->$params["paramName"];
                    }
                }   
            }
        }
        
        if($this->hashData && $hashName){
            $this->formData[$hashName] = $this->createHashString($this->hashData);
        }
        
        $this->missing = $this->checkRequired();
        if($this->missing === true){
            return true;
        } else {
            if ($this->debug) {
                echo "REQUIRED FIELDS MISSING\n";
                echo "More info with getMissing()\n";
            }

            return false;
        }
    }
    
    /*
     * Finds and processes validation response from HTTP response
     * 
     * @param string $resp HTTP response
     */
    public function processResponse($resp){
        preg_match_all("/<EPAYMENT>(.*?)<\/EPAYMENT>/", $resp, $matches);
        $data = explode("|",$matches[1][0]);
        return $this->nameData($data);
    }
    
    /*
     * Validates HASH code of the response
     * 
     * @param array $resp Array with the response data
     */
    public function checkResponseHash($resp){
        $hash = $resp['ORDER_HASH'];
        array_pop($resp);
        $calculated = $this->createHashString($resp);
        if($this->debug){
            echo "\ncalc:".$calculated;
            echo "\nrec: ".$hash."\n";
        }
        if($hash == $calculated){
            return true;
        }
        return false;
    }
    
    /*
     * Check if the cURL PHP extension is available
     */
    protected function checkCurl(){
        if(in_array("curl",  get_loaded_extensions())){
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Check if the DOM PHP extension is available
     */
    protected function checkDom(){
        if(in_array("dom",  get_loaded_extensions())){
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Check if the XmlLib PHP extension is available
     */
    protected function checkXmlLib(){
        if(in_array("libxml",  get_loaded_extensions())){
            return true;
        } else {
            return false;
        }
    }
	
    /*
     * Write log
     * 
     * @param string $state State of the payment process
     * @param array $data Data of the log
	 * @param string $order_id External ID of order
     */	  
	public function logFunc($state = '', $data = array(), $order_id = 0) {
		if ($this->logger) {
			$date = date('Y-m-d H:i:s',time());
			$logtext = "";
			foreach($data as $logkey=>$logvalue){
			
				if(is_array($logvalue)) {
					foreach($logvalue as $subkey => $subvalue) {
						$logtext .= $order_id.' '.$state.' '.$date.' '.$logkey.'='.$subvalue."\n";
					}
				} else {
					$logtext .= $order_id.' '.$state.' '.$date.' '.$logkey.'='.$logvalue."\n";
				}
				
			}			
			file_put_contents($this->log_path.'/'.date('Ymd',time()).'.log', $logtext, FILE_APPEND | LOCK_EX); 
		}
	}

    /*
     * Version of SDK
     */
    public function sdkVersion(){
		$version = 'PHP_2.1.4_sdk20141213';
        return $version;
    }
		
}

?>