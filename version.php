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
 * Theme plugin version definition.
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2023020200; /* This is the version number to increment when changes needing an update are made */
$plugin->requires  = 2019111800;
$plugin->release   = '0.1.0';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->component = 'theme_imtpn';
$plugin->dependencies = [
    'theme_boost' => ANY_VERSION,
    'theme_clboost' => ANY_VERSION,
    'block_group_members' => ANY_VERSION,
    'block_forum_groups' => ANY_VERSION,
    'block_thumblinks_action' => ANY_VERSION,
    'block_enhanced_myoverview' => ANY_VERSION,
    'block_sharing_cart' => ANY_VERSION,
    'block_rss_thumbnails' => ANY_VERSION,
    'block_forum_feed' => ANY_VERSION,
    'block_mcms' => ANY_VERSION,
    'block_featured_courses' => ANY_VERSION,
    'local_syllabus' => ANY_VERSION,
    'local_resourcelibrary' => ANY_VERSION,
];
