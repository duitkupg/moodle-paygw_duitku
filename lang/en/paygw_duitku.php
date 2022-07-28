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
 * Contains all the strings used in the plugin.
 *
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['apikey'] = 'API key';
$string['apikey_help'] = 'API key located in the project website';
$string['course_error'] = 'Course not found';
$string['call_error'] = 'An error has occured when requesting transaction. Please try again or contact the site admin';
$string['environment'] = 'Environment';
$string['environment_help'] = 'Configure Duitku endpoint to be sandbox or production';
$string['expiry'] = 'Expiry period';
$string['expiry_help'] = 'Expiry period for each transaction. Units set in minutes';
$string['expired_transaction'] = 'Expired transaction';
$string['merchantcode'] = 'Merchant code';
$string['merchantcode_help'] = 'Merchant code located in the project website';
$string['gatewayname'] = 'Duitku';
$string['gatewaydescription'] = 'Duitku is a payment gateway online in Indonesia that accepts payment through bank transfer, Debit/Credit Card, Virtual Account, Retail Stores dan e-wallet';
$string['paymentcancelled'] = 'Payment was cancelled';
$string['payment_expirations'] = 'Duitku payment gateway checks for expired transaction in database';
$string['paymentsuccessful'] = 'Payment was successful';
$string['pending_message'] = 'User has not completed payment yet';
$string['pluginname'] = 'Duitku';
$string['pluginname_desc'] = 'The Duitku module allows you to set up paid courses.';
$string['transactions'] = 'Duitku transactions';
$string['user_return'] = 'User has returned from redirect page';

$string['environment:production'] = 'Production';
$string['environment:sandbox'] = 'Sandbox';

$string['duitku_request_log'] = 'Duitku paygw Plugin Log';
$string['log_request_transaction'] = 'Requesting a transaction to Duitku';
$string['log_request_transaction_response'] = 'Duitku response to request transaction';
$string['log_check_transaction'] = 'Checking transaction to Duitku';
$string['log_check_transaction_response'] = 'Duitku respose for che cking transaction';
$string['log_callback'] = 'Received callback from Duitku';

$string['return_header'] = '<h2>Pending Transaction</h2>';
$string['return_sub_header'] = 'Course name : {$a->fullname}<br />';
$string['return_body'] = 'If you have already paid, wait a few moments then check again if you are already enrolled. <br /> We kept your payment <a href="{$a->reference}">here</a> in case you would like to return.';

$string['privacy:metadata:paygw_duitku:paygw_duitku'] = 'Transaction data for the Duitku payment gateway plugin.';
$string['privacy:metadata:paygw_duitku:paygw_duitku:userid'] = 'The ID of the user making requesting a transaction';
$string['privacy:metadata:paygw_duitku:paygw_duitku:component'] = 'Component payment type. Corelates to the enrol column in the enrol table';
$string['privacy:metadata:paygw_duitku:paygw_duitku:paymentarea'] = 'Payment area of transaction';
$string['privacy:metadata:paygw_duitku:paygw_duitku:itemid'] = 'Corelates to the column id from the enrol table';
$string['privacy:metadata:paygw_duitku:paygw_duitku:reference'] = 'Reference number received from Duitku.';
$string['privacy:metadata:paygw_duitku:paygw_duitku:timestamp'] = 'Timestamp of when the transaction was requested';
$string['privacy:metadata:paygw_duitku:paygw_duitku:signature'] = 'Signature used to verify the transaction';
$string['privacy:metadata:paygw_duitku:paygw_duitku:merchant_order_id'] = 'The order id used to identify the transaction';
$string['privacy:metadata:paygw_duitku:paygw_duitku:accountid'] = 'The payment account id.';
$string['privacy:metadata:paygw_duitku:paygw_duitku:payment_status'] = 'Transaction Payment Status.';
$string['privacy:metadata:paygw_duitku:paygw_duitku:pending_reason'] = 'The reason for the payment status';
$string['privacy:metadata:paygw_duitku:paygw_duitku:timeupdated'] = 'The time this specific transaction is updated';
$string['privacy:metadata:paygw_duitku:paygw_duitku:expiryperiod'] = 'The expiry period for this specific transaction';
$string['privacy:metadata:paygw_duitku:paygw_duitku:referenceurl'] = 'The reference link for when user wants to go back to a previous transaction.';
$string['privacy:metadata:paygw_duitku:duitku_com'] = 'Duitku payment gateway plugin sends user data from Moodle to Duitku.';
$string['privacy:metadata:paygw_duitku:duitku_com:merchantcode'] = 'Duitku merchant code';
$string['privacy:metadata:paygw_duitku:duitku_com:apikey'] = 'Duitku API key';
$string['privacy:metadata:paygw_duitku:duitku_com:signature'] = 'Signature generated to verify a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:merchant_order_id'] = 'The order ID generated per order';
$string['privacy:metadata:paygw_duitku:duitku_com:paymentAmount'] = 'The cost of the course requested for transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:username'] = 'Username of the user requesting a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:first_name'] = 'First name of the user requesting a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:last_name'] = 'Last name of the user requesting a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:address'] = 'Address of the user requesting a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:city'] = 'City of the user requesting a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:email'] = 'Email of the user requesting a transaction';
$string['privacy:metadata:paygw_duitku:duitku_com:country'] = 'Country of the user requesting a transaction';

$string['messageprovider:pending_payment'] = 'Duitku pending payment notification';
$string['messageprovider:pending_payment_body'] = 'You have a pending payment for the "{$a->fullname}" course <a href="{$a->referenceurl}">here</a>';
$string['messageprovider:pending_payment_small'] = 'A pending payment awaits you';
