<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * 	Stores all the function needed to run the plugin for better readability
 * 
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_duitku;

defined('MOODLE_INTERNAL') || die();

class duitku_helper {

	/**
	 * @var string The base API URL
	 */
	private $baseurl;

	/**
	 * @var string Merchant Code
	 */
	private $merchantCode;

	/**
	 * @var string Api Key
	 */
	private $apiKey;

	/**
	 * @var string Merchant Order Id
	 */
	private $merchantOrderId;

	/**
	 * @var string Environment
	 */
	private $environment;

	/**
	 * helper constructor.
	 *
	 * @param string 	$merchantCode 		Duitku Merchant Code
	 * @param string 	$apiKey Duitku 		API Key.
	 * @param string 	$merchantOrderId 	Customly genereted Merchant Order Id at call.php.
	 * @param bool 		$environment 		Environment string (sandbox or production).
	 */
	public function __construct(string $merchantCode, string $apiKey, string $merchantOrderId, string $environment) {
		$this->merchantCode = $merchantCode;
		$this->apiKey = $apiKey;
		$this->merchantOrderId = $merchantOrderId;
		$this->environment = $environment;
		$this->baseurl = $environment === 'sandbox' ? 'https://api-sandbox.duitku.com/api/merchant' : 'https://api-prod.duitku.com/api/merchant';
	}


	/**
	 * Creates a transaction to Duitku. Logs the request sent to Duitku as well.
	 *
	 * @param string 			$params_string 	Json encoded of the parameters array being sent to Duitku
	 * @param string 			$timestamp 		Timestamp in Milliseconds. Not generated in here to synchronize with the time given in the return Url.
	 * @param \context_course	$context		Course context needed for request logging
	 */
	public function create_transaction(string $params_string, string $timestamp, \context_course $context) {
		global $USER; 

		$log_request_transaction = get_string('log_request_transaction', 'paygw_duitku');
		$check_transaction_response_string = get_string('log_request_transaction_response', 'paygw_duitku');

		$url = "{$this->baseurl}/createInvoice";
		$signature = hash('sha256', $this->merchantCode.$timestamp.$this->apiKey);

		$curlopt_header = [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($params_string),
			'x-duitku-signature:' . $signature,
			'x-duitku-timestamp:' . $timestamp,
			'x-duitku-merchantcode:' . $this->merchantCode
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $curlopt_header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		//Log outgoing Request
		$event_array = [
			'context' => $context,
			'relateduserid' => $USER->id,
			'other' => [
				'Log Details' => $log_request_transaction,
				'sentParams' => $params_string,
				'destination' => $url
			]
		];
		$this->log_request($event_array);

		//execute post
		$request = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($request, 0, $header_size);

		//Log incoming response
		$event_array = [
			'context' => $context,
			'relateduserid' => $USER->id,
			'other' => [
				'Log Details' => $check_transaction_response_string,
				'httpCode' => $httpCode,
				'response' => json_encode($header),
			]
		];
		$this->log_request($event_array);

		//Return data to redirect user to the designated page
		$return_data = [
			'request' => $request,
			'httpCode' => $httpCode,
		];
		return $return_data; 
	}

	/**
	 * Checks the transaction of a user who has just returned from the Duitku Page and logs the request
	 * @param \context_course	$context	Course context needed for request logging
	 */
	public function check_transaction(\context_course $context) {
		global $USER;
		
		$check_transaction_string = get_string('log_check_transaction', 'paygw_duitku');
		$check_transaction_response_string = get_string('log_check_transaction_response', 'paygw_duitku');
		// $url = "{$this->baseurl}/transactionStatus";
		$url = $this->environment === 'sandbox' ? 'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus' : 'https://passport.duitku.com/webapi/api/merchant/transactionStatus';
		$signature = md5($this->merchantCode . $this->merchantOrderId . $this->apiKey);
		$params = [
			'merchantCode' => $this->merchantCode,
			'merchantOrderId' => $this->merchantOrderId,
			'signature' => $signature
		];
		$params_string = json_encode($params);
		//Setup curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json', 
			'Content-Length: ' . strlen($params_string)
			]
		);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		//Log outgoing request
		$event_array = [
			'context' => $context,
			'relateduserid' => $USER->id,
			'other' => [
				'Log Details' => $check_transaction_string,
				'sentParams' => $params_string,
				'destination' => $url
			]
		];
		$this->log_request($event_array);
		
		//execute post
		$request = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($request, 0, $header_size);

		//Log incoming response
		$event_array = [
			'context' => $context,
			'relateduserid' => $USER->id,
			'other' => [
				'Log Details' => $check_transaction_response_string,
				'httpCode' => $httpCode,
				'response' => json_encode($header),
			]
		];
		$this->log_request($event_array);

		$return_data = [
			'request' => $request,
			'httpCode' => $httpCode,
			'url' => $url
		];
		return $return_data; 
	}

	/**
	 * Logs any incoming/outgoing requests (including callbacks).
	 * @param array	$event_array	Course context needed for request logging
	 */
	public function log_request($event_array) {
		$event = \paygw_duitku\event\duitku_request_log::create($event_array);
		$event->trigger();
	}
}