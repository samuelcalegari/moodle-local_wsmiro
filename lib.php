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
 * @package    local_wsmiro
 * @copyright  2020 - 2021 Université de Perpignan (https://www.univ-perp.fr)
 * @author     Samuel Calegari <samuel.calegari@univ-perp.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_wsmiro_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $mstatsUrl = $CFG->wwwroot . '/mstats/autologin';
    $url = new moodle_url($mstatsUrl, array('email' => $user->email, 'username'=> $user->username));
    $string = get_string('mstats', 'local_wsmiro') . ' *';
    $node = new core_user\output\myprofile\node('miscellaneous', 'mstats', $string, null, $url);
    $tree->add_node($node);
}