<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_learningmap;

use DOMDocument;

/**
 * Class for handling the content of the learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mapworker {
    /**
     * @var $dom DOMDocument for parsing the SVG
     */
    protected $dom;
    /**
     * @var $svgcode String containing the SVG code (synchronized with $dom)
     */
    protected $svgcode;
    /**
     * @var $placestore Array containing the placestore
     */
    protected $placestore;
    /**
     * @var $prepend String to prepend to the SVG code (for parsing by DOMDocument)
     */
    protected $prepend;

    /**
     * Creates mapworker from SVG code
     *
     * @param string $svgcode
     * @param array $placestore
     */
    public function __construct(string $svgcode, array $placestore) {
        global $CFG;
        $this->svgcode = $svgcode;
        $this->placestore = $placestore;
        // This fixes a problem for loading SVG DTD on Windows locally.
        if (strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0) {
            $dtd = '' . new \moodle_url('/mod/learningmap/pix/svg11.dtd');
        } else {
            $dtd = $CFG->dirroot . '/mod/learningmap/pix/svg11.dtd';
        }
        $this->prepend = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "' . $dtd . '">';

        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->validateOnParse = true;
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        $this->load_dom();
    }

    /**
     * Loads the code from svgcode attribute for DOM processing
     *
     * @return void
     */
    public function load_dom() : void {
        $this->remove_tags_before_svg();
        $this->dom->loadXML($this->prepend . $this->svgcode);
    }

    /**
     * Replaces the stylesheet with a new one generated from placestore
     *
     * @param array $placestoreoverride array of overrides for placestore
     * @return void
     */
    public function replace_stylesheet(array $placestoreoverride = []) : void {
        global $OUTPUT;
        $this->placestore_local = array_merge($this->placestore, $placestoreoverride);
        $this->svgcode = preg_replace(
            '/<style[\s\S]*style>/i',
            $OUTPUT->render_from_template('mod_learningmap/cssskeleton', $this->placestore_local),
            $this->svgcode
        );
        $this->load_dom();
    }

    /**
     * Removes tags before the SVG tag to avoid parsing problems
     *
     * @return void
     */
    public function remove_tags_before_svg() : void {
        $remove = ['<?xml version="1.0"?>', $this->prepend];
        $this->svgcode = str_replace($remove, '', $this->svgcode);
    }

    /**
     * Process the map to show / hide paths and places
     * @param cm_info $cm
     * @return void
     */
    public function process_map_objects(\cm_info $cm) : void {
        global $USER;
        $active = [];
        $completedplaces = [];
        $notavailable = [];

        $completion = new \completion_info($cm->get_course());

        $modinfo = get_fast_modinfo($cm->get_course(), $USER->id);

        $allcms = array_keys($modinfo->get_cms());

        // Walk through all places in the map.
        foreach ($this->placestore['places'] as $place) {
            // Get the id of the link in the DOM.
            $link = $this->dom->getElementById($place['linkId']);
            // Only if the place is linked to an activity.
            if ($place['linkedActivity'] != null) {
                // Only get modinfo for the activity if it is in the array of course module ids.
                if (in_array($place['linkedActivity'], $allcms)) {
                    $placecm = $modinfo->get_cm($place['linkedActivity']);
                } else {
                    $placecm = false;
                }
                // If the activity is not found or if there is no activity, add it to the list of not availabile places.
                // Remove the place completely from the map.
                if (!$placecm) {
                    $notavailable[] = $place['id'];
                    $link->parentNode->removeChild($link);
                } else {
                    // Set the link URL in the map.
                    if ($link) {
                        $link->setAttribute(
                            'xlink:href',
                            new \moodle_url('/mod/' . $placecm->modname . '/view.php', ['id' => $placecm->id])
                        );
                        // Set the title element for the link (for accessibility) and for a tooltip when hovering
                        // the link.
                        $title = $this->dom->getElementById('title' . $place['id']);
                        if ($title) {
                            $title->nodeValue =
                                $placecm->get_formatted_name() .
                                (
                                    // Add info to target places (for accessibility).
                                    in_array($place['id'], $this->placestore['targetplaces']) ?
                                    ' (' . get_string('targetplace', 'learningmap') . ')' :
                                    ''
                                );
                        }
                    }
                    // If the place is a starting place, add it to the active places.
                    if (in_array($place['id'], $this->placestore['startingplaces'])) {
                        $active[] = $place['id'];
                    }
                    // If the activity linked to the place is already completed, add it to the completed
                    // and to the active places.
                    if ($completion->get_data($placecm, true, $USER->id)->completionstate > 0) {
                        $completedplaces[] = $place['id'];
                        $active[] = $place['id'];
                    }
                    // Places that are not accessible (e.g. because of additional availability restrictions)
                    // are not shown on the map.
                    if (!$placecm->available) {
                        $notavailable[] = $place['id'];
                    }
                }
                // If the place is not linked to an activity it is not availabile.
            } else {
                $notavailable[] = $place['id'];
                $link->parentNode->removeChild($link);
            }
        }
        foreach ($this->placestore['paths'] as $path) {
            // If the ending of the path is a completed place and this place is availabile,
            // show path and the place on the other end.
            if (in_array($path['sid'], $completedplaces) && !in_array($path['fid'], $notavailable)) {
                // Only set paths visible if hidepaths is not set in placestore.
                if (!$this->placestore['hidepaths']) {
                    $active[] = $path['id'];
                }
                $active[] = $path['fid'];
            }
            // If the beginning of the path is a completed place and this place is availabile,
            // show path and the place on the other end.
            if (in_array($path['fid'], $completedplaces) && !in_array($path['sid'], $notavailable)) {
                // Only set paths visible if hidepaths is not set in placestore.
                if (!$this->placestore['hidepaths']) {
                    $active[] = $path['id'];
                }
                $active[] = $path['sid'];
            }
        }
        // Set all active paths and places to visible.
        foreach ($active as $a) {
            $domplace = $this->dom->getElementById($a);
            if ($domplace) {
                $domplace->setAttribute('style', 'visibility: visible;');
            }
        }
        // Make all completed places visible and set color for visited places.
        foreach ($completedplaces as $place) {
            $domplace = $this->dom->getElementById($place);
            if ($domplace) {
                $domplace->setAttribute('style', 'visibility: visible; fill: ' . $this->placestore['visitedcolor'] . ';');
                // If the option "usecheckmark" is selected, add the checkmark to the circle.
                if ($this->placestore['usecheckmark']) {
                    $x = $domplace->getAttribute('cx');
                    $y = $domplace->getAttribute('cy');
                    $use = $this->dom->createElement('use');
                    $use->setAttribute('xlink:href', '#checkmark');
                    $use->setAttribute('transform', 'translate(' . $x . ' '. $y . ')');
                    $use->setAttribute('class', 'learningmap-checkmark');
                    $domplace->parentNode->appendChild($use);
                }
            }
        }
        // Make all places hidden if they are not availabile.
        foreach ($notavailable as $place) {
            $domplace = $this->dom->getElementById($place);
            if ($domplace) {
                $domplace->setAttribute('style', 'visibility: hidden;');
            }
        }
        $this->svgcode = $this->dom->saveXML();
    }

    /**
     * Returns the current svg code
     *
     * @return string
     */
    public function get_svgcode(): string {
        return $this->svgcode;
    }
}
