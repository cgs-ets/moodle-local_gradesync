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
 * Provides the local_gradesync/map module
 *
 * @package   local_gradesync
 * @category  output
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module local_gradesync/map
 */
define(['jquery', 'core/log', 'core/ajax'], 
    function($, Log, Ajax) {    
    'use strict';

    /**
     * Initializes the map component.
     */
    function init(courseid) {
        Log.debug('local_gradesync/map: initializing');

        var rootel = $('#local_gradesync-map-root');

        if (!rootel.length) {
            Log.error('local_gradesync/map: \'#local_gradesync-map-root\' not found!');
            return;
        }

        var map = new Map(rootel, courseid);
        map.main();
    }

    /**
     * The constructor
     *
     * @constructor
     * @param {jQuery} rootel
     */
    function Map(rootel, courseid) {
        var self = this;
        self.rootel = rootel;
        self.courseid = courseid;
    }

    /**
     * Run the map js.
     *
     */
    Map.prototype.main = function () {
        var self = this;

        // Initialise UI.
        self.changeClass();
        self.changeOverrideGroup();
        self.initMappedTo();

        // Detect class change.
        self.rootel.on('change', '#assessment-class', function(e) {
            e.preventDefault();
            self.changeClass();
        });

        // Add the selected value as an attr so that we can apply styles to the select.
        //self.rootel.find('.gradeitems').each( function(i) {
        //    $(this).attr('data-selected', select.val());
        //});

        // Detect mapping change.
        self.rootel.on('change', '.gradeitems', function(e) {
            e.preventDefault();
            var select = $(this);
            self.saveMapping(select);
        });

        // Detect group override change.
        self.rootel.on('change', '#override-group', function(e) {
            e.preventDefault();
            self.changeOverrideGroup();
        });

        self.checkAlerts();

    };

    /**
     * Load the mapping fields based on selected class.
     *
     */
    Map.prototype.changeClass = function () {
        var self = this;

        // Hide/show the relevant class mappings.
        self.loading(self.rootel);
        self.rootel.find('.mappings-wrap').hide();
        self.rootel.find('.assessment').hide();
        var aclass = self.rootel.find('#assessment-class').val();
        if (aclass && aclass != -1) {
            self.rootel.find('.assessment[data-class="' + aclass + '"]').show();
            self.rootel.find('.mappings-wrap').show();
        }
        self.loading(self.rootel, 1);
        // Reload group mappings.
        self.changeOverrideGroup();
    };

    /**
     * Load the mapping fields based on selected group.
     *
     */
    Map.prototype.changeOverrideGroup = function () {
        var self = this;

        // Hide/show the relevant group mappings.
        var areael = $('.overrides');
        self.loading(areael);
        self.rootel.find('.overrides .mappings').hide();
        self.rootel.find('.overrides .assessment').hide();
        var aclass = self.rootel.find('#assessment-class').val();
        var groupid = self.rootel.find('#override-group').val();
        if (aclass && groupid && aclass != -1 && groupid != -1) {
            self.rootel.find('.overrides .assessment[data-groupid="' + groupid + '"][data-class="' + aclass + '"]').show();
            self.rootel.find('.overrides .mappings').show();
        }
        self.loading(areael, 1);
    };

    /**
     * Initialise the selected mappings.
     *
     */
    Map.prototype.initMappedTo = function () {
        var self = this;

        self.rootel.find('.assessment').each(function() {
            var mappedto = $(this).data('mappedto');
            $(this).find('.gradeitems').val(mappedto).change();
        });
    };        

    /**
     * Load the mapping layout.
     *
     */
    Map.prototype.saveMapping = function (select) {
        var self = this;

        var assessment = select.closest('.assessment');

        self.submitting(assessment);

        var assessmentid = assessment.data('id');
        var assessmentclass = assessment.data('class');
        var courseid = self.courseid;
        var groupid = assessment.data('groupid');
        var gradeitem = select.val();

        Ajax.call([{
            methodname: 'local_gradesync_save_mapping',
            args: { 
                externalclass: assessmentclass,
                externalgradeid: assessmentid,
                courseid: courseid,
                groupid: groupid,
                gradeitemid: gradeitem
            },
            done: function(response) {
                // Update the mappedto data attr.
                assessment.data('mappedto', gradeitem);
                assessment.attr('data-mappedto', gradeitem);
                self.submitting(assessment, 1, 1);
                
                // Check the markoutof.
                self.checkAlerts();
            },
            fail: function(reason) {
                self.submitting(assessment, 1, 2);
                Log.error('local_gradesync/map: failed to save mapping.');
                Log.debug(reason);
            }
        }]);

    };

    /**
     * Check the markoutof values.
     *
     */
    Map.prototype.checkAlerts = function (select) {
        var self = this;

        var assessments = self.rootel.find('.assessment');
        assessments.each(function() {

            var assessment = $(this);

            // Remove the flag initially.
            assessment.find('.alerts').html('');

            // Check if mapped.
            var mappedto = assessment.data('mappedto');
            if (mappedto == '-1') {
                console.log('not mapped...');
                return false;
            }

            // Get the mapped option.
            var option = assessment.find('option[value="' + mappedto + '"]');
            
            // Check the mark out of.
            if (assessment.data('markoutof') != option.data('markoutof')) {
                assessment.find('.alerts').append('<i class="fa fa-exclamation-triangle" aria-hidden="true" data-toggle="tooltip" title="\'Markoutof\' values do not match. Grades will not be synced."></i>');
            }

        });

    }

    /**
     * Show/hide the loading state
     *
     */
    Map.prototype.loading = function (el, finished) {
        var self = this;

        if (finished) {
            setTimeout(function() {
                el.removeClass('loading');
            }, 200);
        } else {
            el.addClass('loading');
        }
    };

    /**
     * Show/hide the submitting state
     *
     */
    Map.prototype.submitting = function (assessment, finished, result) {
        var self = this;

        var assessmentid = assessment.data('id');

        if (finished) {
            assessment.find('#submitspinner-' + assessmentid).remove();
            self.rootel.removeClass('submitting');
            if (result == 1) {
                assessment.append('<div style="display: none;" id="result-' + assessmentid + '" class="icon-result icon-result-success"><i class="fa fa-check" aria-hidden="true"></i> Changes saved</div>');
            } else if (result == 2) {
                assessment.append('<div style="display: none;" id="result-' + assessmentid + '" class="icon-result icon-result-error"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error saving changes</div>');
            }
            assessment.find('#result-' + assessmentid).fadeIn(200).delay(2000).fadeOut(200, function() {$(this).remove()});
        } else {
            self.rootel.addClass('submitting');
            assessment.append('<div id="submitspinner-' + assessmentid + '" class="spinner"><div class="circle spin"></div></div>');
        }
    };

    return {
        init: init
    };
});