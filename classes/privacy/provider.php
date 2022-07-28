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
 * Privacy Subsystem implementation for paygw_duitku plugin
 * @package   paygw_duitku
 * @copyright 2022 Michael David <mikedh2612@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_duitku\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for paygw_duitku plugin.
 *
 * @copyright   2022 Michael David <mikedh2612@gmail.com>
 * @copyright   based on work by 2018 Shamim Rezaie <shamim@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // Duitku Stores user data
        \core_privacy\local\metadata\provider,

        // The Duitku enrolment plugin contains user's transactions.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        // Data may be exported to an external location.
        $collection->add_external_location_link(
            'duitku.com',
            [
                'merchantcode'              => 'privacy:metadata:paygw_duitku:duitku_com:merchantcode',
                'apikey'                    => 'privacy:metadata:paygw_duitku:duitku_com:apikey',
                'signature'                 => 'privacy:metadata:paygw_duitku:duitku_com:signature',
                'merchant_order_id'         => 'privacy:metadata:paygw_duitku:duitku_com:merchant_order_id',
                'paymentAmount'             => 'privacy:metadata:paygw_duitku:duitku_com:paymentAmount',
                'username'                  => 'privacy:metadata:paygw_duitku:duitku_com:username',
                'first_name'                => 'privacy:metadata:paygw_duitku:duitku_com:first_name',
                'last_name'                 => 'privacy:metadata:paygw_duitku:duitku_com:last_name',
                'address'                   => 'privacy:metadata:paygw_duitku:duitku_com:address',
                'city'                      => 'privacy:metadata:paygw_duitku:duitku_com:city',
                'email'                     => 'privacy:metadata:paygw_duitku:duitku_com:email',
                'country'                   => 'privacy:metadata:paygw_duitku:duitku_com:country',
            ],
            'privacy:metadata:paygw_duitku:duitku_com'
        );

        // The paygw_duitku has a database table that contains user data.
        $collection->add_database_table(
            'paygw_duitku',
            [
                'userid' => 'privacy:metadata:paygw_duitku:paygw_duitku:userid',
                'component' => 'privacy:metadata:paygw_duitku:paygw_duitku:component',
                'paymentarea' => 'privacy:metadata:paygw_duitku:paygw_duitku:paymentarea',
                'itemid' => 'privacy:metadata:paygw_duitku:paygw_duitku:itemid',
                'reference' => 'privacy:metadata:paygw_duitku:paygw_duitku:reference',
                'timestamp' => 'privacy:metadata:paygw_duitku:paygw_duitku:timestamp',
                'signature' => 'privacy:metadata:paygw_duitku:paygw_duitku:signature',
                'merchant_order_id' => 'privacy:metadata:paygw_duitku:paygw_duitku:merchant_order_id',
                'accountid' => 'privacy:metadata:paygw_duitku:paygw_duitku:accountid',
                'payment_status' => 'privacy:metadata:paygw_duitku:paygw_duitku:payment_status',
                'pending_reason' => 'privacy:metadata:paygw_duitku:paygw_duitku:pending_reason',
                'timeupdated' => 'privacy:metadata:paygw_duitku:paygw_duitku:timeupdated',
                'expiryperiod' => 'privacy:metadata:paygw_duitku:paygw_duitku:expiryperiod',
                'referenceurl' => 'privacy:metadata:paygw_duitku:paygw_duitku:referenceurl'
            ],
            'privacy:metadata:paygw_duitku:paygw_duitku'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {paygw_duitku} pd
                  JOIN {enrol} e ON pd.itemid = e.id AND e.enrol = pd.paymentarea
                  JOIN {context} ctx ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                  JOIN {user} u ON u.id = pd.userid
                 WHERE u.id = :userid";
        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid'        => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $sql = "SELECT u.id
                  FROM {paygw_duitku} pd
                  JOIN {enrol} e ON pd.itemid = e.id AND e.enrol = pd.paymentarea
                  JOIN {user} u ON u.id = pd.userid
                 WHERE e.courseid = :courseid";
        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('id', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT pd.*, e.courseid
                  FROM {paygw_duitku} pd
                  JOIN {enrol} e ON pd.itemid = e.id AND e.enrol = pd.paymentarea
                  JOIN {context} ctx ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                  JOIN {user} u ON u.id = pd.userid
                 WHERE ctx.id {$contextsql} AND u.id = :userid
              ORDER BY e.courseid";

        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid'        => $user->id,
        ];
        $params += $contextparams;

        $lastcourseid = null;

        $strtransactions = get_string('transactions', 'paygw_duitku');
        $transactions = [];
        $duitkurecords = $DB->get_recordset_sql($sql, $params);
        foreach ($duitkurecords as $duitkurecord) {
            if ($lastcourseid != $duitkurecord->courseid) {
                if (!empty($transactions)) {
                    $coursecontext = \context_course::instance($duitkurecord->courseid);
                    writer::with_context($coursecontext)->export_data(
                            [$strtransactions],
                            (object) ['transactions' => $transactions]
                    );
                }
                $transactions = [];
            }

            $transaction = (object) [
                'userid' => $duitkurecord->userid,
                'component' => $duitkurecord->component,
                'paymentarea' => $duitkurecord->paymentarea,
                'itemid' => $duitkurecord->itemid,
                'reference' => $duitkurecord->reference,
                'timestamp' => $duitkurecord->timestamp,
                'signature' => $duitkurecord->signature,
                'merchant_order_id' => $duitkurecord->merchant_order_id,
                'accountid' => $duitkurecord->accountid,
                'payment_status' => $duitkurecord->payment_status,
                'pending_reason' => $duitkurecord->pending_reason,
                'timeupdated' => $duitkurecord->timeupdated,
                'expiryperiod' => $duitkurecord->expiryperiod,
                'referenceurl' => $duitkurecord->referenceurl
            ];
            if ($duitkurecord->userid == $user->id) {
                $transaction->userid = $duitkurecord->userid;
            }
            $transactions[] = $duitkurecord;

            $lastcourseid = $duitkurecord->courseid;
        }
        $duitkurecord->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($transactions)) {
            $coursecontext = \context_course::instance($duitkurecord->courseid);
            writer::with_context($coursecontext)->export_data(
                    [$strtransactions],
                    (object) ['transactions' => $transactions]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }
        $sql = "SELECT pd.*
        FROM {paygw_duitku} pd
        JOIN {enrol} e ON pd.itemid = e.id AND e.enrol = pd.paymentarea
        WHERE e.courseid = :courseid";
        $duitkurecord = $DB->get_record_sql($sql, array('courseid' => $context->instanceid));
        $DB->delete_records('paygw_duitku', array('itemid' => $duitkurecord->itemid));
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        $contexts = $contextlist->get_contexts();
        $courseids = [];
        foreach ($contexts as $context) {
            if ($context instanceof \context_course) {
                $courseids[] = $context->instanceid;
            }
        }

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $select = "userid = :userid AND itemid = ANY (SELECT pd.itemid
        FROM mdl_paygw_duitku AS pd
        JOIN mdl_enrol AS e ON pd.itemid = e.id AND e.enrol = pd.paymentarea
        WHERE e.courseid $insql)";
        $params = $inparams + ['userid' => $user->id];
        $DB->delete_records_select('paygw_duitku', $select, $params);

        // We do not want to delete the payment record when the user is just the receiver of payment.
        // In that case, we just delete the receiver's info from the transaction record.

        $DB->set_field_select('paygw_duitku', 'accountid', '', $select, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = ['courseid' => $context->instanceid] + $userparams;

        $select = "userid $usersql AND itemid = ANY (SELECT pd.itemid
        FROM mdl_paygw_duitku AS pd
        JOIN mdl_enrol AS e ON pd.itemid = e.id AND e.enrol = pd.paymentarea
        WHERE e.courseid = :courseid)";
        $DB->delete_records_select('paygw_duitku', $select, $params);

        // We do not want to delete the payment record when the user is just the receiver of payment.
        // In that case, we just delete the receiver's info from the transaction record.

        $DB->set_field_select('paygw_duitku', 'accountid', '', $select, $params);
    }
}
