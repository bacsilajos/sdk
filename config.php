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
    * In case of new currency
    * Add new ABC_CURRENCY and ABC_SECRET_KEY fields where "ABC" is the currency.
    * For example if you want to add RON, you have to add the next lines into $config array
    * 
       'RON_MERCHANT' => "",       //merchant account ID (RON)
       'RON_SECRET_KEY' => "",     //secret key for account ID (RON)
    *
    * NOTE: You can add contracted currencies only!
    *
    */
date_default_timezone_set('UTC');
$config = array(
    'HUF_MERCHANT' => "xxx",                                               //merchant account ID (HUF)
    'HUF_SECRET_KEY' => "2hx",                                             //secret key for account ID (HUF)
    'EUR_MERCHANT' => "",                                               //merchant account ID (EUR)
    'EUR_SECRET_KEY' => "",                                             //secret key for account ID (EUR)
    'USD_MERCHANT' => "",                                               //merchant account ID (USD)
    'USD_SECRET_KEY' => "",                                             //secret key for account ID (USD)	
	'METHOD' => "CCVISAMC",												//payment method	 empty -> select payment method on PayU payment page OR [ CCVISAMC, WIRE ]
	'ORDER_DATE' => date("Y-m-d H:i:s"),								//date of transaction
	'LOGGER' => true,													//transaction log
	'LOG_PATH' => 'log',												//path of log file
	'BACK_REF' => 'http://'.$_SERVER['HTTP_HOST'].'/backref.php',		//url of payu payment backref page
	'TIMEOUT_URL' => 'http://'.$_SERVER['HTTP_HOST'].'/timeout.php',	//url of payu payment timeout page
	'IRN_BACK_URL' => 'http://'.$_SERVER['HTTP_HOST'].'/irn.php',		//url of payu payment irn page
	'IDN_BACK_URL' => 'http://'.$_SERVER['HTTP_HOST'].'/idn.php',		//url of payu payment idn page
	'ORDER_TIMEOUT' => 9900,
	'LANGUAGE' => 'HU'
);


