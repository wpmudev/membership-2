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
 * Rule class responsible for downloads protection.
 *
 * @category Membership
 * @package Rule
 */
class Membership_Rule_Downloads extends Membership_Rule {

	var $name = 'downloads';
	var $label = 'Downloads';
	var $description = 'Allows media uploaded to the WordPress media library to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {

		global $wpdb, $M_options;

		if(!$data) $data = array();

		?>
		<div class='level-operation' id='main-downloads'>
			<h2 class='sidebar-name'><?php _e('Downloads', 'membership');?><span><a href='#remove' id='remove-downloads' class='removelink' title='<?php _e("Remove Downloads from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Downloads / Media to be covered by this rule by checking the box next to the relevant group name.','membership'); ?></p>
				<?php
					$mediasql = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_membership_protected_content' );
					$mediaids = $wpdb->get_col( $mediasql );

					if(!empty($mediaids)) {
						// We have some ids so grab the information
						$attachmentsql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND ID IN(" . implode(",", $mediaids) . ")" );

						$attachments = $wpdb->get_results( $attachmentsql );
					}
					?>
					<table cellspacing="0" class="widefat fixed">
						<thead>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Download / Group name', 'membership'); ?></th>
						</tr>
						</thead>
						<tfoot>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Download / Group name', 'membership'); ?></th>
						</tr>
						</tfoot>

						<tbody>
						<?php
						if(!empty($M_options['membershipdownloadgroups'])) {

							foreach($M_options['membershipdownloadgroups'] as $key => $value) {
								if(!empty($value)) {
									?>
									<tr valign="middle" class="alternate" id="group-<?php echo esc_attr(stripslashes(trim($value))); ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo esc_attr(stripslashes(trim($value))); ?>" name="downloads[]" <?php if(in_array(esc_attr(stripslashes(trim($value))), $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<strong><?php echo esc_html(stripslashes(trim($value))); ?></strong>
										</td>
								    </tr>
									<?php
								}
							}

						} else {
							?>
							<tr valign="middle" class="alternate" id="group-nogroup">
								<td class="column-name" colspan='2'>
									<?php echo __('You have no download groups set, please visit the membership options page to set them up.','membership'); ?>
								</td>
						    </tr>
							<?php
						}

						?>
						</tbody>
					</table>

			</div>
		</div>
		<?php
	}

	function can_view_download( $area, $group ) {
		switch ( $area ) {
			case 'positive':
				if ( in_array( $group, (array)$this->data ) ) {
					return true;
				}
				break;
			case 'negative':
				if ( in_array( $group, (array)$this->data ) ) {
					return false;
				}
				break;
			default:
				return false;
		}
	}

}