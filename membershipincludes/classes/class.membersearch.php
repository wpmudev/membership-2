<?php
if(!class_exists('M_Member_Search')) {

	class M_Member_Search extends WP_User_Search {

		var $sub_id = false;
		var $level_id = false;
		var $active = false;

		function M_Member_Search($search_term = '', $page = '', $sub_id = false, $level_id = false, $active = false) {
			$this->search_term = $search_term;
			$this->raw_page = ( '' == $page ) ? false : (int) $page;
			$this->page = (int) ( '' == $page ) ? 1 : $page;

			if(!empty($sub_id)) {
				$this->sub_id = $sub_id;
			}

			if(!empty($level_id)) {
				$this->level_id = $level_id;
			}

			if(!empty($active)) {
				$this->active = $active;
			}

			$this->prepare_query();
			$this->query();
			$this->prepare_vars_for_template_usage();
			$this->do_paging();
		}

		function do_paging() {
			if ( $this->total_users_for_query > $this->users_per_page ) { // have to page the results
				$args = array();
				if( ! empty($this->search_term) )
					$args['usersearch'] = urlencode($this->search_term);
				if( ! empty($this->role) )
					$args['role'] = urlencode($this->role);
				if( ! empty($this->sub_id) )
					$args['sub_id'] = urlencode($this->sub_id);
				if( ! empty($this->level_id) )
					$args['level_id'] = urlencode($this->level_id);

				$this->paging_text = paginate_links( array(
					'total' => ceil($this->total_users_for_query / $this->users_per_page),
					'current' => $this->page,
					'base' => 'admin.php?page=members&%_%',
					'format' => 'userspage=%#%',
					'add_args' => $args
				) );
				if ( $this->paging_text ) {
					$this->paging_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
						number_format_i18n( ( $this->page - 1 ) * $this->users_per_page + 1 ),
						number_format_i18n( min( $this->page * $this->users_per_page, $this->total_users_for_query ) ),
						number_format_i18n( $this->total_users_for_query ),
						$this->paging_text
					);
				}
			}
		}

		function prepare_query() {


			global $wpdb, $wp_version;

			$this->first_user = ($this->page - 1) * $this->users_per_page;

			$this->query_limit = $wpdb->prepare(" LIMIT %d, %d", $this->first_user, $this->users_per_page);
			$this->query_orderby = ' ORDER BY user_login';

			$search_sql = '';

			if ( $this->search_term ) {
				$searches = array();
				$search_sql = 'AND (';
				foreach ( array('user_login', 'user_nicename', 'user_email', 'user_url', 'display_name') as $col )
					$searches[] = $col . " LIKE '%$this->search_term%'";
				$search_sql .= implode(' OR ', $searches);
				$search_sql .= ')';
			}

			// The following code changes in WP3 and above

			// We are on version 3.0 or above
			$this->query_from = " FROM $wpdb->users";
			$this->query_where = " WHERE 1=1 $search_sql";

			if ( $this->role ) {
				$this->query_from .= " INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id";
				$this->query_where .= $wpdb->prepare(" AND $wpdb->usermeta.meta_key = '{$wpdb->prefix}capabilities' AND $wpdb->usermeta.meta_value LIKE %s", '%' . $this->role . '%');
			} elseif ( is_multisite() ) {
				$level_key = $wpdb->prefix . 'capabilities'; // wpmu site admins don't have user_levels
				$this->query_from .= ", $wpdb->usermeta";
				$this->query_where .= " AND $wpdb->users.ID = $wpdb->usermeta.user_id AND meta_key = '{$level_key}'";
			}


			if( $this->sub_id ) {
				$sql = $wpdb->prepare( "SELECT user_id FROM " . membership_db_prefix($wpdb, "membership_relationships") . " WHERE sub_id = %d", $this->sub_id );

				$subs = $wpdb->get_col( $sql );

				if(!empty($subs)) {
					$this->query_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $subs) . ")";
					// wp 2.9.2 and lower
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $subs) . ")";
				} else {
					$this->query_where .= " AND {$wpdb->users}.ID IN (0)";
					// wp 2.9.2 and lower
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (0)";
				}
			}

			if( $this->level_id ) {
				$sql = $wpdb->prepare( "SELECT user_id FROM " . membership_db_prefix($wpdb, "membership_relationships") . " WHERE level_id = %d", $this->level_id );

				$levels = $wpdb->get_col( $sql );

				if(!empty($levels)) {
					$this->query_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $levels) . ")";
					// wp 2.9.2 and lower
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $levels) . ")";
				} else {
					$this->query_where .= " AND {$wpdb->users}.ID IN (0)";
					// wp 2.9.2 and lower
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (0)";
				}

			}

			if($this->active) {
				$sql = $wpdb->prepare( "SELECT user_id FROM " . $wpdb->usermeta . " WHERE meta_key = '" . membership_db_prefix($wpdb, 'membership_active', false) . "' AND meta_value = 'no'" );
				$actives = $wpdb->get_col( $sql );

				if(!empty($actives)) {
					if($this->active == 'yes') {
						$this->query_where .= " AND {$wpdb->users}.ID NOT IN (" . implode(',', $actives) . ")";
						// wp 2.9.2 and lower
						$this->query_from_where .= " AND {$wpdb->users}.ID NOT IN (" . implode(',', $actives) . ")";
					} else {
						// no
						$this->query_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $actives) . ")";
						// wp 2.9.2 and lower
						$this->query_from_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $actives) . ")";
					}
				} else {
					if($this->active == 'yes') {
						$this->query_where .= " AND {$wpdb->users}.ID NOT IN (0)";
						// wp 2.9.2 and lower
						$this->query_from_where .= " AND {$wpdb->users}.ID NOT IN (0)";
					} else {
						// no
						$this->query_where .= " AND {$wpdb->users}.ID IN (0)";
						// wp 2.9.2 and lower
						$this->query_from_where .= " AND {$wpdb->users}.ID IN (0)";
					}
				}
			}

			do_action_ref_array( 'pre_user_search', array( &$this ) );

		}

	}

}
?>