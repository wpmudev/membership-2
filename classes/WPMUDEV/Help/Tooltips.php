<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Very simple tooltip implementation for admin pages.
 *
 * Example usage:
 * <code>
 * if (!class_exists('WpmuDev_HelpTooltips')) require_once YOUR_PLUGIN_BASE_DIR . '/lib/external/class_wd_help_tooltips.php';
 * $tips = new WpmuDev_HelpTooltips();
 * $tips->set_icon_url("URL_TO_YOUR_ICON");
 * echo $tips->add_tip("Tip 1 text here");
 * // ...
 * echo $tips->add_tip("Tip 2 text here");
 * </code>
 * This is a basic usage scenario.
 *
 * Alternative usage example:
 * <code>
 * if (!class_exists('WpmuDev_HelpTooltips')) require_once YOUR_PLUGIN_BASE_DIR . '/lib/external/class_wd_help_tooltips.php';
 * $tips = new WpmuDev_HelpTooltips();
 * $tips->set_icon_url("URL_TO_YOUR_ICON");
 * $tips->bind_tip('My tip text here', '.icon32:first ~h2');
 * // Note that you don't echo anything in this usage example - the tip will be
 * // added automatically next to the supplied selector (second argument)
 * </code>
 * This scenario may be useful for e.g. adding our tips to UI elements created by WP or other plugins.
 * You can freely alternate between add_tip() and bind_tip() methods,
 * just remember to echo add_tip() and *not* echo bind_tip().
 *
 * Another alternative usage example, setting tips for multiple pages in one place:
 * <code>
 * if (!class_exists('WpmuDev_HelpTooltips')) require_once YOUR_PLUGIN_BASE_DIR . '/lib/external/class_wd_help_tooltips.php';
 * // Tips added to $tips1 object will only show on Social Marketing add/edit advert page
 * $tips1 = new WpmuDev_HelpTooltips();
 * $tips1->set_icon_url("URL_TO_YOUR_ICON");
 * $tips1->set_screen_id("social_marketing_ad");
 * $tips1->bind_tip('My tip 1 text here', '.icon32:first ~h2');
 * // ...
 * // Tips added to $tips2 object will only show on Social Marketing getting started page
 * $tips2 = new WpmuDev_HelpTooltips();
 * $tips2->set_icon_url("URL_TO_YOUR_ICON");
 * $tips2->set_screen_id("social_marketing_ad_page_wdsm-get_started");
 * $tips2->bind_tip('My tip 2 text here', '.icon32:first ~h2');
 * </code>
 * Using add_tip() method will do nothing in this last scenario, as it doesn't make sense in that context.
 * This scenario may be useful for adding tooltips to all pages in one central location.
 * E.g. for adding tooltips in a plugin add-on.
 */
class WPMUDEV_Help_Tooltips {

	/**
	 * Holds an array of inline tips: used as dependency inclusion switch.
	 */
	private $_inline_tips = array();

	/**
	 * Holds an array of bound tips: used as dependency inclusion switch and bound tips buffer.
	 */
	private $_bound_tips = array();

	/**
	 * Holds an array of bound tips selectors: used as bound tips selectors buffer.
	 */
	private $_bound_selectors = array();

	/**
	 * Full URL to help icon, which is used as tip anchor
	 * and as notice background image.
	 */
	private $_icon_url;

	/**
	 * Flag that determines do we want to use notices
	 * (tips expanded on click).
	 * Defaults to true.
	 */
	private $_use_notice = true;

	/**
	 * Limits tip output to a screen (page).
	 * Optional.
	 * Works best with bind_tip() method.
	 */
	private $_screen_id = false;

	/**
	 * Bind to footer hooks when instantiated.
	 */
	public function __construct () {
		global $wp_version;
		$version = preg_replace('/-.*$/', '', $wp_version);

		if (version_compare($version, '3.3', '>=')) {
			add_action('admin_footer', array($this, 'add_bound_tips'), 999);
			add_action('admin_print_footer_scripts', array($this, 'initialize'));
		}
	}

	/**
	 * Sets icon URL.
	 * @param string $icon_url Full URL to help anchor icon
	 */
	public function set_icon_url ($icon_url) {
		$this->_icon_url = $icon_url;
	}

	/**
	 * Set show notices (tips expanded on click) flag.
	 * @param bool $use_notice True to use notices (default), false otherwise.
	 */
	public function set_use_notice ($use_notice=true) {
		$this->_use_notice = $use_notice;
	}

	/**
	 * Set screen limiting flag.
	 * @param $screen_id Screen ID that tips in this object apply to.
	 */
	public function set_screen_id ($screen_id) {
		$this->_screen_id = $screen_id;
	}

