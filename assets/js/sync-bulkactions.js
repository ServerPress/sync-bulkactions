/*
 * @copyright Copyright (C) 2015 SpectrOMtech.com. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author SpectrOMtech.com <hello@SpectrOMtech.com>
 * @url https://www.SpectrOMtech.com/products/
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the SpectrOMtech Proprietary Use License v1.0
 * More info at https://SpectrOMtech.com/products/
 */

function WPSiteSyncContent_BulkActions()
{
}

/**
 * Init
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
};

wpsitesynccontent.bulkactions = new WPSiteSyncContent_BulkActions();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function ()
{
    wpsitesynccontent.bulkactions.init();
});