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
 * Contains class for Duitku payment gateway.
 *
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_duitku;

/**
 * The gateway class for Duitku payment gateway.
 *
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * Returns the list of currencies that the payment gateway supports.
     *
     * @return string[] An array of the currency codes in the three-character ISO-4217 format
     */
    public static function get_supported_currencies(): array {
        // 3-character ISO-4217: https://en.wikipedia.org/wiki/ISO_4217#Active_codes.
        return [
            'IDR'
        ];
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();
        $textattributes = 'size="40"'; // Same as css styling for forms.

        $mform->addElement('text', 'apikey', get_string('apikey', 'paygw_duitku'), $textattributes);
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'paygw_duitku');

        $mform->addElement('text', 'merchantcode', get_string('merchantcode', 'paygw_duitku'), $textattributes);
        $mform->setType('merchantcode', PARAM_TEXT);
        $mform->addHelpButton('merchantcode', 'merchantcode', 'paygw_duitku');

        $mform->addElement('text', 'expiry', get_string('expiry', 'paygw_duitku'), $textattributes);
        $mform->setType('expiry', PARAM_INT);
        $mform->addHelpButton('expiry', 'expiry', 'paygw_duitku');

        $options = [
            'production' => get_string('environment:production', 'paygw_duitku'),
            'sandbox'  => get_string('environment:sandbox', 'paygw_duitku'),
        ];

        $mform->addElement('select', 'environment', get_string('environment', 'paygw_duitku'), $options);
        $mform->addHelpButton('environment', 'environment', 'paygw_duitku');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(\core_payment\form\account_gateway $form, \stdClass $data, array $files, array &$errors): void {
        if ($data->enabled &&
                (empty($data->apikey) || empty($data->merchantcode) || empty($data->expiry))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
