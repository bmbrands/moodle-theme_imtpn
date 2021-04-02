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
 * Mur pedagogique routines
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn;

use mod_forum\grades\forum_gradeitem;
use mod_forum\local\container;
use mod_forum\local\renderers\discussion_list as discussion_list_renderer;
use moodle_url;
use theme_imtpn\local\forum\discussion_list_mur_pedago;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mur_pedagogique
 *
 * @package theme_imtpn
 */
class mur_pedagogique {
    /**
     * @param $forum
     * @param $managerfactory
     * @param $legacydatamapperfactory
     * @param $discussionlistvault
     * @param $postvault
     * @param $mode
     * @param $search
     * @param $sortorder
     * @param $pageno
     * @param $pagesize
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function display_page($forum,
        $managerfactory,
        $legacydatamapperfactory,
        $discussionlistvault,
        $postvault,
        $mode,
        $search,
        $sortorder,
        $pageno,
        $pagesize) {
        global $PAGE, $OUTPUT, $CFG, $SESSION, $USER;
        $urlfactory = container::get_url_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($forum);

        $url = $urlfactory->get_forum_view_url_from_forum($forum);
        $PAGE->set_url($url);

        $course = $forum->get_course_record();
        $coursemodule = $forum->get_course_module_record();
        $cm = \cm_info::create($coursemodule);

        require_course_login($course, true, $cm);

        $istypesingle = $forum->get_type() === 'single';
        $saveddisplaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);

        if ($mode) {
            $displaymode = $mode;
        } else {
            $displaymode = $saveddisplaymode;
        }

        if (get_user_preferences('forum_useexperimentalui', false)) {
            if ($displaymode == FORUM_MODE_NESTED) {
                $displaymode = FORUM_MODE_NESTED_V2;
            }
        } else {
            if ($displaymode == FORUM_MODE_NESTED_V2) {
                $displaymode = FORUM_MODE_NESTED;
            }
        }

        if ($displaymode != $saveddisplaymode) {
            set_user_preference('forum_displaymode', $displaymode);
        }

        $PAGE->set_context($forum->get_context());
        $PAGE->set_title($forum->get_name());
        $PAGE->add_body_class('forumtype-' . $forum->get_type());
        $PAGE->set_heading($course->fullname);
        $PAGE->set_button(forum_search_form($course, $search));
        $viewallgroups = $OUTPUT->single_button(
            new moodle_url('/theme/imtpn/pages/groupoverview.php'),
            get_string('allgroups', 'theme_imtpn'));
        $PAGE->set_button($viewallgroups. forum_search_form($course, $search));
        if ($istypesingle && $displaymode == FORUM_MODE_NESTED_V2) {
            $PAGE->add_body_class('nested-v2-display-mode reset-style');
            $settingstrigger = $OUTPUT->render_from_template('mod_forum/settings_drawer_trigger', null);
            $PAGE->add_header_action($settingstrigger);
        }

        if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $forum->get_context())) {
            redirect(
                $urlfactory->get_course_url_from_forum($forum),
                get_string('activityiscurrentlyhidden'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        if (!$capabilitymanager->can_view_discussions($USER)) {
            redirect(
                $urlfactory->get_course_url_from_forum($forum),
                get_string('noviewdiscussionspermission', 'forum'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        // Mark viewed and trigger the course_module_viewed event.
        $forumdatamapper = $legacydatamapperfactory->get_forum_data_mapper();
        $forumrecord = $forumdatamapper->to_legacy_object($forum);
        forum_view(
            $forumrecord,
            $forum->get_course_record(),
            $forum->get_course_module_record(),
            $forum->get_context()
        );

        // Return here if we post or set subscription etc.
        $SESSION->fromdiscussion = qualified_me();

        if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $forum->get_rss_type() &&
            $forum->get_rss_articles()) {
            require_once("{$CFG->libdir}/rsslib.php");

            $rsstitle = format_string($course->shortname, true, [
                    'context' => context_course::instance($course->id),
                ]) . ': ' . format_string($forum->get_name());
            rss_add_http_header($forum->get_context(), 'mod_forum', $forumrecord, $rsstitle);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($forum->get_name()), 2);

        if (!$istypesingle && !empty($forum->get_intro())) {
            echo $OUTPUT->box(format_module_intro('forum', $forumrecord, $cm->id), 'generalbox', 'murpedago-intro');
        }

        // Fetch the current groupid.
        $groupid = groups_get_activity_group($cm, true) ?: null;

        $capabilitymanager = $managerfactory->get_capability_manager($forum);
        $notifications = [];

        $exporterfactory = \mod_forum\local\container::get_exporter_factory();
        $vaultfactory = \mod_forum\local\container::get_vault_factory();
        $builderfactory = \mod_forum\local\container::get_builder_factory();

        $discussionsrenderer = new discussion_list_mur_pedago(
            $forum,
            $PAGE->get_renderer('mod_forum'),
            $legacydatamapperfactory,
            $exporterfactory,
            $vaultfactory,
            $builderfactory,
            $capabilitymanager,
            $urlfactory,
            forum_gradeitem::load_from_forum_entity($forum),
            'mod_forum/blog_discussion_list',
            $notifications,
            function($discussions, $user, $forum) use ($capabilitymanager, $builderfactory, $vaultfactory, $legacydatamapperfactory
            ) {
                $exportedpostsbuilder = $builderfactory->get_exported_posts_builder();
                $discussionentries = [];
                $postentries = [];
                foreach ($discussions as $discussion) {
                    $discussionentries[] = $discussion->get_discussion();
                    $discussionentriesids[] = $discussion->get_discussion()->get_id();
                    $postentries[] = $discussion->get_first_post();
                }

                $exportedposts['posts'] = $exportedpostsbuilder->build(
                    $user,
                    [$forum],
                    $discussionentries,
                    $postentries
                );

                $postvault = $vaultfactory->get_post_vault();
                $canseeanyprivatereply = $capabilitymanager->can_view_any_private_reply($user);
                $discussionrepliescount = $postvault->get_reply_count_for_discussion_ids(
                    $user,
                    $discussionentriesids,
                    $canseeanyprivatereply
                );
                $forumdatamapper = $legacydatamapperfactory->get_forum_data_mapper();
                $forumrecord = $forumdatamapper->to_legacy_object($forum);
                if (forum_tp_is_tracked($forumrecord, $user)) {
                    $discussionunreadscount = $postvault->get_unread_count_for_discussion_ids(
                        $user,
                        $discussionentriesids,
                        $canseeanyprivatereply
                    );
                } else {
                    $discussionunreadscount = [];
                }

                array_walk($exportedposts['posts'], function($post) use ($discussionrepliescount, $discussionunreadscount) {
                    $post->discussionrepliescount = $discussionrepliescount[$post->discussionid] ?? 0;
                    $post->discussionunreadscount = $discussionunreadscount[$post->discussionid] ?? 0;
                    // TODO: Find a better solution due to language differences when defining the singular and plural form.
                    $post->isreplyplural = $post->discussionrepliescount != 1 ? true : false;
                    $post->isunreadplural = $post->discussionunreadscount != 1 ? true : false;
                });

                $exportedposts['state']['hasdiscussions'] = $exportedposts['posts'] ? true : false;

                return $exportedposts;
            }
        );

        // Blog forums always show discussions newest first.
        echo $discussionsrenderer->render($USER, $cm, $groupid, $discussionlistvault::SORTORDER_CREATED_DESC,
            $pageno, $pagesize);

        if (!$CFG->forum_usermarksread && forum_tp_is_tracked($forumrecord, $USER)) {
            $discussions = mod_forum_get_discussion_summaries($forum, $USER, null, null, $pageno, $pagesize);
            $firstpostids = array_map(function($discussion) {
                return $discussion->get_first_post()->get_id();
            }, array_values($discussions));
            forum_tp_mark_posts_read($USER, $firstpostids);
        }
        echo $OUTPUT->footer();

    }
}