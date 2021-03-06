<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Pramith Dayananda <pramithd@catalyst.net.nz>
 * @package mod_confidence
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

header('Content-type: application/json');
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/confidence/lib.php');
require_once($CFG->dirroot .'/totara/core/js/lib/setup.php');
require_once("{$CFG->libdir}/completionlib.php");

//define('AJAX_SCRIPT', true);
$level = required_param('level', PARAM_TEXT);
$instance = required_param('instance', PARAM_TEXT);

$confidence  = $DB->get_record('confidence', array('id' => $instance), '*', MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $confidence->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('confidence', $confidence->id, $course->id, false, MUST_EXIST);

if(!$instance) {
    exit;
}

$PAGE->set_url('/mod/confidence/confidence.php', array('level'=>$level, 'instance' => $instance));

/* Availability of the record */
$row = $DB->get_record('confidence_record', array('confidenceid' => intval($instance)));

if($_POST) {
    $level= $_POST['confidence_'.$instance];
}

$record = new stdClass();
$record->confidenceid = intval($instance);
$record->level = intval($level);
$record->userid = intval($USER->id);
$record->timecreated = time();

if ($row) {
    $record->id = $row->id;
    $DB->update_record('confidence_record', $record);
} else {
    $DB->insert_record('confidence_record', $record);
    // Update completion state
    $completion=new completion_info($course);
    if($completion->is_enabled($cm) && $confidence->completiontrack) {
        $completion->update_state($cm,COMPLETION_COMPLETE, intval($USER->id));
    }
}

// Return to previous page
$referer = get_local_referer(false);
if (!empty($referer)) {
    redirect($referer);
} else {
    redirect('view.php?id='.$course->id);
}
