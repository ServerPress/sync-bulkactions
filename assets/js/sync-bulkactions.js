/*
 * @copyright Copyright (C) 2015-2016 SpectrOMtech.com. - All Rights Reserved.
 * @author SpectrOMtech.com <hello@SpectrOMtech.com>
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @url https://WPSiteSync.com
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the SpectrOMtech Proprietary Use License v1.0
 * More info at https://WPSiteSync.com
 */

function WPSiteSyncContent_BulkActions()
{
}

/**
 * Initializes the page, adding buttons and setting up checkboxes based on content of Notice.
 */
WPSiteSyncContent_BulkActions.prototype.init = function ()
{
    jQuery.each(syncbulkactions.actions, function(i) {
        jQuery('#bulk-action-selector-top').append('<option value="' + syncbulkactions.actions[i].action_name + '">' + syncbulkactions.actions[i].action_text + '</option>');
        jQuery('#bulk-action-selector-bottom').append('<option value="' + syncbulkactions.actions[i].action_name + '">' + syncbulkactions.actions[i].action_text + '</option>');
    });

    if (jQuery('.notice').hasClass('wpsitesync-bulk-errors')) {
        var ids = jQuery('.notice.wpsitesync-bulk-errors').data('errorIds').toString(),
            id_array = ids.split(',');

        jQuery.each(id_array, function(i, value) {
            jQuery('#cb-select-' + value).prop('checked', true);
        });
    }

    jQuery('#post-query-submit').after(jQuery('#sync-bulkactions-ui').html());
};

/**
 * Handler for Push button clicks
 * @param {event} e The event object for the button click
 */
WPSiteSyncContent_BulkActions.prototype.push = function(e)
{
    jQuery('#bulk-action-selector-top').val('bulk_push');
    jQuery('#doaction').click();
};

/**
 * Handler for Pull button clicks
 * @param {boolean} enabled True if Pull is enabled; otherwise False
 */
WPSiteSyncContent_BulkActions.prototype.pull = function (enabled)
{
    if (true === enabled) {
        jQuery('#bulk-action-selector-top').val('bulk_pull');
        jQuery('#doaction').click();
    } else {
        jQuery('.sync-bulkactions-msgs').show();
    }
};

wpsitesynccontent.bulkactions = new WPSiteSyncContent_BulkActions();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function()
{
    wpsitesynccontent.bulkactions.init();

    jQuery('.sync-bulkactions-push').on('click', function() {
        wpsitesynccontent.bulkactions.push();
    });
});
