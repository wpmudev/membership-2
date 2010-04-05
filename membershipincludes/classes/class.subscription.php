<?php
if(!class_exists('M_Subscription')) {

	class M_Subscription {

		var $id = false;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels');

		var $membership_levels;
		var $membership_rules;
		var $subscriptions;
		var $subscriptions_levels;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $subscription;
		var $levels = array();

		var $levelorder = array();

		function __construct( $id = false ) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = $wpdb->prefix . $table;
			}

			$this->id = $id;

		}

		function M_Subscription( $id = false ) {
			$this->__construct();
		}

		// Fields

		function sub_name() {

			if(empty($this->subscription)) {
				$sub = $this->get();

				if($sub) {
					return $sub->sub_name;
				} else {
					return false;
				}
			} else {
				return $this->subscription->sub_name;
			}

		}

		// Gets

		function get() {

			if($this->dirty) {
				$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions} WHERE id = %d", $this->id);

				$this->subscription = $this->db->get_row($sql);

				$this->dirty = false;
			}

			return $this->subscription;

		}

		function get_levels() {

			$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions_levels} sl INNER JOIN {$this->membership_levels} l on sl.level_id = l.id WHERE sub_id = %d ORDER BY level_order ASC", $this->id );

			$this->levels = $this->db->get_results( $sql );

			return $this->levels;

		}

		function toggleactivation( $force = false ) {

			if($force) {
				$sql = $this->db->prepare( "UPDATE {$this->subscriptions} SET sub_active = NOT sub_active WHERE id = %d", $this->id);
			} else {
				$sql = $this->db->prepare( "UPDATE {$this->subscriptions} SET sub_active = NOT sub_active WHERE id = %d AND sub_count = 0", $this->id);
			}

			$this->dirty = true;

			return $this->db->query($sql);


		}

		function togglepublic( $force = false ) {

			$sql = $this->db->prepare( "UPDATE {$this->subscriptions} SET sub_public = NOT sub_public WHERE id = %d", $this->id);

			$this->dirty = true;

			return $this->db->query($sql);


		}

		function delete( $force = false ) {

			if($force) {
				$sql = $this->db->prepare( "DELETE FROM {$this->subscriptions} WHERE id = %d", $this->id);
			} else {
				$sql = $this->db->prepare( "DELETE FROM {$this->subscriptions} WHERE id = %d AND sub_count = 0", $this->id);
			}

			$sql2 = $this->db->prepare( "DELETE FROM {$this->subscriptions_levels} WHERE sub_id = %d", $this->id);

			if($this->db->query($sql)) {

				$this->db->query($sql2);

				$this->dirty = true;

				return true;

			} else {
				return false;
			}

		}

		function add() {

			$this->dirty = true;

			if($this->id > 0) {
				$this->update();
			} else {

				$return = $this->db->insert($this->subscriptions, array('sub_name' => $_POST['sub_name']));
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

							if(isset($_POST['levelprice'][$level])) {
								$levelprice = esc_attr($_POST['levelprice'][$level]);
							} else {
								$levelprice = '';
							}

							if(isset($_POST['levelcurrency'][$level])) {
								$levelcurrency = esc_attr($_POST['levelcurrency'][$level]);
							} else {
								$levelcurrency = '';
							}



							// Calculate the level id
							$lev = explode('-', $level);
							if($lev[0] == 'level') {
								$level_id = (int) $lev[1];
								// write it to the database
								$this->db->insert($this->subscriptions_levels, array(	"sub_id" => $this->id,
																						"level_period" => $levelperiod,
																						"sub_type" => $levelmode,
																						"level_price" => $levelprice,
																						"level_currency" => $levelcurrency,
																						"level_order" => $count++,
																						"level_id" => $level_id
																						));

							}

						}

					}

				}

				return $this->id; // for now

			}

		}

		function update() {

			$this->dirty = true;

			if($this->id < 0) {
				$this->add();
			} else {

				$return = $this->db->update($this->subscriptions, array('sub_name' => $_POST['sub_name']), array('id' => $this->id));

				// Remove the existing rules for this subscription level
				$this->db->query( $this->db->prepare( "DELETE FROM {$this->subscriptions_levels} WHERE sub_id = %d", $this->id ) );

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

							if(isset($_POST['levelprice'][$level])) {
								$levelprice = esc_attr($_POST['levelprice'][$level]);
							} else {
								$levelprice = '';
							}

							if(isset($_POST['levelcurrency'][$level])) {
								$levelcurrency = esc_attr($_POST['levelcurrency'][$level]);
							} else {
								$levelcurrency = '';
							}

							// Calculate the level id
							$lev = explode('-', $level);
							if($lev[0] == 'level') {
								$level_id = (int) $lev[1];
								// write it to the database
								$this->db->insert($this->subscriptions_levels, array(	"sub_id" => $this->id,
																						"level_period" => $levelperiod,
																						"sub_type" => $levelmode,
																						"level_price" => $levelprice,
																						"level_currency" => $levelcurrency,
																						"level_order" => $count++,
																						"level_id" => $level_id
																						));

							}

						}

					}

				}
			}

			return true; // for now

		}


		// For display

		function sub_template() {
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
							<option value=''></option>
							<?php
								for($n = 1; $n <= 365; $n++) {
									?>
									<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
									<?php
								}
							?>
						</select>&nbsp;<?php _e('days','membership'); ?>

						<label for='levelprice[%level%]'><?php _e('Price : ','membership'); ?></label>
						<select name='levelprice[%level%]'>
							<option value=''></option>
							<?php
								for($n = 1; $n <= 300; $n++) {
									?>
									<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
									<?php
								}
							?>
						</select>&nbsp;
						<select name='levelcurrency[%level%]'>
							<option value=''></option>
							<option value='USD'>USD</option>
							<option value='EURO'>EURO</option>
							<option value='GBP'>GBP</option>
						</select>
						</div>
						<div class='levelinformation' style='float: right;'>
							<p class='description'>
								<strong><?php _e('Mode details','membership'); ?></strong><br/><br/>
								<?php _e('<strong>Finite</strong> - user remains at this level for a set period of time before ending');?><br/><br/>
								<?php _e('<strong>Indefinite</strong> - user remains at this level.');?><br/><br/>
								<?php _e('<strong>Serial</strong> - user remains at this level for a set period of time and is then renewed at the same level');?>
							</p>
						</div>
					</div>
				</div>
			</li>
			<?php
		}

		function sub_details() {

			$count = 1;

			$this->levels = $this->get_levels();

			if(!empty($this->levels)) {
				$count = 1;
				foreach($this->levels as $key => $level) {

					$levelid = 'level-' . $level->level_id . '-' . $count++;
					$this->levelorder[] = $levelid;

					?>
					<li class='sortable-levels' id="<?php echo $levelid; ?>" >
						<div class='joiningline'>&nbsp;</div>
						<div class="sub-operation" style="display: block;">
							<h2 class="sidebar-name"><?php echo esc_html($level->level_title); ?><span><a href='#remove' class='removelink' title='<?php _e("Remove this level from the subscription.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
							<div class="inner-operation">
								<div class='levelfields' style='float: left;'>
								<label for='levelmode[<?php echo $levelid; ?>]'><?php _e('Mode : ','membership'); ?></label>
								<select name='levelmode[<?php echo $levelid; ?>]'>
									<!-- <option value='trial' <?php if($level->sub_type == 'trial') echo "selected='selected'"; ?>>Trial</option> -->
									<option value='finite' <?php if($level->sub_type == 'finite') echo "selected='selected'"; ?>>Finite</option>
									<option value='indefinite' <?php if($level->sub_type == 'indefinite') echo "selected='selected'"; ?>>Indefinite</option>
									<option value='serial' <?php if($level->sub_type == 'serial') echo "selected='selected'"; ?>>Serial</option>
									<!-- <option value='sequential' <?php if($level->sub_type == 'sequential') echo "selected='selected'"; ?>>Sequential</option> -->
								</select>
								<label for='levelperiod[<?php echo $levelid; ?>]'><?php _e('Period : ','membership'); ?></label>
								<select name='levelperiod[<?php echo $levelid; ?>]'>
									<option value=''></option>
									<?php
										for($n = 1; $n <= 365; $n++) {
											?>
											<option value='<?php echo $n; ?>' <?php if($level->level_period == $n) echo "selected='selected'"; ?>><?php echo $n; ?></option>
											<?php
										}
									?>
								</select>&nbsp;<?php _e('days','membership'); ?>

								<label for='levelprice[<?php echo $levelid; ?>]'><?php _e('Price : ','membership'); ?></label>
								<select name='levelprice[<?php echo $levelid; ?>]'>
									<option value=''></option>
									<?php
										for($n = 1; $n <= 300; $n++) {
											?>
											<option value='<?php echo $n; ?>' <?php if($level->level_price == $n) echo "selected='selected'"; ?>><?php echo $n; ?></option>
											<?php
										}
									?>
								</select>&nbsp;
								<select name='levelcurrency[<?php echo $levelid; ?>]'>
									<option value=''></option>
									<option value='USD' <?php if($level->level_currency == 'USD') echo "selected='selected'"; ?>>USD</option>
									<option value='EURO' <?php if($level->level_currency == 'EURO') echo "selected='selected'"; ?>>EURO</option>
									<option value='GBP' <?php if($level->level_currency == 'GBP') echo "selected='selected'"; ?>>GBP</option>
								</select>
								</div>
								<div class='levelinformation' style='float: right;'>
									<p class='description'>
										<strong><?php _e('Mode details','membership'); ?></strong><br/><br/>
										<?php _e('<strong>Finite</strong> - user remains at this level for a set period of time before ending');?><br/><br/>
										<?php _e('<strong>Indefinite</strong> - user remains at this level.');?><br/><br/>
										<?php _e('<strong>Serial</strong> - user remains at this level for a set period of time and is then renewed at the same level');?>
									</p>
								</div>
							</div>
						</div>
					</li>
					<?php
				}
			}

		}

	}

}
?>