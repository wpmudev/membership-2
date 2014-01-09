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
 * The subscription model class.
 *
 * @since 3.5
 *
 * @category Membership
 * @package Model
 * @subpackage Subscription
 */
class Membership_Model_Subscription {

	var $id = false;

	/** @var wpdb */
	var $db;

	// if the data needs reloaded, or hasn't been loaded yet
	var $dirty = true;

	var $subscription;
	var $levels = array();

	var $levelorder = array();

	function __construct( $id = false ) {
		global $wpdb;

		$this->db = $wpdb;
		$this->id = $id;
	}

	// Fields

	function sub_id() {

		if(empty($this->subscription)) {
			$sub = $this->get();

			if($sub) {
				return $sub->id;
			} else {
				return false;
			}
		} else {
			return $this->subscription->id;
		}

	}

	function sub_name() {

		if(empty($this->subscription)) {
			$sub = $this->get();

			if($sub) {
				return stripslashes($sub->sub_name);
			} else {
				return false;
			}
		} else {
			return stripslashes($this->subscription->sub_name);
		}

	}

	function sub_description() {

		if(empty($this->subscription)) {
			$sub = $this->get();

			if($sub) {
				return stripslashes($sub->sub_description);
			} else {
				return false;
			}
		} else {
			return stripslashes($this->subscription->sub_description);
		}

	}

	function sub_pricetext() {

		if(empty($this->subscription)) {
			$sub = $this->get();

			if($sub) {
				return stripslashes($sub->sub_pricetext);
			} else {
				return false;
			}
		} else {
			return stripslashes($this->subscription->sub_pricetext);
		}

	}

	function get_pricingarray() {
		$prices = array();
		$levels = $this->get_levels();
		foreach ( (array)$levels as $level ) {
			$prices[] = array(
				'type'     => $level->sub_type,
				'amount'   => $level->level_price,
				'period'   => $level->level_period,
				'unit'     => $level->level_period_unit,
				'level_id' => $level->level_id,
			);

			if ( $level->sub_type == 'indefinite' || $level->sub_type == 'serial' ) {
				// This will be the last item in any list
				break;
			}
		}

		return !empty( $prices ) ? $prices : false;
	}

	// Gets

	function get_next_level($level_id, $order_id) {
		// returns the next level - if there is one
		$onkey = false;

		if(empty($this->levels)) {
			$this->levels = $this->get_levels();
		}

		if(!empty($this->levels)) {
			$onkey = false;
			foreach($this->levels as $key => $level) {
				if($level->level_order == $order_id) {
					// This is the order we are at - check the level_id
					if($level->level_id == $level_id) {
						// sweet - nobody has been messing around with the subscription
						$onkey = $key;
					} else {
						// We're not on the right level, but this is location we should be at
						// return the key for this level and hope for the best I guess
						$onkey = $key;
					}
					break;
				}
			}

			if($onkey !== false) {
				// we have a key for our current position, check it's mode / period and pos next level
				switch($this->levels[$onkey]->sub_type) {
					case 'finite':		// we attempt to move to the next level
										if(isset($this->levels[(int) $onkey + 1])) {
											return $this->levels[(int) $onkey + 1];
										} else {
											return false;
										}
					case 'indefinite':	// we stay at our current level
										return $this->levels[$onkey];
					case 'serial':		// we renew at our current level
										return $this->levels[$onkey];
				}


			} else {
				return false;
			}

		} else {
			return false;
		}
	}

	function get() {
		if ( $this->dirty ) {
			$this->subscription = $this->db->get_row( sprintf(
				"SELECT * FROM %s WHERE id = %d",
				MEMBERSHIP_TABLE_SUBSCRIPTIONS,
				$this->id
			) );

			$this->dirty = false;
		}

		return $this->subscription;
	}

	function get_levels() {
		$this->levels = $this->db->get_results( sprintf(
			"SELECT * FROM %s sl INNER JOIN %s l on sl.level_id = l.id WHERE sub_id = %d ORDER BY level_order ASC",
			MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS,
			MEMBERSHIP_TABLE_LEVELS,
			$this->id
		) );

		return $this->levels;
	}

	function get_level_at($level_id, $level_order) {
		$this->levels = $this->db->get_row( sprintf(
			"SELECT * FROM %s sl INNER JOIN %s l on sl.level_id = l.id WHERE sub_id = %d AND level_id = %d AND level_order = %d ORDER BY level_order ASC",
			MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS,
			MEMBERSHIP_TABLE_LEVELS,
			$this->id,
			$level_id,
			$level_order
		) );

		return $this->levels;
	}

