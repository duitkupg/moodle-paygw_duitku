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
 * This event is triggered whenever a failed http request occurs
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_duitku\event;

defined('MOODLE_INTERNAL') || die();

/**
 * This class will be instantiated whenever a log is needed. Use $event->trigger() to log the events.
 * @author 2022 Michael David <mikedh2612@gmail.com>
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duitku_request_log extends \core\event\base {

    /**
     * Overrides the baseclass.
     *
     * Set all required data properties:
     *  1/ crud - letter [crud]
     *  2/ edulevel - using a constant self::LEVEL_*.
     *  3/ objecttable - name of database table IF objectid specified
     *
     * Optionally it can set:
     * a/ fixed system context
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER; // There are 3 levels. Only this level fits the event (making request).
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('duitku_request_log', 'paygw_duitku');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $descstring = ""; // Initiaize the string that will be returned for the description column.
        foreach ($this->other as $key => $value) {
            $descstring .= "{$key}  : {$value} <br />";
        }
        return $descstring;
    }
}