	/**
	 * Returns inline tip markup.
	 * Scenario: for echoing inline tips next to elements on the page.
	 * Usage example:
	 * <code>
	 * echo $tips->add_tip('My tip text here');
	 * </code>
	 * @param string $tip Tip text
	 * @return string Inline tip markup
	 */
	public function add_tip ($tip) {
		if (!$this->_check_screen()) return false;
		$this->_inline_tips[] = $tip;
		return $this->_get_tip_markup($tip);
	}

	/**
	 * Binds a tip to selector.
	 * This is different from inline tips, as you don't have to output them yourself.
	 * Scenario: for adding help tips next to elements determined by the selector on page load time.
	 * Usage example:
	 * <code>
	 * $tips->bind_tip('My tip text here', '.icon32:first ~h2');
	 * </code>
	 * @param string $tip Tip text
	 * @param string $selector jQuery selector of the element that tip is related to.
	 */
	public function bind_tip  ($tip, $bind_to_selector) {
		$tip_id = 'wpmudev-help-tip-for-' . md5($bind_to_selector);
		$this->_bound_tips[$tip_id] = $tip;
		$this->_bound_selectors[$tip_id] = $bind_to_selector;
	}

	/**
	 * Bounded tips injection handler.
	 * Will queue up the bounded tips.
	 */
	function add_bound_tips () {
		if (!$this->_check_screen()) return false;
		if (!$this->_bound_tips) return false;

		foreach ($this->_bound_tips as $id => $tip) {
			echo $this->_get_tip_markup($tip, 'id="' . $id . '" style=display:none');
		}
	}

	/**
	 * Dependency injection handler.
	 * Will only add dependencies if there are actual tooltips to show.
	 */
	function initialize () {
		if (!$this->_check_screen()) return false;
		if (!$this->_inline_tips && !$this->_bound_tips) return false;

		$this->_print_styles();
		$this->_print_scripts();
	}

	/**
	 * Screen limitation check.
	 * @return bool True if we're good to go, false if we're on a wrong screen.
	 */
	private function _check_screen () {
		if (!$this->_screen_id) return true; // No screen dependency

		$screen = get_current_screen();
		if (!is_object($screen)) return false; // Actually older then 3.3
		if ($this->_screen_id != @$screen->id) return false; // Not for this screen

		return true;
	}

	private function _get_tip_markup ($tip, $arg='') {
		return "<span class='wpmudev-help' {$arg}>{$tip}</span>";
	}

