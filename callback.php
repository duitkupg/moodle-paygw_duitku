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
use paygw_duitku\duitku_helper;
use paygw_duitku\duitku_status_codes;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

/// Keep out casual intruders
if (empty($_POST) or !empty($_GET)) {
	http_response_code(400);
	throw new moodle_exception('invalidrequest', 'core_error');
}

$merchantCode = isset($_POST['merchantCode']) ? $_POST['merchantCode'] : null;
$amount = isset($_POST['amount']) ? $_POST['amount'] : null;
$merchantOrderId = isset($_POST['merchantOrderId']) ? $_POST['merchantOrderId'] : null;
$productDetail = isset($_POST['productDetail']) ? $_POST['productDetail'] : null;
$additionalParam = isset($_POST['additionalParam']) ? $_POST['additionalParam'] : null;
$paymentCode = isset($_POST['paymentCode']) ? $_POST['paymentCode'] : null;
$resultCode = isset($_POST['resultCode']) ? $_POST['resultCode'] : null;
$merchantUserId = isset($_POST['merchantUserId']) ? $_POST['merchantUserId'] : null;
$reference = isset($_POST['reference']) ? $_POST['reference'] : null;
$signature = isset($_POST['signature']) ? $_POST['signature'] : null;


//Making sure that merchant order id is in the correct format
$custom = explode('-', $merchantOrderId);
if (empty($custom) || count($custom) < 5) {
	throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid value of the request param: custom');
}

//Make sure all of the parameters are there
if (empty($merchantCode) || empty($amount) || empty($merchantOrderId) || empty($signature)) {
	throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Bad Parameter');
}

//Make sure it is not a failed payment
if (($resultCode !== duitku_status_codes::CHECK_STATUS_SUCCESS)) {
	throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Payment Failed');
}

$userid = (int)$custom[3];
$component = $custom[0];
$paymentarea = $custom[1];
$itemid = (int)$custom[2];
$timestamp = (int)$custom[4];

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'duitku');
$payable = helper::get_payable($component, $paymentarea, $itemid);

$apiKey = $config->apikey;
$expiryPeriod = $config->expiry;
$environment = $config->environment;
$params = $merchantCode . $amount . $merchantOrderId . $apiKey;
$calcSignature = md5($params);
if ($signature != $calcSignature) {
	throw new Exception('Bad Signature');
}

$referenceUrl = "{$CFG->wwwroot}/payment/gateway/duitku/reference_check.php?component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&merchantOrderId={$merchantOrderId}&description={$productDetail}";

$courseid = ""; //Initialize course outside of if scope
if ($component == 'enrol_fee' && $paymentarea == 'fee') {
	$courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
} else {
	throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid Course');
}
$context = context_course::instance($courseid, MUST_EXIST);

//Double check on transaction before continuing
$duitku_helper = new duitku_helper($merchantCode, $apiKey, $merchantOrderId, $environment);
$request_data = $duitku_helper->check_transaction($context);
$response = json_decode($request_data['request']);
$httpCode = $request_data['httpCode'];

if (($response->statusCode !== duitku_status_codes::CHECK_STATUS_SUCCESS)) {
	throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Payment Failed');
}

//Transaction verified
$data = new stdClass();
$data->userid = $userid;
$data->component = $component;
$data->paymentarea = $custom;
$data->itemid = $itemid;
$data->reference = $reference;
$data->timestamp = $timestamp;
$data->signature = $signature;
$data->merchant_order_id = $merchantOrderId;
$data->accountid = $payable->get_account_id();
$data->payment_status = $resultCode;
$data->pending_reason = get_string('log_callback', 'paygw_duitku');
$data->timeupdated = round(microtime(true) * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$data->expiryperiod = $timestamp + ($expiryPeriod * duitku_mathematical_constants::ONE_MINUTE_TO_SECONDS * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$data->referenceurl = $referenceUrl;

$existing_data = $DB->get_record('paygw_duitku', ['reference' => $reference]);
$data->id = $existing_data->id;
$DB->update_record('paygw_duitku', $data);

// Deliver course.
$component = $custom[0];
$paymentarea = $custom[1];
$itemid = (int)$custom[2];
$userid = (int)$custom[3];

$payable = helper::get_payable($component, $paymentarea, $itemid);
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), helper::get_gateway_surcharge('duitku'));
$paymentid = helper::save_payment($payable->get_account_id(), $component, $paymentarea, $itemid, $userid, $cost, $payable->get_currency(), 'duitku');
helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

$event_array = [
	'context' => $context,
	'relateduserid' => $USER->id,
	'other' => [
		'Log Detail' => get_string('log_callback', 'paygw_duitku'),
		'merchantCode' => $merchantCode,
		'amount' => $amount,
		'merchantOrderId' => $merchantOrderId,
		'productDetail' => $productDetail,
		'paymentCode' => $paymentCode,
		'resultCode' => $resultCode,
		'reference' => $reference,
		'signature' => $signature
	]
];
$event = \paygw_duitku\event\duitku_request_log::create($event_array);
$event->trigger();

// Find redirection.
$url = helper::get_success_url($component, $paymentarea, $itemid);
redirect($url, get_string('paymentsuccessful', 'paygw_duitku'), 0, 'success');