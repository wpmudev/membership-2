<?php
/**
 * Add-on: Allow Search-Engines to index protected content.
 *
 * @since  1.0.1.0
 */
class MS_Addon_Searchindex extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'addon_searchindex';

	/**
	 * This is the type used to identify the special search-index membership.
	 *
	 * @since  1.0.1.0
	 * @var  string
	 */
	const MEMBERSHIP_TYPE = 'searchindex';

	/**
	 * Holds the Special Membership. The value is assigned by the function
	 * self::add_membership() and is used later in the apply_membership() function.
	 *
	 * @since  1.0.1.0
	 * @var  MS_Model_Membership
	 */
	protected $membership = null;

	/**
	 * The First Click Free setting.
	 *
	 * @since  1.0.1.0
	 * @var  bool
	 */
	protected $first_click_free = true;

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.1.0
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->first_click_free = lib3()->is_true(
				$this->get_setting( 'first_click_free' )
			);

			$this->add_filter(
				'ms_model_membership_is_system',
				'is_system',
				10, 2
			);

			$this->add_filter(
				'ms_model_membership_is_valid_type',
				'is_valid_type',
				10, 2
			);

			$this->add_filter(
				'ms_model_membership_get_types',
				'get_types'
			);

			$this->add_filter(
				'ms_helper_listtable_membership_column_name_actions',
				'list_table_actions',
				10, 2
			);

			$this->add_filter(
				'ms_helper_listtable_memberships_name_badge',
				'list_table_badge',
				10, 2
			);

			$this->add_action(
				'ms_init_done',
				'apply_membership'
			);

			// Last action in the init sequence.
			$this->create_membership();
		} else {
			$this->add_filter(
				'ms_model_membership_get_memberships',
				'hide_membership',
				10, 2
			);
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.0.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Search Index', 'membership2' ),
			'description' => __( 'Allow Search Engines to index protected content.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-search',
			'details' => array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => sprintf(
						'%s<br><br>%s',
						__( 'The special Membership "<b>Search Index</b>" is available in your Protection Rules page.<br>All content that is made available for that Membership is always visible to search engine crawlers.', 'membership2' ),
						__( 'Supported Search Engines: Google, Yahoo, Bing', 'membership2' )
					),
				),
				array(
					'id' => 'first_click_free',
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'title' => __( 'First Click Free', 'membership2' ),
					'desc' => sprintf(
						__( 'All content that is available for Search engines is also available for all visitors that <b>directly arrive from a search engine</b> ("%sFirst Click Free%s" policy)<br>Disabling this feature might earn your site penalties by Google', 'membership2' ),
						'<a href="http://googlewebmastercentral.blogspot.com/2008/10/first-click-free-for-web-search.html" target="_blank">',
						'</a>'
					),
					'class' => 'has-labels',
					'before' => __( 'Disable "First Click Free"', 'membership2' ),
					'after' => __( 'Allow "First Click Free"', 'membership2' ),
					'value' => $this->first_click_free,
					'ajax_data' => array(
						'action' => $this->ajax_action(),
						'field' => 'first_click_free',
					),
				),
			),
		);

		return $list;
	}

	/**
	 * Adds a special membership that represents the search index.
	 *
	 * @since 1.0.1.0
	 */
	protected function create_membership() {
		$this->membership = MS_Model_Membership::_get_system_membership(
			self::MEMBERSHIP_TYPE,
			true
		);
	}

	/**
	 * Filters the membership list and removes the search-index Membership from
	 * results.
	 *
	 * @since  1.0.1.0
	 * @param  array $list List of MS_Model_Membership items.
	 * @param  array $args Search arguments.
	 * @return array Modified membership list.
	 */
	public function hide_membership( $list, $args ) {
		foreach ( $list as $key => $item ) {
			if ( $this->is_system( false, $item->type ) ) {
				unset( $list[$key] );
			}
		}

		return $list;
	}

	/**
	 * Returns a list of all Membership types and Type names.
	 *
	 * @since  1.0.1.0
	 * @param  array $types Default list of type names.
	 * @return array Modified list of type names.
	 */
	public function get_types( $types ) {
		$types[self::MEMBERSHIP_TYPE] = __( 'Search Index', 'membership2' );

		return $types;
	}

	/**
	 * Returns true if the specified Membership is the search-index membership.
	 *
	 * @since  1.0.1.0
	 * @param  bool $result Default response.
	 * @param  string $membership_type The Membership type to check.
	 * @return bool Is-System flag.
	 */
	public function is_system( $result, $membership_type ) {
		if ( self::MEMBERSHIP_TYPE == $membership_type ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Checks if the specified string is a valid Membership-Type identifier.
	 *
	 * @since  1.0.1.0
	 * @param  bool $result Default response.
	 * @param  string $membership_type The Membership type to check.
	 * @return bool Is-Valid-Type flag.
	 */
	public function is_valid_type( $result, $membership_type ) {
		if ( self::MEMBERSHIP_TYPE == $membership_type ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Modify the list-table actions for the Search-index membership in the
	 * Membership list.
	 *
	 * @since  1.0.1.0
	 * @param  array $actions Actions displayed in the list.
	 * @param  MS_Model_Membership $membership The membership that is parsed.
	 * @return array Actions displayed in the list.
	 */
	public function list_table_actions( $actions, $membership ) {
		if ( self::MEMBERSHIP_TYPE == $membership->type ) {
			unset( $actions['delete'] );
		}

		return $actions;
	}

	/**
	 * Define a custom Badge that is displayed next to the Membership name in
	 * the Membership list.
	 *
	 * @since  1.0.1.0
	 * @param  string $actions HTML code of the badge to display.
	 * @param  MS_Model_Membership $membership The membership that is parsed.
	 * @return string HTML code of the badge to display.
	 */
	public function list_table_badge( $badge, $membership ) {
		if ( self::MEMBERSHIP_TYPE == $membership->type ) {
			$badge = sprintf(
				'<span class="ms-badge" data-wpmui-tooltip="%2$s" data-width="180">%1$s</span>',
				__( 'Search-Engine', 'membership2' ),
				__( 'Define what content can be indexed by a Search Engine', 'membership2' )
			);
		}

		return $badge;
	}

	/**
	 * Adds the search-index membership to the current member if the visitor is
	 * a search engine crawler or the first-click condition applies.
	 *
	 * @since  1.0.1.0
	 * @param  MS_Model_Plugin $model The object that was just initialized.
	 */
	public function apply_membership( $model ) {
		if ( $this->is_searchengine() ) {
			// A search engine crawls the site.
			$this->member->add_membership( $this->membership->id );
		} elseif ( $this->is_first_click() ) {
			// Current request is directly refererd by a search engine.
			$this->member->add_membership( $this->membership->id );
		}
	}

	/**
	 * Returns true if the current request is made by a search engine.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	protected function is_searchengine() {
		$result = false;

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		if ( $ref ) {
			// Bots never have a referer value!
		} else {
			$host = strtolower( gethostbyaddr( $ip ) );

			$hosts = 'google|yahoo|msn|bing';
			$agents = 'google|slurp|msnbot';

			$valid_host = preg_match( "/$hosts/", $host ) > 0;
			$valid_agent = preg_match( "/$agents/", $agent ) > 0;

			$result = $valid_host && $valid_agent;
		}

		return $result;
	}

	/**
	 * Returns true if the current user directly arrived from a search engine.
	 *
	 * @see Code taken from PopUp Pro "Referer" Rule.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	protected function is_first_click() {
		$response = false;

		if ( $this->first_click_free ) {
			$ref = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

			$patterns = array(
				'/search?',
				'.google.',
				'web.info.com',
				'search.',
				'del.icio.us/search',
				'delicious.com/search',
				'soso.com',
				'/search/',
				'.yahoo.',
				'.bing.',
			);

			foreach ( $patterns as $url ) {
				if ( false !== stripos( $ref, $url ) ) {
					if ( $url == '.google.' ) {
						if ( $this->is_googlesearch( $ref ) ) {
							$response = true;
						} else {
							$response = false;
						}
					} else {
						$response = true;
					}
					break;
				}
			}
		}

		return $response;
	}

	/**
	 * Checks if the referrer is a google web-source.
	 *
	 * @see Code taken from PopUp Pro "Referer" Rule.
	 *
	 * @since  1.0.1.0
	 * @param  string $referrer
	 * @return bool
	 */
	protected function is_googlesearch( $referrer = '' ) {
		$response = true;

		// Get the query strings and check its a web source.
		$qs = parse_url( $referrer, PHP_URL_QUERY );
		$qget = array();

		foreach ( explode( '&', $qs ) as $keyval ) {
			$kv = explode( '=', $keyval );
			if ( 2 == count( $kv ) ) {
				$qget[ trim( $kv[0] ) ] = trim( $kv[1] );
			}
		}

		if ( isset( $qget['source'] ) ) {
			$response = $qget['source'] == 'web';
		}

		return $response;
	}

}