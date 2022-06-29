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
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_duitku\duitku_mathematical_constants;
use paygw_duitku\duitku_status_codes;
use paygw_duitku\duitku_helper;

require_once(__DIR__ . '/../../../config.php');
require_login();

$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'duitku');

$payable = helper::get_payable($component, $paymentarea, $itemid); //Get currency and payment amount
$surcharge = helper::get_gateway_surcharge('duitku');//In case user uses surcharge

//TODO: Check if currency is IDR. If not, then something went really wrong in config.
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

$courseid = ""; //Initialize course outside of if scope
if ($component == 'enrol_fee' && $paymentarea == 'fee') {
	$courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
}

if (!$course = $DB->get_record("course", ["id"=>$courseid])) {
	redirect($CFG->wwwroot, get_string('course_error', 'paygw_duitku')); //Redirects if course id not found
}

//Inititate the required data to send when creating transaction
$environment = $config->environment;
$merchantCode = $config->merchantcode;
$apiKey = $config->apikey;
$expiryPeriod = $config->expiry;
$timestamp = (string)round(microtime(true) * paygw_duitku\duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$merchantOrderId = $component . '-' . $paymentarea . '-' .  $itemid . '-' . $USER->id . '-' . $timestamp;
$callbackUrl = "{$CFG->wwwroot}/payment/gateway/duitku/callback.php";
$returnUrl = "{$CFG->wwwroot}/payment/gateway/duitku/return.php?component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&description={$description}&timestamp={$timestamp}";
$phoneNumber = empty($USER->phone1) === true ? "" : $USER->phone1;
$address = [ 
	'firstName' => $USER->firstname,
	'lastName' => $USER->lastname,
	'address' => $USER->address,
	'city' => $USER->city,
	'postalCode' => "",
	'phone' => $phoneNumber, //There are phone1 and phone2 for users. Main phone goes to phone1.
	'countryCode' => $USER->country
];

$customerDetail = [
	'firstName' => $USER->firstname,
	'lastName' => $USER->lastname,
	'email' => $USER->email,
	'phoneNumber' => $phoneNumber,
	'billingAddress' => $address,
	'shippingAddress' => $address
];

$itemDetails = [
	[
		'name' => $description,
		'price' => $cost,
		'quantity' => duitku_mathematical_constants::ONE_PRODUCT
	]
];

$params = [
	'paymentAmount' => $cost,
	'merchantOrderId' => $merchantOrderId,
	'productDetails' => $description,
	'customerVaName' => $USER->username,
	'merchantUserInfo' => $USER->username,
	'email' => $USER->email,
	'itemDetails' => $itemDetails,
	'customerDetail' => $customerDetail,
	'callbackUrl' => $callbackUrl,
	'returnUrl' => $returnUrl,
	'expiryPeriod' => (int)$expiryPeriod
];
$params_string = json_encode($params);

$params = [
	'userid' => $USER->id,
	'component' => $component,
	'paymentarea' => $paymentarea,
	'itemid' => $itemid
];

//Initiate data 
$payable = helper::get_payable($component, $paymentarea, $itemid);
$signature = md5($merchantCode . $merchantOrderId . $apiKey);
$referenceUrl = "{$CFG->wwwroot}/payment/gateway/duitku/reference_check.php?component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&merchantOrderId={$merchantOrderId}&description={$description}";

$paygw_data = new stdClass();
$paygw_data->userid = $USER->id;
$paygw_data->component = $component;
$paygw_data->paymentarea = $paymentarea;
$paygw_data->itemid = $itemid;
$paygw_data->timestamp = $timestamp;
$paygw_data->signature = $signature;
$paygw_data->merchant_order_id = $merchantOrderId;
$paygw_data->accountid = $payable->get_account_id();
$paygw_data->payment_status = duitku_status_codes::CHECK_STATUS_PENDING;
$paygw_data->pending_reason = get_string('pending_message', 'paygw_duitku');
$paygw_data->timeupdated = round(microtime(true) * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$paygw_data->expiryperiod = $timestamp + ($expiryPeriod * duitku_mathematical_constants::ONE_MINUTE_TO_SECONDS * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);//This converts expiry periods to milliseconds
$paygw_data->referenceurl = $referenceUrl;

$sql_statement = '
SELECT *
FROM {paygw_duitku}
WHERE userid = :userid
AND component = :component
AND paymentarea = :paymentarea
AND itemid = :itemid
ORDER BY {paygw_duitku}.timestamp DESC
';
$existing_data = $DB->get_record_sql($sql_statement, $params, 1);//Will return exactly 1 row. The newest transaction that was saved.
$duitku_helper = new duitku_helper($merchantCode, $apiKey, $merchantOrderId, $environment);
$context = context_course::instance($course->id, MUST_EXIST);
//If there are no existing transaction in the database then create a new one
if (empty($existing_data)){
	$request_data = $duitku_helper->create_transaction($params_string, $timestamp, $context);
	$request = json_decode($request_data['request']);
	$httpCode = $request_data['httpCode'];
	if($httpCode == 200) {
		$paygw_data->reference = $request->reference;//Reference only received after successful request transaction
		$DB->insert_record('paygw_duitku', $paygw_data);
		header('location: '. $request->paymentUrl);die;
	} else {
		redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));//Redirects back to payment page with error message
	}
}

//Check for any previous transaction in duitku using the previous data
$prev_merchantOrderId = $existing_data->merchant_order_id;
$new_duitku_helper = new duitku_helper($merchantCode, $apiKey, $prev_merchantOrderId, $environment);
$request_data = $new_duitku_helper->check_transaction($context);
$request = json_decode($request_data['request']);
$httpCode = $request_data['httpCode'];

//If Duitku does not recognize the transaction but there is an existing data or transaction failed.
if ($httpCode === 404 || $request->statusCode === duitku_status_codes::CHECK_STATUS_PENDING || $request->statusCode === duitku_status_codes::CHECK_STATUS_CANCELED) {
	$redirectUrl = $environment === 'sandbox' ? 'https://app-sandbox.duitku.com/' : 'https://app-prod.duitku.com/';
	$redirectUrl .= 'redirect_checkout?reference=' . $existing_data->reference;
	header('location: '. $redirectUrl);die;
}

if ($request->statusCode === duitku_status_codes::CHECK_STATUS_SUCCESS) {
	//If previous transaction is successful use a new merchantOrderId
	$request_data = $duitku_helper->create_transaction($params_string, $timestamp, $context);
	$request = json_decode($request_data['request']);
	$httpCode = $request_data['httpCode'];
	if($httpCode == 200) {
		//Insert to database to be reused later
		$paygw_data->reference = $request->reference;
		$DB->insert_record('paygw_duitku', $paygw_data);

		header('location: '. $request->paymentUrl);die;
	} else {
		redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));//Redirects back to payment page with error message
	}
}