	function get_level_at_position($level_order) {
		$this->levels = $this->db->get_row( sprintf(
			"SELECT * FROM %s sl INNER JOIN %s l on sl.level_id = l.id WHERE sub_id = %d AND level_order = %d ORDER BY level_order ASC",
			MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS,
			MEMBERSHIP_TABLE_LEVELS,
			$this->id,
			$level_order
		) );

		return $this->levels;
	}

	function toggleactivation( $force = false ) {

		if(!apply_filters( 'pre_membership_toggleactivate_subscription', true, $this->id )) {
			return false;
		}

		$this->dirty = true;

		$result = $this->db->query( sprintf(
			"UPDATE %s SET sub_active = NOT sub_active WHERE id = %d",
			MEMBERSHIP_TABLE_SUBSCRIPTIONS,
			$this->id
		) );

		do_action( 'membership_toggleactivate_subscription', $this->id, $result );

		return $result;

	}

	function togglepublic( $force = false ) {

		if(!apply_filters( 'pre_membership_togglepublic_subscription', true, $this->id )) {
			return false;
		}

		$this->dirty = true;

		$result = $this->db->query( sprintf(
			"UPDATE %s SET sub_public = NOT sub_public WHERE id = %d",
			MEMBERSHIP_TABLE_SUBSCRIPTIONS,
			$this->id
		) );

		do_action( 'membership_togglepublic_subscription', $this->id, $result );

		return $result;


	}

	function delete() {
		if ( !apply_filters( 'pre_membership_delete_subscription', true, $this->id ) ) {
			return false;
		}

		$this->db->delete( MEMBERSHIP_TABLE_SUBSCRIPTIONS, array( 'id' => $this->id ), array( '%d' ) );
		$this->db->delete( MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS, array( 'sub_id' => $this->id ), array( '%d' ) );

		$this->dirty = true;

		do_action( 'membership_delete_subscription', $this->id );

		return true;
	}

	function add() {

		$this->dirty = true;

		if($this->id > 0) {
			$this->update();
		} else {

			$this->db->insert( MEMBERSHIP_TABLE_SUBSCRIPTIONS, array(
				'sub_name'        => trim( filter_input( INPUT_POST, 'sub_name' ) ),
				'sub_description' => trim( filter_input( INPUT_POST, 'sub_description' ) ),
				'sub_pricetext'   => trim( filter_input( INPUT_POST, 'sub_pricetext' ) ),
				'order_num'       => filter_input( INPUT_POST, 'sub_order_num', FILTER_VALIDATE_INT ),
			), array( '%s', '%s', '%s', '%d' ) );

			$this->id = $this->db->insert_id;

			if(!empty($_POST['level-order'])) {

				$levels = explode(',', $_POST['level-order']);
				$count = 1;
				foreach( (array) $levels as $level ) {
					if(!empty($level)) {
						// Check if the rule has any information for it.
						if(isset($_POST['levelmode'][$level])) {
							$levelmode = esc_attr($_POST['levelmode'][$level]);
						} else {
							continue;
						}

						if(isset($_POST['levelperiod'][$level])) {
							$levelperiod = esc_attr($_POST['levelperiod'][$level]);
						} else {
							$levelperiod = '';
						}

						if(isset($_POST['levelperiodunit'][$level])) {
							$levelperiodunit = esc_attr($_POST['levelperiodunit'][$level]);
						} else {
							$levelperiodunit = '';
						}

						if(isset($_POST['levelprice'][$level])) {
							$levelprice = floatval(esc_attr($_POST['levelprice'][$level]));
						} else {
							$levelprice = '';
						}

						if(isset($_POST['levelcurrency'][$level])) {
							$levelcurrency = esc_attr($_POST['levelcurrency'][$level]);
						} else {
							$levelcurrency = '';
						}



						// Calculate the level id
						$lev = explode( '-', $level );
						if ( $lev[0] == 'level' ) {
							$level_id = (int) $lev[1];
							// write it to the database
							$this->db->insert( MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS, array(
								"sub_id" => $this->id,
								"level_period" => $levelperiod,
								"sub_type" => $levelmode,
								"level_price" => $levelprice,
								"level_currency" => $levelcurrency,
								"level_order" => $count++,
								"level_id" => $level_id,
								"level_period_unit" => $levelperiodunit
							) );
						}
					}
				}

			}

			do_action('membership_subscription_add', $this->id);

			return $this->id; // for now

		}

	}

