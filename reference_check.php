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
 * Checks the referenceUrl for expiry (just in case admin does not run cron)
 *
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_duitku\duitku_helper;
use paygw_duitku\duitku_status_codes;

require('../../../config.php');
require_login();

$merchantorderid = required_param('merchantOrderId', PARAM_ALPHANUMEXT);;
$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'duitku');

$environment = $config->environment;
$merchantcode = $config->merchantcode;
$apikey = $config->apikey;

$courseid = ""; // Initialize course outside of if scope.
if ($component == 'enrol_fee' && $paymentarea == 'fee') {
    $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
}
$context = context_course::instance($courseid, MUST_EXIST);

$duitkuhelper = new duitku_helper($merchantcode, $apikey, $merchantorderid, $environment);
$requestdata  = $duitkuhelper->check_transaction($context);
$response = json_decode($requestdata['request']);
$httpcode = $requestdata['httpCode'];

$params = [
    'userid' => $USER->id,
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'payment_status' => duitku_status_codes::CHECK_STATUS_PENDING
];
$existingdata = $DB->get_record('paygw_duitku', $params);

// Check for HTTP code first.
// Earlier PHP versions would throw an error to $response->statusCode if not found. Later version would not.
if ($httpcode !== 200) {
    $redirecturl = "{$CFG->wwwroot}/payment/gateway/duitku/call.php?component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&description={$description}";
    header('location: '. $redirecturl);die;
}

if ($response->statusCode === duitku_status_codes::CHECK_STATUS_CANCELED) {
    $redirecturl = "{$CFG->wwwroot}/payment/gateway/duitku/call.php?component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&description={$description}";
    header('location: '. $redirecturl);die;
} else {
    $redirecturl = $environment === 'sandbox' ? 'https://app-sandbox.duitku.com/' : 'https://app-prod.duitku.com/';
    $redirecturl .= 'redirect_checkout?reference=' . $existingdata->reference;
    header('location: '. $redirecturl);die;
}
