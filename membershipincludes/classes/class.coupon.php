<?php

if(!class_exists('M_Coupon')) {

class M_Coupon {

	var $build = 1;

	var $tables = array('coupons', 'subscriptions');

	var $coupons;
	var $subscriptions;

	var $coupon_label = false;

	var $id;
	var $_coupon;
	var $_tips;

	var $errors = array();

	function __construct( $id = false, &$tips = false ) {

		global $wpdb;

		$this->db =& $wpdb;

		foreach($this->tables as $table) {
			$this->$table = membership_db_prefix($this->db, $table);
		}

		// If we are passing a non numeric ID we should try to find the ID by searching for the coupon name instead.
		if(!is_numeric($id)) {
			$search = $this->db->get_var( $this->db->prepare( "SELECT * FROM $this->coupons WHERE `couponcode` = %s", $id ) );

			if(!empty($search)) {
				$this->id = $search;
			}
		} else {
			$this->id = $id;
		}

		if($tips !== false) {
			$this->_tips = $tips;
		}

	}

	function M_Coupon( $id = false, &$tips = false ) {
		$this->__construct( $id, $tips );
	}

	function valid_coupon() {

	}

	function valid_for_subscription( $sub_id ) {

	}

	function add( $data ) {

		global $blog_id;

		if($this->id > 0 ) {
			return $this->update( $data );
		} else {
			if(!empty($data)) {

				$newdata = array();

				$newdata['couponcode'] = preg_replace('/[^A-Z0-9_-]/', '', strtoupper($data['couponcode']));
			    if (!$newdata['couponcode'])
			       $this->errors[] = __('Please enter a valid Coupon Code', 'membership');

			    $newdata['discount'] = round($data['discount'], 2);
			    if ($newdata['discount'] <= 0)
					$this->errors[] = __('Please enter a valid Discount Amount', 'membership');

				$newdata['discount_type'] = $data['discount_type'];
				if ($newdata['discount_type'] != 'amt' && $newdata['discount_type'] != 'pct')
			        $this->errors[] = __('Please choose a valid Discount Type', 'membership');

				$newdata['discount_currency'] = $data['discount_currency'];

				$newdata['coupon_sub_id'] = $data['coupon_sub_id'];

			  	$newdata['coupon_startdate'] = date('Y-m-d H:i:s',strtotime($data['coupon_startdate']));
			 	if ($newdata['coupon_startdate'] === false)
			        $this->errors[] = __('Please enter a valid Start Date', 'membership');

				if(empty($data['coupon_enddate'])) {
					if(isset($newdata['coupon_enddate'])) {
						unset($newdata['coupon_enddate']);
					}
				} else {
					$newdata['coupon_enddate'] = ( !empty($data['coupon_enddate']) ? date('Y-m-d H:i:s',strtotime($data['coupon_enddate'])) : '' );
					if (!empty($newdata['coupon_enddate']) && $data['coupon_enddate'] < $data['coupon_startdate']) {
						$this->errors[] = __('Please enter a valid End Date not earlier than the Start Date', 'membership');
					}
				}

				$newdata['coupon_uses'] = (is_numeric($data['coupon_uses'])) ? (int) $data['coupon_uses'] : '';

				//We need to insert a site_id
				$newdata['site_id'] = $blog_id;

				$this->db->insert( $this->coupons, $newdata );


			} else {
				$this->errors[] = __('Please ensure you complete the form.','membership');
			}
		}

	}

	function increment_coupon_used() {
		$sql = $this->db->prepare( "UPDATE {$this->coupons} SET coupon_used = coupon_used + 1 WHERE id = %d", $this->id);

		if($this->db->query($sql)) {
			return true;
		} else {
			return false;
		}
	}

	function update( $data ) {

		global $blog_id;

		$coupon_id = $data['ID'];

		if(!empty($data) && isset($coupon_id)) {

			$newdata = array();

				$newdata['couponcode'] = preg_replace('/[^A-Z0-9_-]/', '', strtoupper($data['couponcode']));
			    if (!$newdata['couponcode'])
			       $this->errors[] = __('Please enter a valid Coupon Code', 'membership');

			    $newdata['discount'] = round($data['discount'], 2);
			    if ($newdata['discount'] <= 0)
					$this->errors[] = __('Please enter a valid Discount Amount', 'membership');

				$newdata['discount_type'] = $data['discount_type'];
				if ($newdata['discount_type'] != 'amt' && $newdata['discount_type'] != 'pct')
			        $this->errors[] = __('Please choose a valid Discount Type', 'membership');

				$newdata['discount_currency'] = $data['discount_currency'];

				$newdata['coupon_sub_id'] = $data['coupon_sub_id'];

			  	$newdata['coupon_startdate'] = date('Y-m-d H:i:s',strtotime($data['coupon_startdate']));
			 	if (empty($newdata['coupon_startdate']))
			        $this->errors[] = __('Please enter a valid Start Date', 'membership');

				if(empty($data['coupon_enddate'])) {
					if(isset($newdata['coupon_enddate'])) {
						unset($newdata['coupon_enddate']);
					}
				} else {
					$newdata['coupon_enddate'] = ( !empty($data['coupon_enddate']) ? date('Y-m-d H:i:s',strtotime($data['coupon_enddate'])) : '' );
					if (!empty($newdata['coupon_enddate']) && $data['coupon_enddate'] < $data['coupon_startdate']) {
						$this->errors[] = __('Please enter a valid End Date not earlier than the Start Date', 'membership');
					}
				}

				if(isset($data['coupon_uses']))
					$newdata['coupon_uses'] = $data['coupon_uses'];

				$this->db->update( $this->coupons, $newdata, array('id' => $coupon_id ), '%s', '%s' );
				//$this->db->update( $this->coupons, $newdata );

		} else {
			$this->errors[] = __('Please ensure you complete the form.','membership');
		}

	}

	function delete( $id ) {

		if(!apply_filters( 'pre_membership_delete_coupon', true, $this->id )) {
			return false;
		}

		$sql = $this->db->prepare( "DELETE FROM {$this->coupons} WHERE id = %d", $this->id);

		if($this->db->query($sql)) {
			do_action( 'membership_delete_coupon', $this->id );

			return true;
		} else {
			return false;
		}

	}

	private function get_subscriptions() {

		// Bring up a list of active subscriptions
		$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions} WHERE sub_active = %d", 1 );

		return $this->db->get_results( $sql );

	}

	function get_coupon($return_array=False) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->coupons} WHERE id = %d", $this->id );

		if($return_array)
			return $this->db->get_row( $sql, ARRAY_A );
		else
			return $this->db->get_row( $sql );
	}
	function apply_price($price) {
		if(!is_numeric($this->id) || $this->id < 1)
			return $price;

		$coupon = $this->get_coupon();

		if($coupon->discount_type == 'pct') {
			$discount = ($price / 100) * $coupon->discount;
			$new_price = $price - $discount;
			$this->coupon_label = sprintf(__('%s: -%s%%','membership'), $coupon->couponcode, $coupon->discount );
		} else if($coupon->discount_type == 'amt') {
			$new_price = $price - $coupon->discount;
			$discount_currency = (isset($M_options['paymentcurrency']) ? $M_options['paymentcurrency'] : '$');
			$discount_currency = (isset($coupon->discount_currency) && !empty($coupon->discount_currency) ? $coupon->discount_currency : $discount_currency);
			$this->coupon_label = sprintf(__('%s: -%s%s','membership'), $coupon->couponcode, $discount_currency, $coupon->discount );
		} else {
			//Unknown type
			$new_price = $price;
		}

		return apply_filters('membership_coupon_price', $new_price, $price, $coupon);

	}
	function addform() {

		global $M_options;

		echo '<table class="form-table">';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Coupon Code','membership') . $this->_tips->add_tip( __('The Coupon code should contain letters and numbers only.','membership') ) . '</th>';
		echo '<td valign="top"><input name="couponcode" type="text" size="50" title="' . __('Coupon Code', 'membership') . '" style="width: 50%;" value="" /></td>';
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Discount','membership') . $this->_tips->add_tip( __('The amount or percantage of a discount the coupon is valid for.','membership') ) . '</th>';
		echo '<td valign="top"><input name="discount" type="text" size="6" title="' . __('discount', 'membership') . '" style="width: 6em;" value="" />';
		echo "&nbsp;";
		echo "<select name='discount_type'>";
			echo "<option value='amt'>" . (isset($M_options['paymentcurrency']) ? $M_options['paymentcurrency'] : '$') . "</option>";
			echo "<option value='pct'>%</option>";
		echo "</select>";
		echo "<input type='hidden' name='discount_currency' value='" . $M_options['paymentcurrency'] . "'/>";
		echo "</td>";
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Start Date','membership') . $this->_tips->add_tip( __('The date that the Coupon code should be valid from.','membership') ) . '</th>';
		echo '<td valign="top"><input name="coupon_startdate" type="text" size="20" title="' . __('Start Date', 'membership') . '" style="width: 10em;" value="' . date("Y-m-d") . '" class="pickdate" /></td>';
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Expire Date','membership') . $this->_tips->add_tip( __('The date that the Coupon code should be valid until. Leave this blank if there is no end date.','membership') ) . '</th>';
		echo '<td valign="top"><input name="coupon_enddate" type="text" size="20" title="' . __('Expire Date', 'membership') . '" style="width: 10em;" value="" class="pickdate" /></td>';
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Subscription','membership') . $this->_tips->add_tip( __('The subscription that this coupon can be used on.','membership') ) . '</th>';
		echo '<td valign="top">';
		echo "<select name='coupon_sub_id'>";
			echo "<option value='0'>" . __('Any Subscription','membership') . "</option>";

			$subs = $this->get_subscriptions();
			if(!empty($subs)) {
				foreach($subs as $sub) {
					echo "<option value='" . $sub->id . "'>" . $sub->sub_name . "</option>";
				}
			}
		echo "</select>";
		echo "</td>";
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Allowed Uses','membership') . $this->_tips->add_tip( __('The number of times the coupon can be used. Leave this blank if there is no limit.','membership') ) . '</th>';
		echo '<td valign="top"><input name="coupon_uses" type="text" size="20" title="' . __('Allowed Uses', 'membership') . '" style="width: 6em;" value="" class="" /></td>';
		echo '</tr>';

		echo '</div>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

	}

	function editform() {

		global $M_options;

		if(empty($this->_coupon)) {
			$this->_coupon = $this->get_coupon();
		}

		echo '<table class="form-table">';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Coupon Code','membership') . $this->_tips->add_tip( __('The Coupon code should contain letters and numbers only.','membership') ) . '</th>';
		echo '<td valign="top"><input name="couponcode" type="text" size="50" title="' . __('Coupon Code', 'membership') . '" style="width: 50%;" value="' . esc_attr($this->_coupon->couponcode) . '" /></td>';
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Discount','membership') . $this->_tips->add_tip( __('The amount or percantage of a discount the coupon is valid for.','membership') ) . '</th>';
		echo '<td valign="top"><input name="discount" type="text" size="6" title="' . __('discount', 'membership') . '" style="width: 6em;" value="';
		if($this->_coupon->discount_type == 'amt') {
			echo esc_attr($this->_coupon->discount);
		} else {
			echo esc_attr(number_format_i18n($this->_coupon->discount, 2));
		}
		echo '" />';
		echo "&nbsp;";
		echo "<select name='discount_type'>";
			echo "<option value='amt' " . selected('amt', esc_attr($this->_coupon->discount_type), false) . ">" . (isset($M_options['paymentcurrency']) ? $M_options['paymentcurrency'] : '$') . "</option>";
			echo "<option value='pct'" . selected('pct', esc_attr($this->_coupon->discount_type), false) . ">%</option>";
		echo "</select>";
		echo "<input type='hidden' name='discount_currency' value='" . esc_attr($this->_coupon->discount_currency) . "'/>";
		echo "</td>";
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Start Date','membership') . $this->_tips->add_tip( __('The date that the Coupon code should be valid from.','membership') ) . '</th>';
		echo '<td valign="top"><input name="coupon_startdate" type="text" size="20" title="' . __('Start Date', 'membership') . '" style="width: 10em;" value="' . mysql2date("Y-m-d", $this->_coupon->coupon_startdate) . '" class="pickdate" /></td>';
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Expire Date','membership') . $this->_tips->add_tip( __('The date that the Coupon code should be valid until. Leave this blank if there is no end date.','membership') ) . '</th>';
		echo '<td valign="top"><input name="coupon_enddate" type="text" size="20" title="' . __('Expire Date', 'membership') . '" style="width: 10em;" value="';
		if(!empty($this->_coupon->coupon_enddate)) echo mysql2date("Y-m-d", $this->_coupon->coupon_enddate);
		echo '" class="pickdate" /></td>';
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Subscription','membership') . $this->_tips->add_tip( __('The subscription that this coupon can be used on.','membership') ) . '</th>';
		echo '<td valign="top">';
		echo "<select name='coupon_sub_id'>";
			echo "<option value='0' " . selected(0, $this->_coupon->coupon_sub_id) . ">" . __('Any Subscription','membership') . "</option>";

			$subs = $this->get_subscriptions();
			if(!empty($subs)) {
				foreach($subs as $sub) {
					echo "<option value='" . $sub->id . "' " . selected($sub->id, $this->_coupon->coupon_sub_id) . ">" . $sub->sub_name . "</option>";
				}
			}
		echo "</select>";
		echo "</td>";
		echo '</tr>';

		echo '<tr class="form-field form-required">';
		echo '<th style="" scope="row" valign="top">' . __('Allowed Uses','membership') . $this->_tips->add_tip( __('The number of times the coupon can be used. Leave this blank if there is no limit.','membership') ) . '</th>';
		echo '<td valign="top"><input name="coupon_uses" type="text" size="20" title="' . __('Allowed Uses', 'membership') . '" style="width: 6em;" value="';
		if($this->_coupon->coupon_uses != 0) echo esc_attr($this->_coupon->coupon_uses);
		echo '" class="" /></td>';
		echo '</tr>';

		echo '</div>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

	}

}

}

?>