	function update() {
		$this->dirty = true;

		if($this->id < 0) {
			$this->add();
		} else {

			$this->db->update( MEMBERSHIP_TABLE_SUBSCRIPTIONS, array(
				'sub_name'        => trim( filter_input( INPUT_POST, 'sub_name' ) ),
				'sub_description' => trim( filter_input( INPUT_POST, 'sub_description' ) ),
				'sub_pricetext'   => trim( filter_input( INPUT_POST, 'sub_pricetext' ) ),
				'order_num'       => filter_input( INPUT_POST, 'sub_order_num', FILTER_VALIDATE_INT ),
			), array( 'id' => $this->id ), array( '%s', '%s', '%s', '%d' ), array( '%d' ) );

			// Remove the existing rules for this subscription level
			$this->db->delete( MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS, array( 'sub_id' => $this->id ), array( '%d' ) );


			if(!empty($_POST['level-order'])) {

				$levels = explode(',', $_POST['level-order']);
				$count = 1;
				foreach( (array) $levels as $level ) {
					if(!empty($level)) {
						// Check if the rule has any information for it.
						if(isset($_POST['levelmode'][$level])) {
							$levelmode = esc_attr($_POST['levelmode'][$level]);
						} else {
							continue;
						}

						if(isset($_POST['levelperiod'][$level])) {
							$levelperiod = esc_attr($_POST['levelperiod'][$level]);
						} else {
							$levelperiod = '';
						}

						if(isset($_POST['levelperiodunit'][$level])) {
							$levelperiodunit = esc_attr($_POST['levelperiodunit'][$level]);
						} else {
							$levelperiodunit = '';
						}

						if(isset($_POST['levelprice'][$level])) {
							$levelprice = floatval(esc_attr($_POST['levelprice'][$level]));
						} else {
							$levelprice = '';
						}

						if(isset($_POST['levelcurrency'][$level])) {
							$levelcurrency = esc_attr($_POST['levelcurrency'][$level]);
						} else {
							$levelcurrency = '';
						}

						// Calculate the level id
						$lev = explode( '-', $level );
						if ( $lev[0] == 'level' ) {
							$level_id = (int) $lev[1];
							// write it to the database
							$this->db->insert( MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS, array(
								"sub_id" => $this->id,
								"level_period" => $levelperiod,
								"sub_type" => $levelmode,
								"level_price" => $levelprice,
								"level_currency" => $levelcurrency,
								"level_order" => $count++,
								"level_id" => $level_id,
								"level_period_unit" => $levelperiodunit
							) );
						}
					}

				}

			}

			do_action('membership_subscription_update', $this->id);
		}

		return true; // for now

	}


	// For display

	function sub_template() {

		global $M_options;

		?>
		<li class='sortable-levels' id="%templateid%" >
			<div class='joiningline'>&nbsp;</div>
			<div class="sub-operation" style="display: block;">
				<h2 class="sidebar-name">%startingpoint%<span><a href='#remove' class='removelink' title='<?php _e("Remove this level from the subscription.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
				<div class="inner-operation">
					<div class='levelfields' style='float: left;'>
					<label for='levelmode[%level%]'><?php _e('Mode : ','membership'); ?></label>
					<select name='levelmode[%level%]'>
						<!-- <option value='trial'>Trial</option> -->
						<option value='finite'>Finite</option>
						<option value='indefinite'>Indefinite</option>
						<option value='serial'>Serial</option>
						<!-- <option value='sequential'>Sequential</option> -->
					</select>
					<label for='levelperiod[%level%]'><?php _e('Period : ','membership'); ?></label>
					<select name='levelperiod[%level%]'>
						<?php
							for($n = 1; $n <= 365; $n++) {
								?>
								<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
								<?php
							}
						?>
					</select>	&nbsp;
						<select name="levelperiodunit[%level%]">
							<option value='d'><?php _e('day(s)','membership'); ?></option>
							<option value='w'><?php _e('week(s)','membership'); ?></option>
							<option value='m'><?php _e('month(s)','membership'); ?></option>
							<option value='y'><?php _e('year(s)','membership'); ?></option>
						</select>

					<label for='levelprice[%level%]'><?php _e('Price : ','membership'); ?></label>
					<input type='text' name='levelprice[%level%]' value='' class='narrow' />
					<?php /* ?>
					<select name='levelprice[%level%]'>
						<option value=''></option>
						<?php
							for($n = 1; $n <= (int) MEMBERSHIP_MAX_CHARGE; $n++) {
								?>
								<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
								<?php
							}
						?>
					</select><?php */ ?>&nbsp;
					<?php
						if(!empty($M_options['paymentcurrency'])) {
							echo esc_html($M_options['paymentcurrency']);
						} else {
							$M_options['paymentcurrency'] = 'USD';
							echo esc_html($M_options['paymentcurrency']);
						}
					?>
					</div>
					<div class='levelinformation' style='float: right;'>
						<p class='description'>
							<strong><?php _e('Mode details','membership'); ?></strong><br/>
							<?php _e('<strong>Finite</strong> - user remains at this level for a set period of time before ending','membership');?><br/><br/>
							<?php _e('<strong>Indefinite</strong> - user remains at this level for ever.','membership');?><br/><br/>
							<?php _e('<strong>Serial</strong> - user remains at this level for a set period of time and is then renewed at the same level','membership');?><br/><br/>
							<?php _e('<strong>Note:</strong> - depending on the payment gateway used, changing the price will not alter subscriptions charged to existing members.','membership');?>
						</p>
					</div>
				</div>
			</div>
		</li>
		<?php
	}

