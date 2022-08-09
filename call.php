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
 * Redirects user to the payment page
 *
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

$payable = helper::get_payable($component, $paymentarea, $itemid);// Get currency and payment amount.
$surcharge = helper::get_gateway_surcharge('duitku');// In case user uses surcharge.

// TODO: Check if currency is IDR. If not, then something went really wrong in config.
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

$courseid = ""; // Initialize course outside of if scope.
if ($component == 'enrol_fee' && $paymentarea == 'fee') {
    $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
}

if (!$course = $DB->get_record("course", ["id"  => $courseid])) {
    redirect($CFG->wwwroot, get_string('course_error', 'paygw_duitku')); // Redirects if course id not found.
}

// Inititate the required data to send when creating transaction.
$environment = $config->environment;
$merchantcode = $config->merchantcode;
$apikey = $config->apikey;
$expiryperiod = $config->expiry;
$timestamp = (string)round(microtime(true) * paygw_duitku\duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$merchantorderid = $timestamp; // Merchant Order Id is now based on timestamp
$callbackurl = "{$CFG->wwwroot}/payment/gateway/duitku/callback.php";
$returnparam = "component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&description={$description}";
$returnurl = "{$CFG->wwwroot}/payment/gateway/duitku/return.php?" . $returnparam; // Moodle does not allow more than 180 chars.
$phonenumber = empty($USER->phone1) === true ? "" : $USER->phone1;
$address = [
    'firstName' => $USER->firstname,
    'lastName' => $USER->lastname,
    'address' => $USER->address,
    'city' => $USER->city,
    'postalCode' => "",
    'phone' => $phonenumber, // There are phone1 and phone2 for users. Main phone goes to phone1.
    'countryCode' => $USER->country
];

$customerdetail = [
    'firstName' => $USER->firstname,
    'lastName' => $USER->lastname,
    'email' => $USER->email,
    'phoneNumber' => $phonenumber,
    'billingAddress' => $address,
    'shippingAddress' => $address
];

$itemdetails = [
    [
        'name' => $description,
        'price' => $cost,
        'quantity' => duitku_mathematical_constants::ONE_PRODUCT
    ]
];

$params = [
    'paymentAmount' => $cost,
    'merchantOrderId' => $merchantorderid,
    'productDetails' => $description,
    'customerVaName' => $USER->username,
    'merchantUserInfo' => $USER->username,
    'email' => $USER->email,
    'itemDetails' => $itemdetails,
    'customerDetail' => $customerdetail,
    'callbackUrl' => $callbackurl,
    'returnUrl' => $returnurl,
    'expiryPeriod' => (int)$expiryperiod,
    'additionalParam' => $component . '-' . $paymentarea . '-' .  $itemid . '-' . $USER->id
];
$paramstring = json_encode($params);

// Initiate data.
$payable = helper::get_payable($component, $paymentarea, $itemid);
$signature = md5($merchantcode . $merchantorderid . $apikey);
$referenceurl = "{$CFG->wwwroot}/payment/gateway/duitku/reference_check.php?";
$referenceurl .= $returnparam;
$minutestomilli = duitku_mathematical_constants::ONE_MINUTE_TO_SECONDS * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS;

$paygwdata = new stdClass();
$paygwdata->userid = $USER->id;
$paygwdata->component = $component;
$paygwdata->paymentarea = $paymentarea;
$paygwdata->itemid = $itemid;
$paygwdata->timestamp = $timestamp;
$paygwdata->signature = $signature;
$paygwdata->merchant_order_id = $merchantorderid;
$paygwdata->accountid = $payable->get_account_id();
$paygwdata->payment_status = duitku_status_codes::CHECK_STATUS_PENDING;
$paygwdata->pending_reason = get_string('pending_message', 'paygw_duitku');
$paygwdata->timeupdated = round(microtime(true) * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$paygwdata->expiryperiod = $timestamp + ($expiryperiod * $minutestomilli);// This converts expiry periods to milliseconds.

$params = [
    'userid' => $USER->id,
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid
];
$sql = 'SELECT * FROM {paygw_duitku} WHERE userid = :userid AND component = :component AND paymentarea = :paymentarea AND itemid = :itemid ORDER BY {paygw_duitku}.timestamp DESC';
$existingdata = $DB->get_record_sql($sql, $params, 1);// Will return exactly 1 row. The newest transaction that was saved.
$duitkuhelper = new duitku_helper($merchantcode, $apikey, $merchantorderid, $environment);
$context = context_course::instance($course->id, MUST_EXIST);
// Preparing data for the message sender.
$a = new stdClass();
$a->courseid = $course->id;
$a->fullname = $course->fullname;
// If there are no existing transaction in the database then create a new one.
if (empty($existingdata)) {
    $requestdata = $duitkuhelper->create_transaction($paramstring, $timestamp, $context);
    $request = json_decode($requestdata['request']);
    $httpcode = $requestdata['httpCode'];
    if ($httpcode == 200) {
        $paygwdata->reference = $request->reference;// Reference only received after successful request transaction.
        $paygwdata->referenceurl = $referenceurl  . "&merchantOrderId={$merchantorderid}";
        $a->referenceurl = $paygwdata->referenceurl;
        $DB->insert_record('paygw_duitku', $paygwdata);
        $duitkuhelper->send_pending_payment_message($a);
        header('location: '. $request->paymentUrl);die;
    } else {
        redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));
    }
}

