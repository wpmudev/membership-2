<?php
if ( !class_exists( 'M_Urlgroup' ) ) :
	class M_Urlgroup {

		var $build = 1;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $membermeta;
		var $communications;
		var $urlgroups;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $group;

		function __construct( $id = false ) {
			global $wpdb;

			$this->id = $id;
			$this->db = $wpdb;
			foreach ( $this->tables as $table ) {
				$this->$table = membership_db_prefix( $this->db, $table );
			}
		}

		function get_group() {
			if ( !$this->group ) {
				$this->group = !empty( $this->id )
					? $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->urlgroups} WHERE id = %d ", $this->id ) )
					: (object)array(
						'groupname'        => '',
						'groupurls'        => '',
						'stripquerystring' => 0,
						'isregexp'         => 0,
					);;
			}

			return $this->group;
		}

	 	function group_urls() {
			$group = $this->get_group();
			return !empty( $group ) ? $group->groupurls : false;
		}

		function group_urls_array() {
			$group = $this->get_group();
			return !empty( $group )
				? array_map( 'strtolower', array_filter( array_map( 'trim', explode( PHP_EOL, $group->groupurls ) ) ) )
				: false;
		}

		function render_form() {
			$this->get_group();

			$yesno = array(
				1 => esc_html__( 'Yes', 'membership' ),
				0 => esc_html__( 'No', 'membership' ),
			);

			?><table class="form-table">
				<tr class="form-field form-required">
					<th scope="row" valign="top"><?php esc_html_e( 'Group name', 'membership' ) ?></th>
					<td valign="top">
						<input name="groupname" type="text" size="50" title="<?php esc_attr_e( 'Group name', 'membership' ) ?>" style="width:50%" value="<?php echo esc_attr( stripslashes( $this->group->groupname ) ) ?>">
					</td>
				</tr>

				<tr class="form-field form-required">
					<th scope="row" valign="top"><?php esc_html_e( 'Page URLs', 'membership' ) ?></th>
					<td valign="top">
						<textarea id="groupurls" name="groupurls" rows="15" cols="40"><?php
							echo esc_textarea( stripslashes( $this->group->groupurls ) )
						?></textarea>
						<p class="description"><?php esc_html_e( "You should place each page URL or expression on a new line.", 'membership' ) ?></p>
					</td>
				</tr>

				<tr class="form-field form-required">
					<th scope="row" valign="top"><?php esc_html_e( 'Strip query strings from URL', 'membership' ) ?></th>
					<td valign="top" align="left">
						<select name="stripquerystring">
							<?php foreach ( $yesno as $key => $label ) : ?>
								<option value="<?php echo $key ?>"<?php selected( $key, $this->group->stripquerystring ) ?>><?php echo $label ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( "Remove any query string values prior to checking URL.", 'membership' ) ?></p>
					</td>
				</tr>

				<tr class="form-field form-required">
					<th scope="row" valign="top"><?php esc_html_e( 'Regular Expression', 'membership' ) ?></th>
					<td valign="top" align="left">
						<select name="isregexp">';
							<?php foreach ( $yesno as $key => $label ) : ?>
								<option value="<?php echo $key ?>"<?php selected( $key, $this->group->isregexp ) ?>><?php echo $label ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( "If any of the page URLs are regular expressions then set this to yes.", 'membership' ) ?></p>
					</td>
				</tr>
			</table><?php
		}

		function add() {
			return $this->db->insert( $this->urlgroups, array(
				"groupname"        => filter_input( INPUT_POST, 'groupname' ),
				"groupurls"        => implode( PHP_EOL, array_filter( array_map( 'trim', explode( PHP_EOL, filter_input( INPUT_POST, 'groupurls' ) ) ) ) ),
				"isregexp"         => (int)filter_input( INPUT_POST, 'isregexp', FILTER_VALIDATE_BOOLEAN ),
				"stripquerystring" => (int)filter_input( INPUT_POST, 'stripquerystring', FILTER_VALIDATE_BOOLEAN ),
			), array( '%s', '%s', '%d', '%d' ) );
		}

		function update() {
			return $this->db->update( $this->urlgroups, array(
				"groupname"        => filter_input( INPUT_POST, 'groupname' ),
				"groupurls"        => implode( PHP_EOL, array_filter( array_map( 'trim', explode( PHP_EOL, filter_input( INPUT_POST, 'groupurls' ) ) ) ) ),
				"isregexp"         => (int)filter_input( INPUT_POST, 'isregexp', FILTER_VALIDATE_BOOLEAN ),
				"stripquerystring" => (int)filter_input( INPUT_POST, 'stripquerystring', FILTER_VALIDATE_BOOLEAN ),
			), array( "id" => $this->id ), array( '%s', '%s', '%d', '%d' ), array( '%d' ) );
		}

		function delete() {
			return $this->db->delete( $this->urlgroups, array( 'id' => $this->id ), array( '%d' ) );
		}

		function url_matches( $host, $exclude = array() ) {
			$this->group = $this->get_group();
			
			$groups = array_map( 'strtolower', array_map( 'trim', explode( "\n", $this->group->groupurls ) ) );

			if ( $this->group->stripquerystring == 1 ) {
				$host = current( explode( '?', $host ) );
			}

			if ( $this->group->isregexp == 0 ) {
				// straight match
				$newgroups = array_map( 'untrailingslashit', $groups );
				$groups = array_merge( $groups, $newgroups );
				if ( in_array( strtolower( $host ), $groups ) ) {
					return true;
				} else {
					return false;
				}
			} else {
				//reg expression match
				$matchstring = "";
				foreach ( $groups as $key => $value ) {
					if ( $matchstring != "" )
						$matchstring .= "|";

					if ( stripos( $value, '\/' ) ) {
						$matchstring .= stripcslashes( $value );
					} else {
						$matchstring .= $value;
					}
				}
				return preg_match( "#^{$matchstring}$#i", $host );
			}
		}

	}
endif;

function M_add_to_global_urlgroup( $urls, $area = 'negative' ) {
	global $M_global_groups;

	if ( !is_array( $M_global_groups ) ) {
		$M_global_groups = array();
		$M_global_groups['positive'] = array();
		$M_global_groups['negative'] = array();
	}

	$urls = array_map( 'strtolower', array_filter( array_map( 'trim', (array)$urls ) ) );
	foreach ( $urls as $p ) {
		$M_global_groups[$area][] = $p;
	}
}