	function sub_details() {

		global $M_options;

		$count = 1;

		$this->levels = $this->get_levels();

		$afterserial = false;

		if(!empty($this->levels)) {
			$count = 1;
			foreach($this->levels as $key => $level) {

				$levelid = 'level-' . $level->level_id . '-' . $count++;
				$this->levelorder[] = $levelid;

				?>
				<li class='sortable-levels <?php echo ($afterserial) ? 'afterserial' : ''; ?>' id="<?php echo $levelid; ?>" >
					<div class='joiningline'>&nbsp;</div>
					<div class="sub-operation" style="display: block;">
						<h2 class="sidebar-name"><?php echo esc_html($level->level_title); ?><span><a href='#remove' class='removelink' title='<?php _e("Remove this level from the subscription.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
						<div class="inner-operation">
							<div class='levelfields' style='float: left;'>
							<label for='levelmode[<?php echo $levelid; ?>]'><?php _e('Mode : ','membership'); ?></label>
							<select name='levelmode[<?php echo $levelid; ?>]' class='sublevelmode'>
								<!-- <option value='trial' <?php if($level->sub_type == 'trial') echo "selected='selected'"; ?>><?php _e('Trial','membership'); ?></option> -->
								<option value='finite' <?php if($level->sub_type == 'finite') echo "selected='selected'"; ?>><?php _e('Finite','membership'); ?></option>
								<option value='indefinite' <?php if($level->sub_type == 'indefinite') echo "selected='selected'"; ?>><?php _e('Indefinite','membership'); ?></option>
								<option value='serial' <?php if($level->sub_type == 'serial') echo "selected='selected'"; ?>><?php _e('Serial','membership'); ?></option>
								<!-- <option value='sequential' <?php if($level->sub_type == 'sequential') echo "selected='selected'"; ?>><?php _e('Sequential','membership'); ?></option> -->
							</select>
							<label for='levelperiod[<?php echo $levelid; ?>]'><?php _e('Period : ','membership'); ?></label>
							<select name='levelperiod[<?php echo $levelid; ?>]' class='sublevelperiod'>
								<?php
									for($n = 1; $n <= 365; $n++) {
										?>
										<option value='<?php echo $n; ?>' <?php if($level->level_period == $n) echo "selected='selected'"; ?>><?php echo $n; ?></option>
										<?php
									}
								?>
							</select>&nbsp;
							<select name="levelperiodunit[<?php echo $levelid; ?>]" class='sublevelperiodunit'>
								<option value='d' <?php if($level->level_period_unit == 'd') echo "selected='selected'"; ?>><?php _e('day(s)','membership'); ?></option>
								<option value='w' <?php if($level->level_period_unit == 'w') echo "selected='selected'"; ?>><?php _e('week(s)','membership'); ?></option>
								<option value='m' <?php if($level->level_period_unit == 'm') echo "selected='selected'"; ?>><?php _e('month(s)','membership'); ?></option>
								<option value='y' <?php if($level->level_period_unit == 'y') echo "selected='selected'"; ?>><?php _e('year(s)','membership'); ?></option>
							</select>

							<label for='levelprice[<?php echo $levelid; ?>]'><?php _e('Price : ','membership'); ?></label>
							<input type='text' name='levelprice[<?php echo $levelid; ?>]' value='<?php echo number_format($level->level_price, 2, '.', ''); ?>' class='narrow sublevelprice' />
							<?php /* ?>
							<select name='levelprice[<?php echo $levelid; ?>]'>
								<option value=''></option>
								<?php
									for($n = 1; $n <= (int) MEMBERSHIP_MAX_CHARGE; $n++) {
										?>
										<option value='<?php echo $n; ?>' <?php if($level->level_price == $n) echo "selected='selected'"; ?>><?php echo $n; ?></option>
										<?php
									}
								?>
							</select><?php */ ?>&nbsp;
							<?php
								if(!empty($M_options['paymentcurrency'])) {
									echo esc_html($M_options['paymentcurrency']);
								} else {
									$M_options['paymentcurrency'] = 'USD';
									echo esc_html($M_options['paymentcurrency']);
								}
							?>
							</div>
							<div class='levelinformation' style='float: right;'>
								<p class='description'>
									<strong><?php _e('Mode details','membership'); ?></strong><br/>
									<?php _e('<strong>Finite</strong> - user remains at this level for a set period of time before ending','membership');?><br/><br/>
									<?php _e('<strong>Indefinite</strong> - user remains at this level for ever.','membership');?><br/><br/>
									<?php _e('<strong>Serial</strong> - user remains at this level for a set period of time and is then renewed at the same level','membership');?><br/><br/>
									<?php _e('<strong>Note:</strong> - depending on the payment gateway used, changing the price will not alter subscriptions charged to existing members.','membership');?>
								</p>
							</div>
						</div>
					</div>
				</li>
				<?php

				// Set the afterserial to true
				if($level->sub_type == 'serial' || $level->sub_type == 'indefinite') {
					$afterserial = true;
				}

			}
		}

	}