// Check for any previous transaction in duitku using the previous data.
$prevmerchantorderid = $existingdata->merchant_order_id;
$newduitkuhelper = new duitku_helper($merchantcode, $apikey, $prevmerchantorderid, $environment);
$requestdata = $newduitkuhelper->check_transaction($context);
$request = json_decode($requestdata['request']);
$httpcode = $requestdata['httpCode'];

// Checks for expired transaction first.
if ($existingdata->expiryperiod < $timestamp) {
    $params = [
        'paymentAmount' => $cost,
        'merchantOrderId' => $prevmerchantorderid,
        'productDetails' => $description,
        'customerVaName' => $USER->username,
        'merchantUserInfo' => $USER->username,
        'email' => $USER->email,
        'itemDetails' => $itemdetails,
        'customerDetail' => $customerdetail,
        'callbackUrl' => $callbackurl,
        'returnUrl' => $returnurl,
        'expiryPeriod' => (int)$expiryperiod,
        'additionalParam' => $component . '-' . $paymentarea . '-' .  $itemid . '-' . $USER->id
    ];
    $paramstring = json_encode($params);
    $requestdata = $newduitkuhelper->create_transaction($paramstring, $timestamp, $context);
    $request = json_decode($requestdata['request']);
    $httpcode = $requestdata['httpCode'];
    if ($httpcode == 200) {
        // Insert to database to be reused later.
        $paygwdata->id = $existingdata->id;
        $paygwdata->merchant_order_id = $prevmerchantorderid; // Make sure to use the old merchant order id.
        $paygwdata->reference = $request->reference;
        $paygwdata->timestamp = $timestamp;
        $paygwdata->referenceurl = $referenceurl  . "&merchantOrderId={$prevmerchantorderid}";
        $paygwdata->expiryperiod = $timestamp + ($expiryperiod * $minutestomilli);// Converts expiry period to milliseconds.
        $DB->update_record('paygw_duitku', $paygwdata);
        $a->referenceurl = $paygwdata->referenceurl;
        header('location: '. $request->paymentUrl);die;
    } else {
        redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));
    }
}

// If Duitku does not recognize the transaction but there is an existing data or transaction failed.
if ($httpcode !== 200) {
    $params = [
        'paymentAmount' => $cost,
        'merchantOrderId' => $prevmerchantorderid,
        'productDetails' => $description,
        'customerVaName' => $USER->username,
        'merchantUserInfo' => $USER->username,
        'email' => $USER->email,
        'itemDetails' => $itemdetails,
        'customerDetail' => $customerdetail,
        'callbackUrl' => $callbackurl,
        'returnUrl' => $returnurl,
        'expiryPeriod' => (int)$expiryperiod,
        'additionalParam' => $component . '-' . $paymentarea . '-' .  $itemid . '-' . $USER->id
    ];
    $paramstring = json_encode($params);
    $requestdata = $newduitkuhelper->create_transaction($paramstring, $timestamp, $context);
    $request = json_decode($requestdata['request']);
    $httpcode = $requestdata['httpCode'];
    if ($httpcode == 200) {
        // Insert to database to be reused later.
        $paygwdata->id = $existingdata->id;
        $paygwdata->merchant_order_id = $prevmerchantorderid; // Make sure to use the old merchant order id.
        $paygwdata->reference = $request->reference;
        $paygwdata->timestamp = $timestamp;
        $paygwdata->referenceurl = $referenceurl  . "&merchantOrderId={$prevmerchantorderid}";
        $paygwdata->expiryperiod = $timestamp + ($expiryperiod * $minutestomilli);// Converts expiry period to milliseconds.
        $DB->update_record('paygw_duitku', $paygwdata);
        $a->referenceurl = $paygwdata->referenceurl;
        header('location: '. $request->paymentUrl);die;
    } else {
        redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));
    }
}

