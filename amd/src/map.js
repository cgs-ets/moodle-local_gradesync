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
define(['jquery', 'core/log'], 
    function($, Log) {    
    'use strict';

    /**
     * Initializes the map component.
     */
    function init() {
        Log.debug('local_gradesync/map: initializing');

        var rootel = $('#local_gradesync-map-root');

        if (!rootel.length) {
            Log.error('local_gradesync/map: \'#local_gradesync-map-root\' not found!');
            return;
        }

        var map = new Map(rootel);
        map.main();
    }


    /**
     * The constructor
     *
     * @constructor
     * @param {jQuery} rootel
     */
    function Map(rootel) {
        var self = this;
        self.rootel = rootel;
    }

    /**
     * Run the map js.
     *
     */
    Map.prototype.main = function () {
        var self = this;

        // Initialise mapping fields.
        self.layout();

        // Detect class change.
        self.rootel.on('change', '#assessment-class', function(e) {
            e.preventDefault();
            self.layout();
        });

        // Detect mapping change.
        self.rootel.on('change', '.gradeitems', function(e) {
            e.preventDefault();
            var select = $(this);
            self.saveMapping(select);
        });

    };

    /**
     * Load the mapping layout.
     *
     */
    Map.prototype.layout = function () {
        var self = this;

        self.loading();

        // Hide/show the relevant class mappings.
        var aclass = $('#assessment-class').val();
        $('.assessment').hide();
        $('.assessment[data-class="' + aclass + '"]').show();

        self.loading(1);
    };

    /**
     * Load the mapping layout.
     *
     */
    Map.prototype.saveMapping = function (select) {
        var self = this;

        var assessment = select.closest('.assessment');

        self.submitting(assessment);

        var id = select.data('id');
        var aclass = select.data('class');
        Log.debug(id);
        Log.debug(aclass);


        self.submitting(assessment, 1);
    };

    /**
     * Load the mapping layout.
     *
     */
    Map.prototype.loading = function (finished) {
        var self = this;

        if (finished) {
            setTimeout(function() {
                self.rootel.removeClass('loading');
            }, 200);
        } else {
            self.rootel.addClass('loading');
        }
    };

    /**
     * Load the mapping layout.
     *
     */
    Map.prototype.submitting = function (assessment, finished) {
        var self = this;

        if (finished) {
            setTimeout(function() {
                //assessment.find('#submitspinner-' + assessment.data('id')).remove();
                self.rootel.removeClass('submitting');
            }, 200);
        } else {
            self.rootel.addClass('submitting');
            assessment.append('<div id="submitspinner-' + assessment.data('id') + '" class="spinner"><div class="circle spin"></div></div>');
        }
    };

    return {
        init: init
    };
});