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
 * Rule class responsible for shortcodes protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_Shortcodes extends Membership_Model_Rule {

	var $name = 'shortcodes';
	var $label = 'Shortcodes';
	var $description = 'Allows specific shortcodes and contained content to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {

		global $shortcode_tags;

		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-shortcodes'>
			<h2 class='sidebar-name'><?php _e('Shortcodes', 'membership');?><span><a href='#remove' id='remove-shortcodes' class='removelink' title='<?php _e("Remove Shortcodes from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Shortcodes to be covered by this rule by checking the box next to the relevant shortcode tag.','membership'); ?></p>
				<?php
					if($shortcode_tags) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
								<tr>
									<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
									<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Shortcode tag', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
								<tr>
									<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
									<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Shortcode tag', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
								<?php
								foreach($shortcode_tags as $key => $function) {
									?>
									<tr valign="middle" class="alternate" id="post-<?php echo $key; ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo esc_attr(trim($key)); ?>" name="shortcodes[]" <?php if(in_array(trim($key), $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<strong>[<?php echo esc_html(trim($key)); ?>]</strong>
										</td>
								   </tr>
									<?php
									}
									?>
								</tbody>
							</table>
							<?php
							}
						?>
			</div>
		</div>
		<?php
	}

	function on_creation() {
		//add_filter('the_content', array(&$this, 'override_shortcodes'), 1);
	}

	function override_shortcodes() {
		global $M_shortcode_tags, $shortcode_tags;

		$M_shortcode_tags = $shortcode_tags;

		foreach ( $shortcode_tags as $key => $function ) {
			if ( $key != 'subscriptionform' ) {
				$shortcode_tags[$key] = array( &$this, 'do_protected_shortcode' );
			}
		}

		return $content;
	}

	function on_positive( $data ) {
		global $M_options, $M_shortcode_tags, $shortcode_tags;

		$this->data = $data;

		if ( $M_options['shortcodedefault'] == 'no' ) {
			// Need to re-enable some shortcodes
			foreach ( (array) $data as $key => $code ) {
				if ( isset( $M_shortcode_tags[$code] ) && isset( $shortcode_tags[$code] ) ) {
					$shortcode_tags[$code] = $M_shortcode_tags[$code];
				}
			}
		}
	}

	function on_negative( $data ) {
		global $M_options, $M_shortcode_tags, $shortcode_tags;

		$this->data = $data;
		$M_shortcode_tags = $shortcode_tags;

		if ( $M_options['shortcodedefault'] != 'no' ) {
			// Need to disable some shortcodes
			foreach ( (array) $data as $key => $code ) {
				if ( isset( $M_shortcode_tags[$code] ) && isset( $shortcode_tags[$code] ) ) {
					if ( $code != 'subscriptionform' ) {
						$shortcode_tags[$code] = array( &$this, 'do_protected_shortcode' );
					}
				}
			}
		}
	}

	// Show the protected shortcode message
	function do_protected_shortcode( $atts, $content = null, $code = "" ) {
		global $M_options;
		return stripslashes( $M_options['shortcodemessage'] );
	}

}