// Seperate this condition in httpcode is error 400 which will result in undefined property.
if ($request->statusCode === duitku_status_codes::CHECK_STATUS_PENDING) {
    $redirecturl = $environment === 'sandbox' ? 'https://app-sandbox.duitku.com/' : 'https://app-prod.duitku.com/';
    $redirecturl .= 'redirect_checkout?reference=' . $existingdata->reference;
    header('location: '. $redirecturl);die;
}

if ($request->statusCode === duitku_status_codes::CHECK_STATUS_CANCELED) {
    $params = [
        'paymentAmount' => $cost,
        'merchantOrderId' => $prevmerchantorderid,
        'productDetails' => $description,
        'customerVaName' => $USER->username,
        'merchantUserInfo' => $USER->username,
        'email' => $USER->email,
        'itemDetails' => $itemdetails,
        'customerDetail' => $customerdetail,
        'callbackUrl' => $callbackurl,
        'returnUrl' => $returnurl,
        'expiryPeriod' => (int)$expiryperiod,
        'additionalParam' => $component . '-' . $paymentarea . '-' .  $itemid . '-' . $USER->id
    ];
    $paramstring = json_encode($params);
    $requestdata = $newduitkuhelper->create_transaction($paramstring, $timestamp, $context);
    $request = json_decode($requestdata['request']);
    $httpcode = $requestdata['httpCode'];
    if ($httpcode == 200) {
        // Insert to database to be reused later.
        $paygwdata->merchant_order_id = $prevmerchantorderid; // Make sure to use the old merchant order id.
        $paygwdata->id = $existingdata->id;
        $paygwdata->reference = $request->reference;
        $paygwdata->timestamp = $timestamp;
        $paygwdata->referenceurl = $referenceurl  . "&merchantOrderId={$prevmerchantorderid}";
        $paygwdata->expiryperiod = $timestamp + ($expiryperiod * $minutestomilli);// Converts expiry period to milliseconds.
        $DB->update_record('paygw_duitku', $paygwdata);
        $a->referenceurl = $paygwdata->referenceurl;
        header('location: '. $request->paymentUrl);die;
    } else {
        redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));
    }
}

if ($request->statusCode === duitku_status_codes::CHECK_STATUS_SUCCESS) {
    $params = [
        'paymentAmount' => $cost,
        'merchantOrderId' => $merchantorderid,
        'productDetails' => $description,
        'customerVaName' => $USER->username,
        'merchantUserInfo' => $USER->username,
        'email' => $USER->email,
        'itemDetails' => $itemdetails,
        'customerDetail' => $customerdetail,
        'callbackUrl' => $callbackurl,
        'returnUrl' => $returnurl,
        'expiryPeriod' => (int)$expiryperiod,
        'additionalParam' => $component . '-' . $paymentarea . '-' .  $itemid . '-' . $USER->id
    ];
    $paramstring = json_encode($params);
    // If previous transaction is successful use a new merchantOrderId.
    $requestdata = $duitkuhelper->create_transaction($paramstring, $timestamp, $context);
    $request = json_decode($requestdata['request']);
    $httpcode = $requestdata['httpCode'];
    if ($httpcode == 200) {
        // Insert to database to be reused later.
        $paygwdata->reference = $request->reference;
        $paygwdata->referenceurl = $referenceurl  . "&merchantOrderId={$merchantorderid}";
        $DB->insert_record('paygw_duitku', $paygwdata);
        $a->referenceurl = $paygwdata->referenceurl;
        $duitkuhelper->send_pending_payment_message($a);
        header('location: '. $request->paymentUrl);die;
    } else {
        redirect("{$CFG->wwwroot}/enrol/index.php?id={$courseid}", get_string('call_error', 'paygw_duitku'));
    }
}
