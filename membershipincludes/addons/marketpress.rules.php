<?php
/*
Addon Name: Default MarketPress Rules
Description: Main MarketPress rules
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

class M_Marketpress extends M_Rule {

	var $name = 'marketpress';
	var $label = 'MarketPress Pages';

	var $rulearea = 'public';

	var $pages = array();

	function on_creation() {

		$this->pages = array( 	'mp_global_products' 		=> __('Global Products','membership'),
								'mp_global_categories'		=> __('Global Categories', 'membership'),
								'mp_global_tags'		=> __('Global Tags', 'membership'),
								'product_list'		=> __('Product List', 'membership'),
								'cart'		=> __('Cart', 'membership'),
								'orderstatus'		=> __('Order Status', 'membership')
								);

	}

	function admin_main($data) {

		global $wpdb, $M_options;

		if(!$data) $data = array();

		?>
		<div class='level-operation' id='main-marketpress'>
			<h2 class='sidebar-name'><?php _e('MarketPress Pages', 'membership');?><span><a href='#remove' id='remove-marketpress' class='removelink' title='<?php _e("Remove MarketPress Pages from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the MarketPress pages to be covered by this rule by checking the box next to the relevant page name.','membership'); ?></p>
				<?php

					?>
					<table cellspacing="0" class="widefat fixed">
						<thead>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Page type', 'membership'); ?></th>
						</tr>
						</thead>
						<tfoot>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Page type', 'membership'); ?></th>
						</tr>
						</tfoot>

						<tbody>
						<?php
						if(!empty($this->pages)) {

							foreach($this->pages as $key => $value) {
								if(!empty($value)) {
									?>
									<tr valign="middle" class="alternate" id="page-<?php echo esc_attr(stripslashes(trim($key))); ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo esc_attr(stripslashes(trim($key))); ?>" name="marketpress[]" <?php if(in_array(esc_attr(stripslashes(trim($key))), $data)) echo 'checked="checked"'; ?>>
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
							<tr valign="middle" class="alternate" id="page-<?php echo $key; ?>">
								<td class="column-name" colspan='2'>
									<?php echo __('You have no pages available, please ensure you have MarketPress installed.','membership'); ?>
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

	function on_positive($data) {

		global $M_options;

		$this->data = $data;

		add_filter( 'membership_notallowed_pagenames', array(&$this , 'build_notallowed_pages') );

	}

	function on_negative($data) {

		global $M_options;

		$this->data = $data;

		add_filter( 'membership_notallowed_pagenames', array(&$this , 'get_notallowed_pages') );

	}

	function build_notallowed_pages ( $pages ) {

		if(!is_array($pages)) $pages = array();

		foreach($this->pages as $key => $value) {
			if(!in_array( $key, (array) $this->data )) {
				// it's not in the list so it's a not allowed page
				$pages[] = $key;
			}
		}

		$pages = array_unique( $pages );

		return $pages;

	}

	function get_notallowed_pages( $pages ) {

			if(!is_array($pages)) $pages = array();

			foreach($this->pages as $key => $value) {
				if(in_array( $key, (array) $this->data )) {
					// it's not in the list so it's a not allowed page
					$pages[] = $key;
				}
			}

			$pages = array_unique( $pages );
			return $pages;

	}

}

M_register_rule('marketpress', 'M_Marketpress', 'content');

?>