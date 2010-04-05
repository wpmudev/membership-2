<?php
if(!class_exists('M_Member_Search')) {

	class M_Member_Search extends WP_User_Search {

		var $sub_id = false;
		var $level_id = false;

		function M_Member_Search($search_term = '', $page = '', $sub_id = '', $level_id = '') {
			$this->search_term = $search_term;
			$this->raw_page = ( '' == $page ) ? false : (int) $page;
			$this->page = (int) ( '' == $page ) ? 1 : $page;
			$this->sub_id = $sub_id;
			$this->level_id = $level_id;

			$this->prepare_query();
			$this->query();
			$this->prepare_vars_for_template_usage();
			$this->do_paging();
		}

		function prepare_query() {
			global $wpdb;
			$this->first_user = ($this->page - 1) * $this->users_per_page;
			$this->query_limit = $wpdb->prepare(" LIMIT %d, %d", $this->first_user, $this->users_per_page);
			$this->query_sort = ' ORDER BY user_login';
			$search_sql = '';
			if ( $this->search_term ) {
				$searches = array();
				$search_sql = 'AND (';
				foreach ( array('user_login', 'user_nicename', 'user_email', 'user_url', 'display_name') as $col )
					$searches[] = $col . " LIKE '%$this->search_term%'";
				$search_sql .= implode(' OR ', $searches);
				$search_sql .= ')';
			}

			$this->query_from_where = "FROM $wpdb->users";
			$this->query_from_where .= ", $wpdb->usermeta WHERE $wpdb->users.ID = $wpdb->usermeta.user_id AND meta_key = '{$wpdb->prefix}capabilities'";

			if( $this->sub_id ) {
				$sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}membership_relationships WHERE sub_id = %d", $this->sub_id );

				$subs = $wpdb->get_col( $sql );

				if(!empty($subs)) {
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $subs) . ")";
				} else {
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (0)";
				}

			}

			if( $this->level_id ) {
				$sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}membership_relationships WHERE level_id = %d", $this->level_id );

				$levels = $wpdb->get_col( $sql );

				if(!empty($levels)) {
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (" . implode(',', $levels) . ")";
				} else {
					$this->query_from_where .= " AND {$wpdb->users}.ID IN (0)";
				}

			}

			$this->query_from_where .= " $search_sql";

		}

	}

}
?>