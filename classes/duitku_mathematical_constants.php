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
 * Contains the mathematical constants to work with Duitku Payment Gateway Plugin.
 *
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_duitku;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores all of the mathematical constants used in the plugin
 *
 * @author  2022 Michael David <mikedh2612@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duitku_mathematical_constants {
    /**
     * @var string Multiplier for turning days into hours.
     */
    public const ONE_DAY_TO_HOURS = 24;

    /**
     * @var string Multiplier for turning hours to minutes
     */
    public const ONE_HOUR_TO_MINUTES = 60;

    /**
     * @var string Multiplier for turning minutes to seconds
     */
    public const ONE_MINUTE_TO_SECONDS = 60;

    /**
     * @var string Multiplier for turning seconds to milliseconds
     */
    public const ONE_SECOND_TO_MILLISECONDS = 1000;

    /**
     * @var string One product
     */
    public const ONE_PRODUCT = 1;
}