	/**
	 * Private helper method that prints style dependencies.
	 */
	private function _print_styles () {
		// Have we already done this?
		if (!defined('WPMUDEV_TOOLTIPS_CSS_ADDED')) define('WPMUDEV_TOOLTIPS_CSS_ADDED', true);
		else return false;

		?>
<style type="text/css">
.wpmudev-help {
	display: block;
	background-color: #ffffe0;
	border: 1px solid #e6db55;
	padding: 1em 1em;
	-moz-border-radius:3px;
	-khtml-border-radius:3px;
	-webkit-border-radius:3px;
	border-radius:3px;
}
<?php
		if ($this->_icon_url) {
?>
.wpmudev-help {
	background: url(<?php echo $this->_icon_url; ?>) no-repeat scroll 10px center #ffffe0;
	padding-left: 40px;
}
.wpmudev-help-trigger span {
	display: block;
	position: absolute;
	left: -12000000px;
}
.wpmudev-help-trigger {
	position: relative;
	background: url(<?php echo $this->_icon_url; ?>) no-repeat scroll center bottom transparent;
	padding: 1px 12px;
	text-decoration: none;
}
<?php
		}
?>
#wpmudev-tooltip-source {
	margin: 0 13px;
	padding: 8px;

	background: #fff;
	border-style: solid;
	border-width: 1px;
	/* Fallback for non-rgba-compliant browsers */
	border-color: #dfdfdf;
	/* Use rgba to look better against non-white backgrounds. */
	border-color: rgba(0,0,0,.125);
	-webkit-border-radius: 3px;
	border-radius: 3px;

	-webkit-box-shadow: 0 2px 4px rgba(0,0,0,.19);
	-moz-box-shadow: 0 2px 4px rgba(0,0,0,.19);
	box-shadow: 0 2px 4px rgba(0,0,0,.19);
}
.wpmudev-left_pointer {
	float: left;
	width: 14px;
	height: 30px;
	margin-top: 8px;
	background: url(<?php echo site_url("/wp-includes/images/arrow-pointer-blue.png");?>) 0 -15px no-repeat;
}
.wpmudev-right_pointer {
	float: right;
	width: 14px;
	height: 30px;
	margin-top: 8px;
	background: url(<?php echo site_url("/wp-includes/images/arrow-pointer-blue.png");?>) -16px -15px no-repeat;
}
</style>
		<?php
	}

	/**
	 * Private helper method that prints javascript dependencies.
	 */
	private function _print_scripts () {
		// Have we already done this?
		if (!defined('WPMUDEV_TOOLTIPS_JS_ADDED')) define('WPMUDEV_TOOLTIPS_JS_ADDED', true);
		else return false;

		// Initialize bound selectors
		$selectors = json_encode($this->_bound_selectors);

		?>
<script type="text/javascript">
(function ($) {

/**
 * Converts help text placeholders to tooltip items.
 */
function initialize_help_item ($me) {
	var $prev = $me.prev();
	var help = '&nbsp;<a class="wpmudev-help-trigger" href="#help"><span><?php _e('Help');?></span></a>';
	$prev = $prev.length ?
		$prev.after(help)
		:
		$me.before(help)
	;
	$me.hide();
}

/**
 * Finds a help block corresponding to trigger.
 */
function get_help_block ($me) {
	var $parent = $me.parent();
	return $parent.find('.wpmudev-help');
}

/**
 * Handles help block visibility.
 */
function show_help_block ($me) {
	var $help = get_help_block($me);
	if (!$help.length) return false;

	if ($("#wpmudev-tooltip").length) $("#wpmudev-tooltip").remove();
	if ($help.is(":visible")) $help.hide('fast');
	else $help.show('fast');
}

/**
 * Pops tooltip open.
 */
function open_tooltip ($me) {
	var $help = get_help_block($me);
	if ($help.is(":visible")) return false;

	if ($("#wpmudev-tooltip").length) $("#wpmudev-tooltip").remove();
	if (!$("#wpmudev-tooltip").length) $("body").append('<div id="wpmudev-tooltip"><div class="wpmudev-pointer wpmudev-left_pointer"></div><div id="wpmudev-tooltip-source"></div></div>');
	var $tip = $("#wpmudev-tooltip");
	if (!$tip.length) return false;

	var width = 200;
	var margin = 20;
	var src_pos = $me.offset();

	var top_pos = src_pos.top + ($me.height() / 2);
	var left_pos = src_pos.left + margin;
	var $pointer = $tip.find(".wpmudev-pointer");

	// Setup left/right orientation
<?php if (!is_rtl()) { ?>
	if ((left_pos+width+60) >= $(window).width()) {
		left_pos = src_pos.left - ($me.outerWidth()+width+margin);
		$pointer
			.removeClass("wpmudev-left_pointer")
			.addClass("wpmudev-right_pointer")
		;
	}
<?php } else { ?>
	var min_left = left_pos - (width+60);
	if (min_left > 0) {
		left_pos = min_left;
		$pointer
			.removeClass("wpmudev-left_pointer")
			.addClass("wpmudev-right_pointer")
		;
	}
<?php } ?>

	// IE safeguard
	if ($.browser.msie) {
		var $pointer_left = $tip.find(".wpmudev-left_pointer");
		if ($pointer_left.length) $pointer_left.css("position", "absolute");
	}

	$tip
		// Populate tip text
		.find("#wpmudev-tooltip-source")
			.width(width)
			.html($help.html())
		.end()
		// Position tip
		.css({
			"position": "absolute",
		})
		.offset({
			"top": top_pos - ($tip.height()/2),
			"left": left_pos
		})
		// Vertically align pointer
		.find(".wpmudev-pointer")
			.css({
				"margin-top": ($tip.height() - 32) / 2
			})
		.end()
		// Show entire tip
		.show()
	;
}

/**
 * Closes tooltip.
 */
function close_tooltip () {
	if (!$("#wpmudev-tooltip").length) return false;

	// IE conditional alternate removal
	if ($.browser.msie) {
		$("#wpmudev-tooltip").hide('fast');
	} else {
		// Not IE, do regular transparency animation
		$("#wpmudev-tooltip")
			.animate({
				"opacity": 0
			},
			'fast',
			function () {
				$(this).remove();
			}
		);
	}
}


// Init
$(function () {

// Populate and place bound tips
$.each($.parseJSON('<?php echo $selectors; ?>'), function (tip_id, selector) {
	var $tip = $("#" + tip_id);
	if (!$tip.length) return true;

	var $selector = $(selector);
	if (!$selector.length) return true;

	$selector.append($tip);
});

// Initialize help and add handles
$(".wpmudev-help").each(function () {
	initialize_help_item($(this));

});

// Handle help requests
$(".wpmudev-help-trigger")
	.click(function (e) {
<?php if ($this->_use_notice) { ?>
		show_help_block($(this));
<?php } ?>
		return false;
	})
	.mouseover(function (e) {
		open_tooltip($(this));

	})
	.mouseout(close_tooltip)
;


});
})(jQuery);
</script>
		<?php
	}
}
