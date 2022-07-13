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
 * Handles the return page after user returns from payment page
 *
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_duitku\duitku_helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_login();

// Parameters sent from Duitku return response and return url at enrol.html.
$merchantorderid = required_param('merchantOrderId', PARAM_TEXT);
$reference = required_param('reference', PARAM_TEXT);
$resultcode = required_param('resultCode', PARAM_TEXT);
$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_INT);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'duitku');

$environment = $config->environment;
$merchantcode = $config->merchantcode;
$apikey = $config->apikey;

$courseid = ""; // Initialize course outside of if scope.
if ($component === 'enrol_fee' && $paymentarea === 'fee') {
    $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
}

if (!$course = $DB->get_record("course", ["id" => $courseid])) {
    redirect($CFG->wwwroot);
}

$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);

if (!empty($SESSION->wantsurl)) {
    $destination = $SESSION->wantsurl;
    unset($SESSION->wantsurl);
} else {
    $destination = "{$CFG->wwwroot}/course/view.php?id={$course->id}";
}

// If user is enrolled.
$fullname = format_string($course->fullname, true, ['context' => $context]);
if (is_enrolled($context, null, '', true)) {
    redirect($destination, get_string('paymentthanks', '', $fullname));
}
// Somehow they aren't enrolled yet.
// Log user return.
$duitkuhelper = new duitku_helper($merchantcode, $apikey, $merchantorderid, $environment);
$eventarray = [
    'context' => $context,
    'relateduserid' => $USER->id,
    'other' => [
        'Log Details' => get_string('user_return', 'paygw_duitku')
    ]
];
$duitkuhelper->log_request($eventarray);

$referenceurl = "{$CFG->wwwroot}/payment/gateway/duitku/reference_check.php?";
$referenceurl .= "component={$component}&paymentarea={$paymentarea}&itemid={$itemid}&merchantOrderId={$merchantorderid}&description={$description}";
$PAGE->set_url($destination);
$a = new stdClass();
$a->teacher = get_string('defaultcourseteacher'); // Variable name must be $a, according to Moodle.
$a->fullname = $fullname;// I have tried using other variable name than $a and it was not recognized.
$a->reference = $referenceurl;
$response = (object)[
    'return_header' => format_text(get_string('return_header', 'paygw_duitku'), FORMAT_MOODLE),
    'return_sub_header' => format_text(get_string('return_sub_header', 'paygw_duitku', $a), FORMAT_MOODLE),
    'return_body' => format_text(get_string('return_body', 'paygw_duitku', $a), FORMAT_MOODLE)
];

// Output reason why user has not been enrolled yet.
echo $OUTPUT->header();
echo($OUTPUT->render_from_template('paygw_duitku/duitku_return_template', $response));
notice(get_string('paymentsorry', '', $a), $destination);
