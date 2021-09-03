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

namespace theme_roc\output\core;

use moodle_url;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_roc
 * @copyright  2021 Peter Meint Heida
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \core_renderer {

    public function edit_button(moodle_url $url) {
        $url->param('sesskey', sesskey());
        if ($this->page->user_is_editing()) {
            $url->param('edit', 'off');
            $editstring = get_string('turneditingoff');
        } else {
            $url->param('edit', 'on');
            $editstring = get_string('turneditingon');
        }
        $button = new \single_button($url, $editstring, 'post', ['class' => 'btn btn-primary']);
        return $this->render_single_button($button);
    }

}

require_once($CFG->dirroot.'/course/renderer.php');

use cm_info;
use core_availability\info;
use core_text;
use context_course;
use coursecat_helper;
use lang_string;
use course_in_list;
use renderable;
use action_link;
use stdClass;
use pix_icon;
use html_writer;
class course_renderer extends \core_course_renderer {
//    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
//    }
    public function course_section_cm_name_title(cm_info $mod, $displayoptions = array()) {
        $output = '';
        $url = $mod->url;
        if (!$mod->is_visible_on_course_page() || !$url) {
            // Nothing to be displayed to the user.
            return $output;
        }

        //Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = $mod->get_formatted_name();
        $altname = $mod->modfullname;
        // Avoid unnecessary duplication: if e.g. a forum name already
        // includes the word forum (or Forum, etc) then it is unhelpful
        // to include that in the accessible description that is added.
        if (false !== strpos(core_text::strtolower($instancename),
                core_text::strtolower($altname))) {
            $altname = '';
        }
        // File type after name, for alphabetic lists (screen reader).
        if ($altname) {
            $altname = get_accesshide(' '.$altname);
        }

        list($linkclasses, $textclasses) = $this->course_section_cm_classes($mod);

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

        $pix_name = '';
        global $DB;
        $result = $DB->get_record_sql('select t.name
                                   from mdl_tag as t,
                                        mdl_tag_instance as ti
                                   where ti.tagid = t.id
                                   and ti.itemtype = \'course_modules\'
                                   and ti.itemid = ?
                                   and t.name IN (  \'introductie\',
                                                    \'kennistest\',
                                                    \'online\',
                                                    \'podcast\',
                                                    \'praktijkafsluiting\',
                                                    \'praktijkleerplan\',
                                                    \'praktijkscan\',
                                                    \'praktijkvragen\',
                                                    \'theorie\',
                                                    \'theorie_en_oefenvragen\')',
            array($mod->id));
        if(!empty($result)) {
            $return_values = new stdClass();
            switch (strtolower($result->name)) {
                case 'voorbereiding_praktijkscan' :
                case 'praktijkscan' :
                case 'kennistest' :
                case 'praktijkafsluiting' :
                case 'praktijkleerplan' :
                case 'praktijkvragen' :
                case 'theorie' :
                case 'theorie_en_oefenvragen' :
                case 'podcast' :
                case 'introductie' :
                case 'online' :
                    $pix_name = $result->name;
                    break;
                default: $pix_name = 'icon';
            }
        } else {
            $pix_name = 'default';
        }
        // Display link itself.
        $activitylink = $this->output->render(new pix_icon($pix_name, '', 'theme_roc', array('title' => $instancename, 'class' => 'roc_mod_icon'))) .
            html_writer::tag('span', $instancename . $altname, array('class' => 'instancename'));
        if ($mod->uservisible) {
            $output .= html_writer::link($url, $activitylink, array('class' => 'aalink' . $linkclasses, 'onclick' => $onclick));
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->is_visible_on_course_page()).
            $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses));
        }
        return $output;
    }
}