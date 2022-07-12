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
 * handles callback received from Duitku
 *
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_duitku\duitku_mathematical_constants;
use paygw_duitku\duitku_helper;
use paygw_duitku\duitku_status_codes;

// Does not require login.
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$merchantcode = required_param('merchantCode', PARAM_TEXT);
$amount = required_param('amount', PARAM_INT);
$merchantorderid = required_param('merchantOrderId', PARAM_TEXT);
$productdetail = required_param('productDetail', PARAM_TEXT);
$additionalparam = required_param('additionalParam', PARAM_TEXT);
$paymentcode = required_param('paymentCode', PARAM_TEXT);
$resultcode = required_param('resultCode', PARAM_TEXT);
$merchantuserid = required_param('merchantUserId', PARAM_TEXT);
$reference = required_param('reference', PARAM_TEXT);
$signature = required_param('signature', PARAM_TEXT);

// Making sure that merchant order id is in the correct format.
$custom = explode('-', $additionalparam);
if (empty($custom) || count($custom) < 4) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid value of additional param');
}

// Make sure all of the parameters are there.
if (empty($merchantcode) || empty($amount) || empty($merchantorderid) || empty($signature)) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Bad Parameter');
}

// Make sure it is not a failed payment.
if (($resultcode !== duitku_status_codes::CHECK_STATUS_SUCCESS)) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Payment Failed');
}

$userid = (int)$custom[3];
$component = $custom[0];
$paymentarea = $custom[1];
$itemid = (int)$custom[2];
$timestamp = (int)$merchantorderid;

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'duitku');
$payable = helper::get_payable($component, $paymentarea, $itemid);

$apikey = $config->apikey;
$expiryperiod = $config->expiry;
$environment = $config->environment;
$params = $merchantcode . $amount . $merchantorderid . $apikey;
$calcsignature = md5($params);
if ($signature != $calcsignature) {
    throw new Exception('Bad Signature');
}

$referenceurl = "{$CFG->wwwroot}/payment/gateway/duitku/reference_check.php?";
$referenceurl .= "component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&merchantOrderId={$merchantorderid}&description={$productdetail}";

$courseid = ""; // Initialize course outside of if scope.
if ($component == 'enrol_fee' && $paymentarea == 'fee') {
    $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
} else {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid Course');
}
$context = context_course::instance($courseid, MUST_EXIST);

// Double check on transaction before continuing.
$duitkuhelper = new duitku_helper($merchantcode, $apikey, $merchantorderid, $environment);
$requestdata = $duitkuhelper->check_transaction($context);
$response = json_decode($requestdata['request']);

if (($response->statusCode !== duitku_status_codes::CHECK_STATUS_SUCCESS)) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Payment Failed');
}

// Transaction verified.
$data = new stdClass();
$data->userid = $userid;
$data->component = $component;
$data->paymentarea = $paymentarea;
$data->itemid = $itemid;
$data->reference = $reference;
$data->timestamp = $timestamp;
$data->signature = $signature;
$data->merchant_order_id = $merchantorderid;
$data->accountid = $payable->get_account_id();
$data->payment_status = $resultcode;
$data->pending_reason = get_string('log_callback', 'paygw_duitku');
$data->timeupdated = round(microtime(true) * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$data->expiryperiod = $timestamp + ($expiryperiod * duitku_mathematical_constants::ONE_MINUTE_TO_SECONDS * duitku_mathematical_constants::ONE_SECOND_TO_MILLISECONDS);
$data->referenceurl = $referenceurl;

$existingdata = $DB->get_record('paygw_duitku', ['reference' => $reference]);
$data->id = $existingdata->id;
$DB->update_record('paygw_duitku', $data);

// Deliver Course.
$payable = helper::get_payable($component, $paymentarea, $itemid);
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), helper::get_gateway_surcharge('duitku'));
$paymentid = helper::save_payment($payable->get_account_id(), $component, $paymentarea, $itemid, $userid, $cost, $payable->get_currency(), 'duitku');
helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

$eventarray = [
    'context' => $context,
    'relateduserid' => $USER->id,
    'other' => [
        'Log Detail' => get_string('log_callback', 'paygw_duitku'),
        'merchantCode' => $merchantcode,
        'amount' => $amount,
        'merchantOrderId' => $merchantorderid,
        'productDetail' => $productdetail,
        'paymentCode' => $paymentcode,
        'resultCode' => $resultcode,
        'reference' => $reference,
        'signature' => $signature
    ]
];
$event = \paygw_duitku\event\duitku_request_log::create($eventarray);
$event->trigger();
