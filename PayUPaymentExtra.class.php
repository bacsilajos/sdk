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
 * PayU Instant Delivery Information
 *
 * Sends delivery notification via HTTP
 * 
 */
class PayUIdn extends PayUBase{
    public $missing = array();
    
    public $targetUrl;
    public $formData;
    public $hashData;

    public $hashFields = array(
        "MERCHANT",
        "ORDER_REF",
        "ORDER_AMOUNT",
        "ORDER_CURRENCY",
        "IDN_DATE"
    );

    protected $validFields = array(
        "MERCHANT" => array("type"=>"single", "paramName"=>"merchantId", "required"=>true),
        "ORDER_REF" => array("type"=>"single", "paramName"=>"orderRef", "required"=>true),
        "ORDER_AMOUNT" => array("type"=>"single", "paramName"=>"amount", "required"=>true),
        "ORDER_CURRENCY" => array("type"=>"single", "paramName"=>"currency", "required"=>true),
        "IDN_DATE" => array("type"=>"single", "paramName"=>"idnDate", "required"=>true),
        "REF_URL" => array("type"=>"single", "paramName"=>"refUrl"),
    );

    /*
     * Constructor of PayUIdn class
     * 
     * @param mixed $config Configuration array or filename
     * @param boolean $debug Debug mode
     */
    public function __construct($config, $debug = false){
        $this->hashData = array();
        $this->formData = array();

        parent::__construct($config, $debug);
        $this->fieldData['MERCHANT'] = $this->merchantId;
        $this->targetUrl = $this->idnUrl;
    }

    /*
     * Creates associative array for the received data
     * 
     * @param array $data Processed data
     */
    protected function nameData($data){
        return array(
            "ORDER_REF"=>$data[0],
            "RESPONSE_CODE"=>$data[1],
            "RESPONSE_MSG"=>$data[2],
            "IDN_DATE"=>$data[3],
            "ORDER_HASH"=>$data[4],
        );
    }
    
    /*
     * Sends notification via cURL
     * 
     * @param array $data Data array to be sent
     */
    public function requestIdnCurl($data = false){

		if(!$data){
            return 'N/A|N/A|N/A|N/A|N/A';
        }

		$idnHash = parent::createHashString($data);
		$data['ORDER_HASH'] = $idnHash;

		$ch = curl_init();
		$url = "https://secure.payu.hu/order/idn.php";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 600);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		curl_close($ch);

		$dom = new DOMDocument;
		$dom->loadXML($result);
		$data = explode("|",$dom->textContent);

		$response = array(
            "ORDER_REF" => $data[0],
            "RESPONSE_CODE" => $data[1],
            "RESPONSE_MSG" => $data[2],
            "IDN_DATE" => $data[3],
            "ORDER_HASH" => $data[4]
        );

		return $response;
    }                                      
     
    /*
     * Returns a list of missing required fields
     * 
     */
    public function getMissing(){
        return $this->missing;
    }
}


/**
 * PayU Instant Refund Notification
 *
 * Sends Refund request via HTTP request
 * 
 */
class PayUIrn extends PayUBase{
    /*
     * Constructor of PayUIrn class
     * 
     * @param mixed $config Configuration array or filename
     * @param boolean $debug Debug mode
     */
    public function __construct($config, $debug = false){
        $this->hashFields = array(
            "MERCHANT",
            "ORDER_REF",
            "ORDER_AMOUNT",
            "ORDER_CURRENCY",
            "IRN_DATE",
            "ORDER_PCODE",
            "ORDER_QTY",
            "AMOUNT"
        );

        $this->validFields = array(
            "MERCHANT" => array("type" => "single", "paramName" => "merchantId", "required" => true),
            "ORDER_REF" => array("type" => "single", "paramName" => "orderRef", "required" => true),
            "ORDER_AMOUNT" => array("type" => "single", "paramName" => "amount", "required" => true),
            "AMOUNT" => array("type" => "single", "paramName" => "amount", "required" => true),
            "ORDER_CURRENCY" => array("type" => "single", "paramName" => "currency", "required" => true),
            "IRN_DATE" => array("type" => "single", "paramName" => "irnDate", "required" => true),
            "REF_URL" => array("type" => "single", "paramName" => "refUrl"),
            "ORDER_PCODE" => array("type" => "product", "paramName" => "code", "rename"=>"PRODUCTS_IDS"),
            "ORDER_QTY" => array("type" => "product", "paramName" => "qty", "rename" => "PRODUCTS_QTY")
        );

        parent::__construct($config, $debug);
        $this->fieldData['MERCHANT'] = $this->merchantId;
        $this->targetUrl = $this->irnUrl;
    }

    /*
     * Sends notification via cURL
     * 
     * @param array $data (Optional) Data array to be sent
     */
    public function requestIrnCurl($data = array()){
		if(!$data){
            return 'N/A|N/A|N/A|N/A|N/A';
        }
		$irnHash = parent::createHashString($data);
		$data['ORDER_HASH'] = $irnHash;
		
		$ch = curl_init();
		$url = "https://secure.payu.hu/order/irn.php";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 600);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$dom = new DOMDocument;
		$dom->loadXML($result);
		$data = explode("|",$dom->textContent);
		
		$response = array(
            "ORDER_REF" => $data[0],
            "RESPONSE_CODE" => $data[1],
            "RESPONSE_MSG" => $data[2],
            "IRN_DATE" => $data[3],
            "ORDER_HASH" => $data[4]
        );
		parent::logFunc("IRN",$response,$response['ORDER_REF']);
		return $response;
	}         
    
    /*
     * Returns a list of missing required fields
     * 
     */
    public function getMissing(){
        return $this->missing;
    }
}

?>