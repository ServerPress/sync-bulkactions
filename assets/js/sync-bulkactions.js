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
	this.post_list = null;
	this.post_idx = 0;
}

/**
 * Initializes the page, adding buttons and setting up checkboxes based on content of Notice.
 */
WPSiteSyncContent_BulkActions.prototype.init = function()
{
console.log('BulkActions.init()');
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
	e.preventDefault();
	jQuery('#bulk-action-selector-top').val('bulk_push');
	this.post_list = this.get_post_list();
console.log('bulk.push() post list:');
console.log(this.post_list);
	if (0 === this.post_list.length) {
		this.close_ui();
		this.set_message(jQuery('#sync-bulkactions-msg-no-selection').html())
	} else {
		this.setup_ui();
		wpsitesynccontent.set_api_callback(wpsitesynccontent.bulkactions.push_next);
		this.post_idx = -1;
		this.push_next(0, true, {});

/*		var msg = jQuery('#sync-bulkactions-msg-pushing').html();
		this.post_idx = 0;
		for (this.post_idx = 0; this.post_idx < this.post_list.length; this.post_idx++) {
			this.set_progress(idx, posts.length);
			var pushing = msg.replace('~1', idx + 1)
				.replace('~2', posts.length);
			this.set_message(pushing);
			wpsitesynccontent.push(posts[idx]);
			// TODO: check for errors
		} */
	}
//	jQuery('#doaction').click();
};

WPSiteSyncContent_BulkActions.prototype.push_next = function(post_id, success, response)
{
console.log('bulk.push_next() success=' + (success ? 'true' : 'false'));
console.log(response);
	var self = wpsitesynccontent.bulkactions;
	if (success) {
		// first check for errors
		if ('undefined' !== typeof(response.has_errors) && 1 === response.has_errors) {
			self.set_message(response.error_message);
			self.post_idx = self.post_list.length;
			return;
		}
		// successful- increment index and push again
		self.post_idx++;
console.log('bulk.push_next() idx=' + self.post_idx);
console.log(self.post_list);
		if (self.post_idx === self.post_list.length) {
			self.set_message(jQuery('#sync-bulkactions-msg-complete').html());
			return;
		}

		var msg = jQuery('#sync-bulkactions-msg-pushing').html();
		self.set_progress(self.post_idx, self.post_list.length);
		var pushing = msg.replace('~1', self.post_idx + 1)
				.replace('~2', self.post_list.length);
		self.set_message(pushing);
		wpsitesynccontent.push(self.post_list[self.post_idx]);
	} else {
		// got an error, set the message and don't continue
		self.set_message(response.error_message);
	}
}

/**
 * Handler for Pull button clicks
 * @param {boolean} enabled True if Pull is enabled; otherwise False
 */
WPSiteSyncContent_BulkActions.prototype.pull = function(enabled)
{
	if (enabled) {
		jQuery('#bulk-action-selector-top').val('bulk_pull');
		jQuery('#doaction').click();
	} else {
		this.set_message(jQuery('#sync-bulkactions-msg-activate-pull').html());
//		jQuery('.sync-bulkactions-msgs').show();
	}
};


WPSiteSyncContent_BulkActions.prototype.get_post_list = function()
{
	var post_list = Array();
	var items = jQuery('#the-list input[name*="post"]').each(function() {
//		if (jQuery(this).attr('checked'))
		if (jQuery(this).is(':checked'))
			post_list.push(jQuery(this).val());
	});
console.log('BulkActions.get_post_list() items:');
console.log(post_list);
	return post_list;
};

WPSiteSyncContent_BulkActions.prototype.setup_ui = function()
{
	this.clear_message();
	jQuery('#spectrom_sync .sync-bulkactions-indicator').css('width', '0');
	jQuery('#spectrom_sync .percent').text('0');
	jQuery('#spectrom_sync .sync-bulkactions-ui').show();
};

WPSiteSyncContent_BulkActions.prototype.set_progress = function(item, count)
{
	var pcnt = Math.floor(((item + 1) * 100) / count);
	jQuery('#spectrom_sync .sync-bulkactions-indicator').css('width', pcnt + '%');
	jQuery('#spectrom_sync .percent').text(pcnt + '');
};

WPSiteSyncContent_BulkActions.prototype.close_ui = function()
{
	jQuery('#spectrom_sync .sync-bulkactions-ui').hide();
};

WPSiteSyncContent_BulkActions.prototype.set_message = function(msg)
{
console.log('set_message() "' + msg + '"');
	jQuery('.sync-bulkactions-msg').html(msg);
	jQuery('.sync-bulkactions-msg').show();
};

WPSiteSyncContent_BulkActions.prototype.clear_message = function()
{
	jQuery('.sync-bulkactions-msg').hide();
};

wpsitesynccontent.bulkactions = new WPSiteSyncContent_BulkActions();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function()
{
	wpsitesynccontent.bulkactions.init();

	jQuery('.sync-bulkactions-push').on('click', function(e) {
		wpsitesynccontent.bulkactions.push(e);
	});
});
