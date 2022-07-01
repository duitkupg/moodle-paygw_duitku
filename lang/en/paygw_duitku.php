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

$string['apikey'] = 'API Key';
$string['apikey_help'] = 'API Key located in the Project website';
$string['course_error'] = 'Course not found';
$string['call_error'] = 'An error has occured when requesting transaction. Please try again or contact the site admin';
$string['environment'] = 'Environment';
$string['environment_help'] = 'Configure Duitku endpoint to be sandbox or production';
$string['expiry'] = 'Expiry Period';
$string['expiry_help'] = 'Expiry period for each transaction. Units set in minutes';
$string['expired_transaction'] = 'Expired Transaction';
$string['merchantcode'] = 'Merchant Code';
$string['merchantcode_help'] = 'Merchant code located in the Project website';
$string['gatewayname'] = 'Duitku';
$string['gatewaydescription'] = 'Duitku is a Payment Gateway Online in Indonesia that accepts payment through bank transfer, Debit/Credit Card, Virtual Account, Retail Stores dan e-wallet';
$string['paymentcancelled'] = 'Payment was cancelled';
$string['payment_expirations'] = 'Duitku Payment Gateway checks for expired transaction in database';
$string['paymentsuccessful'] = 'Payment was successful';
$string['pending_message'] = 'User has not completed payment yet';
$string['pluginname'] = 'Duitku';
$string['pluginname_desc'] = 'The Duitku module allows you to set up paid courses.';
$string['user_return'] = 'User has returned from redirect page';

$string['environment:production'] = 'Production';
$string['environment:sandbox'] = 'Sandbox';

$string['duitku_request_log'] = 'Duitku Paygw Plugin Log';
$string['log_request_transaction'] = 'Requesting a transaction to Duitku';
$string['log_request_transaction_response'] = 'Duitku response to Request Transaction';
$string['log_check_transaction'] = 'Checking transaction to Duitku';
$string['log_check_transaction_response'] = 'Duitku respose for Checking Transaction';
$string['log_callback'] = 'Received Callback from Duitku';