	// Counting
	function count() {
		return $this->db->get_var( sprintf(
			"SELECT count(*) as subcount FROM %s WHERE sub_id = %d",
			MEMBERSHIP_TABLE_RELATIONS,
			$this->id
		) );
	}

	// Meta information
	function get_meta( $key, $default = false ) {

		$sql = $this->db->prepare( 'SELECT meta_value FROM ' . MEMBERSHIP_TABLE_SUBSCRIPTION_META . ' WHERE meta_key = %s AND sub_id = %d', $key, $this->id );

		$row = $this->db->get_var( $sql );

		if ( empty( $row ) ) {
			return $default;
		} else {
			return $row;
		}
	}

	function add_meta($key, $value) {

		return $this->insertorupdate( MEMBERSHIP_TABLE_SUBSCRIPTION_META, array( 'sub_id' => $this->id, 'meta_key' => $key, 'meta_value' => $value) );

	}

	function update_meta($key, $value) {

		return $this->insertorupdate( MEMBERSHIP_TABLE_SUBSCRIPTION_META, array( 'sub_id' => $this->id, 'meta_key' => $key, 'meta_value' => $value) );

	}

	function delete_meta($key) {

		$sql = $this->db->prepare( 'DELETE FROM ' . MEMBERSHIP_TABLE_SUBSCRIPTION_META . ' WHERE meta_key = %s AND sub_id = %d', $key, $this->id);

		return $this->db->query( $sql );

	}

	function insertorupdate( $table, $query ) {

			$fields = array_keys($query);
			$formatted_fields = array();
			foreach ( $fields as $field ) {
				$form = '%s';
				$formatted_fields[] = $form;
			}
			$sql = "INSERT INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES ('" . implode( "','", $formatted_fields ) . "')";
			$sql .= " ON DUPLICATE KEY UPDATE ";

			$dup = array();
			foreach($fields as $field) {
				$dup[] = "`" . $field . "` = VALUES(`" . $field . "`)";
			}

			$sql .= implode(',', $dup);

			return $this->db->query( $this->db->prepare( $sql, $query ) );

	}

	function is_free() {
		$prices = $this->get_pricingarray();
		if ( !empty( $prices ) ) {
			foreach ( $prices as $price ) {
				if ( $price['amount'] > 0 ) {
					return false;
				}
			}
		}

		return true;
	}

}

/**
 * Deprecated version of the subscription class. Do not use this class anymore.
 *
 * @deprecated since version 3.5
 *
 * @category Membership
 * @package Model
 * @subpackage Subscription
 */
class M_Subscription extends Membership_Model_Subscription {}