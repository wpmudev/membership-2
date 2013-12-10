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
 * Rule class responsible for BuddyPress groups protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Buddypress
 */
class Membership_Model_Rule_Buddypress_Groups extends Membership_Model_Rule {

	var $name = 'bpgroups';
	var $label = 'Groups';
	var $description = 'Allows specific BuddyPress groups to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpgroups'>
			<h2 class='sidebar-name'><?php _e('Groups', 'membership');?><span><a href='#remove' id='remove-bpgroups' class='removelink' title='<?php _e("Remove Groups from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the groups to be covered by this rule by checking the box next to the relevant groups title.','membership'); ?></p>
				<?php

					if(function_exists('groups_get_groups')) {
						$groups = groups_get_groups(array('per_page' => MEMBERSHIP_GROUP_COUNT));
					}

					if($groups) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Group title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Group created', 'membership'); ?></th>
							</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Group title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Group created', 'membership'); ?></th>
							</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($groups['groups'] as $key => $group) {
							?>
							<tr valign="middle" class="alternate" id="bpgroup-<?php echo $group->id; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $group->id; ?>" name="bpgroups[]" <?php if(in_array($group->id, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($group->name); ?></strong>
								</td>
								<td class="column-date">
									<?php
										echo date("Y/m/d", strtotime($group->date_created));
									?>
								</td>
						    </tr>
							<?php
						}
						?>
							</tbody>
						</table>
						<?php
					}

					if($groups['total'] > MEMBERSHIP_GROUP_COUNT) {
						?>
						<p class='description'><?php echo __("Only the most recent ", 'membership') . MEMBERSHIP_GROUP_COUNT . __(" groups are shown above.",'membership'); ?></p>
						<?php
					}

				?>

			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );

		add_filter( 'groups_get_groups', array( $this, 'add_viewable_groups' ) );
		add_filter( 'bp_activity_get', array( $this, 'add_has_activity' ) );
		//add_filter( 'bp_has_groups', array( $this, 'add_has_groups'), 10, 2 );
	}

	function add_has_activity( $activities ) {
		$inneracts = $activities['activities'];

		foreach ( (array)$inneracts as $key => $act ) {
			if ( $act->component == 'groups' ) {
				if ( !in_array( $act->item_id, $this->data ) ) {
					unset( $inneracts[$key] );
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach ( (array) $inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_has_groups( $one, $groups ) {
		$innergroups = $groups->groups;
		foreach ( (array)$innergroups as $key => $group ) {
			if ( !in_array( $group->group_id, $this->data ) ) {
				unset( $innergroups[$key] );
				$groups->total_group_count--;
			}
		}

		$groups->groups = array();
		foreach ( (array)$innergroups as $key => $group ) {
			$groups->groups[] = $group;
		}

		return !empty( $groups->groups );
	}

	function add_unhas_groups( $one, $groups ) {
		$innergroups = $groups->groups;
		foreach ( (array)$innergroups as $key => $group ) {
			if ( in_array( $group->group_id, $this->data ) ) {
				unset( $innergroups[$key] );
				$groups->total_group_count--;
			}
		}

		$groups->groups = array();
		foreach ( (array)$innergroups as $key => $group ) {
			$groups->groups[] = $group;
		}

		return !empty( $groups->groups );
	}

	function on_negative($data) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );

		add_filter( 'groups_get_groups', array( &$this, 'add_unviewable_groups' ) );
		add_filter( 'bp_activity_get', array( &$this, 'add_unhas_activity' ) );
		//add_filter( 'bp_has_groups', array( $this, 'add_unhas_groups'), 10, 2 );
	}

	function add_unhas_activity( $activities ) {
		$inneracts = $activities['activities'];
		foreach ( (array)$inneracts as $key => $act ) {
			if ( $act->component == 'groups' ) {
				if ( in_array( $act->item_id, $this->data ) ) {
					unset( $inneracts[$key] );
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array( );
		foreach ( (array)$inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_viewable_groups( $groups ) {
		foreach ( (array)$groups['groups'] as $key => $group ) {
			if ( !in_array( $group->id, $this->data ) ) {
				unset( $groups['groups'][$key] );
				$groups['total']--;
			}
		}

		sort( $groups['groups'] );

		return $groups;
	}

	function add_unviewable_groups( $groups ) {
		foreach ( (array)$groups['groups'] as $key => $group ) {
			if ( in_array( $group->id, $this->data ) ) {
				unset( $groups['groups'][$key] );
				$groups['total']--;
			}
		}

		sort( $groups['groups'] );

		return $groups;
	}

	function validate_negative() {
		global $bp;

		if ( bp_current_component() != 'groups' ) {
			return parent::validate_negative();
		}

		return isset( $bp->groups->current_group ) && is_a( $bp->groups->current_group, 'BP_Groups_Group' )
			? !in_array( $bp->groups->current_group, $this->data )
			: parent::validate_negative();
	}

	function validate_positive() {
		global $bp;

		if ( bp_current_component() != 'groups' ) {
			return parent::validate_positive();
		}

		return isset( $bp->groups->current_group ) && is_a( $bp->groups->current_group, 'BP_Groups_Group' )
			? in_array( $bp->groups->current_group, $this->data )
			: parent::validate_positive();
	}

}