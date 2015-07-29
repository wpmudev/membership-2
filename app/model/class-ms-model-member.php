<?php
/**
 * Member model.
 *
 * Defines several details about a WordPress user.
 * The Member object allows us to quickly check if the user did subscribe to a
 * certain membership, and other useful stuff.
 *
 * Note that all properties are declared protected but they can be access
 * directly (e.g. `$membership->type` to get the type value).
 * There are magic methods \_\_get() and \_\_set() that do some validation before
 * accessing the properties.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Member extends MS_Model {

	/**
	 * Members search constants.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	const SEARCH_ONLY_MEMBERS = 'only members';

	/**
	 * Members search constants.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	const SEARCH_NOT_MEMBERS = 'not_members';

	/**
	 * Members search constants.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	const SEARCH_ALL_USERS = 'all_users';

	/**
	 * Cache for function is_admin_user()
	 *
	 * @since  1.0.0
	 * @internal
	 * @var bool[]
	 */
	static protected $_is_admin_user = array();

	/**
	 * Cache for function is_normal_admin()
	 *
	 * @since  1.0.0
	 * @internal
	 * @var bool[]
	 */
	static protected $_is_normal_admin = array();

	/**
	 * Cache for function is_simulated_user()
	 *
	 * @since  1.0.0
	 * @internal
	 * @var bool[]
	 */
	static protected $_is_simulated_user = array();

	/**
	 * Cache for function is_normal_user()
	 *
	 * @since  1.0.0
	 * @internal
	 * @var bool[]
	 */
	static protected $_is_normal_user = array();

	/**
	 * Member's active subscriptions.
	 *
	 * Note: This field is populated by MS_Factory when the Member instance is
	 * created.
	 *
	 * @since  1.0.0
	 * @var array {
	 *     @type int $membership_id The membership ID.
	 *     @type MS_Model_Relationship The membership relationship model object.
	 * }
	 */
	protected $subscriptions = array();

	/**
	 * Indicator if the user is an active M2 Member.
	 *
	 * This is a convenience/redudant flag to speed up SQL queries.
	 * Actually everyone that has an active or trial status membership is
	 * considered an active member.
	 *
	 * This flag is set when:
	 * - In MS_Model_Relationship, when a payment is recorded
	 *   via add_payment()
	 *
	 * This flag is reset when:
	 * - In MS_Model_Relationship, when a subscription is deactivated
	 *   via check_membership_status()
	 *
	 * @since  1.0.0
	 * @var boolean
	 */
	protected $is_member = false;

	/**
	 * Member's username.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $user_login.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $username = '';

	/**
	 * Member's email.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $user_email.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $email = '';

	/**
	 * Member's name.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $user_nicename.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $name = '';

	/**
	 * Member's first name.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $first_name
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $first_name = '';

	/**
	 * Member's last name.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $last_name.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $last_name = '';

	/**
	 * Member's password.
	 *
	 * Used when registering.
	 *
	 * @since  1.0.0
	 * @internal
	 * @var string
	 */
	protected $password = '';

	/**
	 * Member's password confirmation.
	 *
	 * Used when registering.
	 *
	 * @since  1.0.0
	 * @internal
	 * @var string
	 */
	protected $password2 = '';

	/**
	 * Member's gateway profiles info.
	 *
	 * Save gateway IDs.
	 *
	 * @since  1.0.0
	 * @var array {
	 *     Return structure: $gateway[ $field ] => $value;
	 *
	 *     @type string $gateway_id The gateway id.
	 *     @type string $field The field to store.
	 *     @type mixed $value The field value to store.
	 * }
	 */
	protected $gateway_profiles = array();

	/**
	 * The associated WP_User object
	 *
	 * @since  1.0.0
	 * @internal
	 * @var WP_User
	 */
	protected $wp_user = null;

	/**
	 * Custom data can be used by other plugins via the set_custom_data() and
	 * get_custom_data() functions.
	 *
	 * This can be used to store additional information on user-level, e.g.
	 * settings needed by some Add-ons or even by other plugins.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $custom_data = array();


	//
	//
	//
	// -------------------------------------------------------------- COLLECTION

	/**
	 * Get current member.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @return MS_Model_Member The current member.
	 */
	static public function get_current_member() {
		return MS_Factory::load( 'MS_Model_Member', get_current_user_id() );
	}

	/**
	 * Checks if user-signup is enabled for this site or not.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return bool
	 */
	static public function can_register() {
		static $Signup_Allowed = null;

		if ( null === $Signup_Allowed ) {
			$Signup_Allowed = false;

			if ( is_multisite() ) {
				$reg_option = get_site_option( 'registration', 'none' );
				if ( in_array( $reg_option, array( 'all', 'user' ) ) ) {
					$Signup_Allowed = true;
				}
			} else {
				if ( get_option( 'users_can_register' ) ) {
					$Signup_Allowed = true;
				}
			}
		}

		return apply_filters(
			'ms_member_can_register',
			$Signup_Allowed
		);
	}

	/**
	 * Allows users to register for this site.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return bool
	 */
	static public function allow_registration() {
		if ( self::can_register() ) { return; }

		if ( is_multisite() ) {
			$reg_option = get_site_option( 'registration', 'none' );
			if ( 'blog' == $reg_option ) {
				// Creation of new blogs is allowd. Add User-Registration.
				update_site_option( 'registration', 'all' );
			} else {
				// Only enable user registration and keep blogs disabled.
				update_site_option( 'registration', 'user' );
			}
		} else {
			// Simply enable registration on single sites.
			update_option( 'users_can_register', true );
		}
	}

	/**
	 * Get members total count.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param $args The query user args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @return int The count.
	 */
	public static function get_members_count( $args = null ) {
		$args = self::get_query_args( $args, self::SEARCH_ALL_USERS );
		$args['number'] = 0;
		$args['count_total'] = true;
		$wp_user_search = new WP_User_Query( $args );

		return apply_filters(
			'ms_model_member_get_members_count',
			$wp_user_search->get_total()
		);
	}

	/**
	 * Get members IDs.
	 * The IDs are cached and only fetched once for each set of $args.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  $args The query user args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @return array List of member IDs
	 */
	public static function get_member_ids( $args = null, $search_option = self::SEARCH_ALL_USERS ) {
		static $Members = array();
		$key = json_encode( $args );

		if ( ! isset( $Members[$key] ) ) {
			$args = self::get_query_args( $args, $search_option );
			$wp_user_search = new WP_User_Query( $args );
			$users = $wp_user_search->get_results();
			$members = array();

			foreach ( $users as $user_id ) {
				$members[] = $user_id;
			}

			$Members[$key] = apply_filters(
				'ms_model_member_get_member_ids',
				$members,
				$args
			);
		}

		return $Members[$key];
	}

	/**
	 * Get members.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  $args The query user args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @return MS_Model_Member[] The selected members.
	 */
	public static function get_members( $args = null, $search_option = self::SEARCH_ALL_USERS ) {
		$members = array();
		$ids = self::get_member_ids( $args, $search_option );

		foreach ( $ids as $user_id ) {
			$members[] = MS_Factory::load( 'MS_Model_Member', $user_id );
		}

		return apply_filters(
			'ms_model_member_get_members',
			$members,
			$ids,
			$args
		);
	}

	/**
	 * Get usernames.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param $args The query user args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @param string $search_option The search options (only members, not members, all users).
	 * @return array {
	 *     @type int $id The user_id.
	 *     @type string $username The username.
	 * }
	 */
	public static function get_usernames( $args = null, $search_option = self::SEARCH_ONLY_MEMBERS, $return_array = true ) {
		$members = array();

		if ( $return_array ) {
			$members[0] = __( 'Select a user', MS_TEXT_DOMAIN );
		}

		$args['fields'] = array( 'ID', 'user_login' );
		$args['number'] = 0;
		$args = self::get_query_args( $args, $search_option );
		$wp_user_search = new WP_User_Query( $args );
		$users = $wp_user_search->get_results();

		foreach ( $users as $user ) {
			if ( ! self::is_admin_user( $user->ID ) ) {
				if ( $return_array ) {
					$members[ $user->ID ] = $user->user_login;
				} else {
					$members[] = array(
						'id' => $user->ID,
						'text' => $user->user_login,
					);
				}
			}
		}

		return apply_filters(
			'ms_model_member_get_members_usernames',
			$members,
			$return_array
		);
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this model.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param $args The query user args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @param string $search_option The search options (only members, not members, all users).
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args = null, $search_option = self::SEARCH_ONLY_MEMBERS ) {
		$defaults = apply_filters(
			'ms_model_member_get_query_args_defaults',
			array(
				'order' => 'DESC',
				'orderby' => 'ID',
				'number' => 20,
				'offset' => 0,
				'fields' => 'ID',
			)
		);

		$args = lib2()->array->get( $args );
		lib2()->array->equip( $args, 'meta_query' );

		if ( 'none' !== $args['meta_query'] ) {
			$args['meta_query'] = lib2()->array->get( $args['meta_query'] );

			switch ( $search_option ) {
				case self::SEARCH_ONLY_MEMBERS:
					$args['meta_query'] = array(
						array(
							'key'   => 'ms_is_member',
							'value' => true,
						),
					);
					break;

				case self::SEARCH_NOT_MEMBERS:
					/*
					 * This does a recursive call to first get all member IDs
					 */
					$members = self::get_member_ids(
						null,
						self::SEARCH_ONLY_MEMBERS
					);

					$args['exclude'] = $members;
					break;

				case self::SEARCH_ALL_USERS:
				default:
					break;
			}
		} else {
			unset( $args['meta_query'] );
		}

		if ( MS_Plugin::is_network_wide() ) {
			$defaults['blog_id'] = false;
		}

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_member_get_query_args',
			$args,
			$defaults
		);
	}

	/**
	 * Returns the current user ID.
	 * This function can be called before the init action hook.
	 *
	 * Much of this logic is taken from wp-includes/pluggable.php
	 *
	 * @since  1.0.0
	 * @internal
	 * @return int|false
	 */
	public static function get_user_id() {
		static $User_id = false;

		if ( $User_id ) {
			// We already found the user-id, no need to do it again.
			return $User_id;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			// A cron request has no user credentials...
			return 0;
		}

		$cookie = wp_parse_auth_cookie();

		if ( ! $cookie ) {
			// Missing, expired or corrupt cookie.
			return 0;
		}

		$scheme = $cookie['scheme'];
		$username = $cookie['username'];
		$hmac = $cookie['hmac'];
		$token = $cookie['token'];
		$expiration = $cookie['expiration'];

		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			// Invalid username.
			return 0;
		}

		$pass_frag = substr( $user->user_pass, 8, 4 );
		$key = wp_hash( $username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );
		$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
		$hash = hash_hmac( $algo, $username . '|' . $expiration . '|' . $token, $key );

		if ( ! hash_equals( $hash, $hmac ) ) {
			// Forged/expired cookie value.
			return 0;
		}

		// Remember the user-ID so we don't have to validate everything again.
		$User_id = $user->ID;

		return $User_id;
	}

	/**
	 * Verify is user is logged in.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @return boolean True if user is logged in.
	 */
	public static function is_logged_in() {
		$logged = is_user_logged_in();

		return apply_filters( 'ms_member_is_logged_in', $logged );
	}

	/**
	 * Verify is user is Admin user.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  int|false $user_id Optional. The user ID. Default to current user.
	 * @param  bool $deprecated Do not use.
	 * @return boolean True if user is admin.
	 */
	static public function is_admin_user( $user_id = false ) {
		if ( ! isset( self::$_is_admin_user[ $user_id ] ) ) {
			$is_admin = false;
			$default_user_id = null;

			if ( empty( $user_id ) ) {
				$default_user_id = $user_id;
				$user_id = self::get_user_id();
			}

			if ( is_super_admin( $user_id ) ) {
				// Superadmin always is considered admin user, no discussion...
				$is_admin = true;
			} else {
				/**
				 * Use the capability defined by the main plugin controller.
				 *
				 * This capability defines which user is considered admin user.
				 * An Admin user has full permissions to edit M2 settings.
				 *
				 * To modify the capability:
				 *   Use filter `ms_admin_user_capability`  or
				 *   define( 'MS_ADMIN_CAPABILITY', '...' )
				 *
				 * @var string|bool A WordPress capability or boolean false.
				 */
				$controller = MS_Plugin::instance()->controller;
				$capability = $controller->capability;

				if ( ! empty( $capability ) ) {
					if ( empty( $user_id ) ) {
						$is_admin = current_user_can( $capability );
					} else {
						$is_admin = user_can( $user_id, $capability );
					}
				}

				$is_admin = apply_filters(
					'ms_model_member_is_admin_user',
					$is_admin,
					$user_id,
					$capability
				);
			}

			self::$_is_admin_user[ $user_id ] = $is_admin;

			if ( null !== $default_user_id ) {
				self::$_is_admin_user[ $default_user_id ] = $is_admin;
			}
		}

		return self::$_is_admin_user[ $user_id ];
	}

	/**
	 * Verify is user is Admin user and simulation mode is deactivated.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int|false $user_id Optional. The user ID. Default to current user.
	 * @return boolean
	 */
	static public function is_normal_admin( $user_id = false ) {
		if ( ! isset( self::$_is_normal_admin[$user_id] ) ) {
			$res = self::is_admin_user( $user_id )
				&& ! MS_Factory::load( 'MS_Model_Simulate' )->is_simulating();
			self::$_is_normal_admin[$user_id] = $res;

			if ( empty( $user_id ) ) {
				self::$_is_normal_admin[ get_current_user_id() ] = $res;
			}
		}

		return self::$_is_normal_admin[$user_id];
	}

	/**
	 * Verify is user is Admin user and simulation mode is active.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int|false $user_id Optional. The user ID. Default to current user.
	 * @return boolean
	 */
	static public function is_simulated_user( $user_id = false ) {
		if ( ! isset( self::$_is_simulated_user[$user_id] ) ) {
			$res = self::is_admin_user( $user_id )
				&& MS_Factory::load( 'MS_Model_Simulate' )->is_simulating();
			self::$_is_simulated_user[$user_id] = $res;

			if ( empty( $user_id ) ) {
				self::$_is_simulated_user[ get_current_user_id() ] = $res;
			}
		}

		return self::$_is_simulated_user[$user_id];
	}

	/**
	 * Verify is user is not Admin user and simulation mode is deactivated.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int|false $user_id Optional. The user ID. Default to current user.
	 * @return boolean
	 */
	static public function is_normal_user( $user_id = false ) {
		if ( ! isset( self::$_is_normal_user[$user_id] ) ) {
			// Simlation is only activated when the current user is an Admin.
			$res = ! self::is_admin_user( $user_id );
			self::$_is_normal_user[$user_id] = $res;

			if ( empty( $user_id ) ) {
				self::$_is_normal_user[ get_current_user_id() ] = $res;
			}
		}

		return self::$_is_normal_user[$user_id];
	}

	/**
	 * Get email addresses of all admin users.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @return string[] The admin emails.
	 */
	public static function get_admin_user_emails() {
		$admins = array();

		$args = array(
			'role' => 'administrator',
			'fields' => array( 'ID', 'user_email' ),
		);

		$wp_user_search = new WP_User_Query( $args );
		$users = $wp_user_search->get_results();

		if ( ! empty ($users ) ) {
			foreach ( $users as $user ) {
				$admins[ $user->user_email ] = $user->user_email;
			}
		}

		return apply_filters(
			'ms_model_member_get_admin_user_emails',
			$admins
		);
	}

	/**
	 * Get username from user_id.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int $user_id The user ID to get username.
	 * @return string The username.
	 */
	public static function get_username( $user_id ) {
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );

		return apply_filters(
			'ms_model_member_get_username',
			$member->username,
			$user_id
		);
	}

	/**
	 * Search for orphaned relationships and remove them.
	 *
	 * We write a custom SQL query for this, as solving it with a meta-query
	 * structure is very performance intense and requires at least two queries
	 * and a loop...
	 *
	 * For additional performance we will only do this check once every hour.
	 *
	 * Note: We cannot use the hook 'delete_user' to do this, because in
	 * Multisite users are deleted via the Main network admin; however, there
	 * we do not have access to the site data; especially if Plugin is not
	 * network enabled...
	 *
	 * @todo Change this to use WP-Cron instead of own implementation...
	 *
	 * @since  1.0.0
	 * @internal
	 */
	static public function clean_db() {
		$timestamp = absint( MS_Factory::get_transient( 'ms_member_clean_db' ) );
		$elapsed = time() - $timestamp;

		if ( $elapsed > 3600 ) {
			// Last check is longer than 1 hour ago. Check again.
			MS_Factory::set_transient( 'ms_member_clean_db', time(), 3600 );
		} else {
			// Last check was within past hour. Do nothing yet...
			return;
		}

		global $wpdb;

		// Find all Relationships that have no post-author.
		$sql = "
		SELECT p.ID
		FROM {$wpdb->posts} p
		WHERE p.post_type=%s
		AND NOT EXISTS (
			SELECT 1
			FROM {$wpdb->users} u
			WHERE u.ID = p.post_author
		);
		";

		$sql = $wpdb->prepare(
			$sql,
			MS_Model_Relationship::get_post_type()
		);

		// Delete these Relationships!
		$items = $wpdb->get_results( $sql );
		foreach ( $items as $item ) {
			$junk = MS_Factory::load( 'MS_Model_Relationship', $item->ID );
			$junk->delete();
		}
	}

	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Returns a list of variables that should be included in serialization,
	 * i.e. these values are the only ones that are stored in DB
	 *
	 * @since  1.0.0
	 * @internal
	 * @return array
	 */
	public function __sleep() {
		return array(
			'id',
			'username',
			'email',
			'name',
			'first_name',
			'last_name',
			'subscriptions',
			'is_member',
			'active',
			'gateway_profiles',
			'custom_data',
		);
	}

	/**
	 * Validates the object right after it was loaded/initialized.
	 *
	 * We ensure that the custom_data field is an array.
	 *
	 * @since  1.0.0
	 */
	public function prepare_obj() {
		parent::prepare_obj();

		if ( ! is_array( $this->custom_data ) ) {
			$this->custom_data = array();
		}
	}

	/**
	 * Save member.
	 *
	 * Create a new user is id is empty.
	 * Save member fields to wp_user and wp_usermeta tables.
	 * Set cache for further use in MS_Factory::load.
	 * The usermeta are prefixed with 'ms_'.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return MS_Model_Member The saved member object.
	 */
	public function save() {
		$class = get_class( $this );

		if ( empty( $this->id ) ) {
			$this->create_new_user();
		}

		if ( isset( $this->username ) ) {
			$wp_user = new stdClass();
			$wp_user->ID = $this->id;
			$wp_user->nickname = $this->username;
			$wp_user->user_nicename = $this->username;
			$wp_user->first_name = $this->first_name;
			$wp_user->last_name = $this->last_name;

			if ( ! empty( $this->password )
				&& $this->password == $this->password2
			) {
				$wp_user->user_pass = $this->password;
			}
			wp_update_user( get_object_vars( $wp_user ) );
		}

		// Serialize our plugin meta data
		$data = MS_Factory::serialize_model( $this );

		// Then update all meta fields that are inside the collection
		foreach ( $data as $field => $val ) {
			update_user_meta( $this->id, 'ms_' . $field, $val );
		}

		wp_cache_set( $this->id, $this, $class );
		return apply_filters( 'ms_model_member_save', $this );
	}

	/**
	 * Create new WP user.
	 *
	 * @since  1.0.0
	 * @internal
	 * @throws Exception
	 */
	private function create_new_user() {
		// Check if the WordPress settings allow user registration.
		if ( ! MS_Model_Member::can_register() ) {
			throw new Exception( __( 'Registration is currently not allowed.', MS_TEXT_DOMAIN ), 1 );
			return;
		}

		$validation_errors = new WP_Error();

		$required = array(
			'username' => __( 'Username', MS_TEXT_DOMAIN ),
			'email' => __( 'Email address', MS_TEXT_DOMAIN ),
			'password'   => __( 'Password', MS_TEXT_DOMAIN ),
			'password2'  => __( 'Password confirmation', MS_TEXT_DOMAIN ),
		);

		/**
		 * Filter the required field list to customize the fields that are
		 * mandatory.
		 *
		 * @since 1.0.1.0
		 * @var   array
		 */
		$required = apply_filters(
			'ms_model_member_create_user_required_fields',
			$required
		);

		foreach ( $required as $field => $message ) {
			if ( empty( $this->$field ) && empty( $_POST[$field] ) ) {
				$validation_errors->add(
					$field,
					sprintf(
						__( 'Please ensure that the <span class="ms-bold">%s</span> information is completed.', MS_TEXT_DOMAIN ),
						$message
					)
				);
			}
		}

		if ( $this->password != $this->password2 ) {
			$validation_errors->add(
				'passmatch',
				__( 'Please ensure the passwords match.', MS_TEXT_DOMAIN )
			);
		}

		if ( ! validate_username( $this->username ) ) {
			$validation_errors->add(
				'usernamenotvalid',
				__( 'The username is not valid, sorry.', MS_TEXT_DOMAIN )
			);
		}

		if ( username_exists( $this->username ) ) {
			$validation_errors->add(
				'usernameexists',
				__( 'That username is already taken, sorry.', MS_TEXT_DOMAIN )
			);
		}

		if ( ! is_email( $this->email ) ) {
			$validation_errors->add(
				'emailnotvalid',
				__( 'The email address is not valid, sorry.', MS_TEXT_DOMAIN )
			);
		}

		if ( email_exists( $this->email ) ) {
			$validation_errors->add(
				'emailexists',
				__( 'That email address is already taken, sorry.', MS_TEXT_DOMAIN )
			);
		}

		// Check the multisite Email-Domain limitation for new registrations.
		if ( is_multisite() ) {
			$illegal_names = get_site_option( 'illegal_names' );
			$limited_domains = get_site_option( 'limited_email_domains' );
			$banned_domains = get_site_option( 'banned_email_domains' );
			$email_domain = substr( strrchr( $this->email, '@' ), 1 );

			if ( $illegal_names && is_array( $illegal_names ) ) {
				if ( in_array( $this->username, $illegal_names ) ) {
					$validation_errors->add(
						'illegalname',
						__( 'The username is not valid, sorry.', MS_TEXT_DOMAIN )
					);
				}
			}

			if ( $limited_domains && is_array( $limited_domains ) ) {
				if ( ! in_array( $email_domain, $limited_domains ) ) {
					$validation_errors->add(
						'emaildomain',
						__( 'That email domain is not allowed for registration, sorry.', MS_TEXT_DOMAIN )
					);
				}
			}

			if ( $banned_domains && is_array( $banned_domains ) ) {
				if ( in_array( $email_domain, $banned_domains ) ) {
					$validation_errors->add(
						'emaildomain',
						__( 'That email domain is not allowed for registration, sorry.', MS_TEXT_DOMAIN )
					);
				}
			}
		}

		$validation_errors = apply_filters(
			'ms_model_membership_create_new_user_validation_errors',
			$validation_errors
		);

		// Compatibility with WangGuard
		$_POST['user_email'] = $this->email;

		$user_data = array(
			'user_name' => $this->username,
			'orig_username' => $this->username,
			'user_email' => $this->email,
			'errors' => $validation_errors,
		);

		$user_data = apply_filters(
			'wpmu_validate_user_signup',
			$user_data
		);

		if ( is_wp_error( $user_data ) ) {
			/*
			 * Some plugins incorrectly return a WP_Error object as result of
			 * the wpmu_validate_user_signup filter.
			 */
			$validation_errors = $user_data;
		} else {
			$validation_errors = $user_data['errors'];
		}

		$errors = $validation_errors->get_error_messages();

		if ( ! empty( $errors ) ) {
			throw new Exception( implode( '<br/>', $errors ) );
		} else {
			$user_id = wp_create_user( $this->username, $this->password, $this->email );

			if ( is_wp_error( $user_id ) ) {
				$validation_errors->add(
					'userid',
					$user_id->get_error_message()
				);

				throw new Exception(
					implode(
						'<br/>',
						$validation_errors->get_error_messages()
					)
				);
			}
			$this->id = $user_id;
		}

		do_action( 'ms_model_member_create_new_user', $this );
	}

	/**
	 * Marks the current user as "confirmed"
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function confirm() {
		global $wpdb;

		$sql = "UPDATE $wpdb->users SET user_status = 0 WHERE ID = %d";
		$sql = $wpdb->prepare( $sql, $this->id );
		$wpdb->query( $sql );
	}

	/**
	 * Sign on user.
	 *
	 * @since  1.0.0
	 * @api
	 */
	public function signon_user() {
		$user = new WP_User( $this->id );

		if ( ! headers_sent() ) {
			$user = @wp_signon(
				array(
					'user_login'    => $this->username,
					'user_password' => $this->password,
					'remember'      => true,
				)
			);

			// Stop here in case the login failed.
			if ( is_wp_error( $user ) ) {
				return $user;
			}
		}

		// Also used in class-ms-controller-dialog.php (Ajax login)
		wp_set_current_user( $this->id );
		wp_set_auth_cookie( $this->id );
		do_action( 'wp_login', $this->username, $user );
		do_action( 'ms_model_member_signon_user', $user, $this );
	}

	/**
	 * Either creates or updates the value of a custom data field.
	 *
	 * Note: Remember to prefix the $key with a unique string to prevent
	 * conflicts with other plugins that also use this function.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 * @param  mixed $value The new value to assign to the field.
	 */
	public function set_custom_data( $key, $value ) {
		$this->custom_data[ $key ] = $value;
	}

	/**
	 * Removes a custom data field from this object.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 */
	public function delete_custom_data( $key ) {
		unset( $this->custom_data[ $key ] );
	}

	/**
	 * Returns the value of a custom data field.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 * @return mixed The value that was previously assigned to the custom field
	 *         or false if no value was set for the field.
	 */
	public function get_custom_data( $key ) {
		$res = false;
		if ( isset( $this->custom_data[ $key ] ) ) {
			$res = $this->custom_data[ $key ];
		}
		return $res;
	}

	/**
	 * Returns a list of all membership IDs of the current user.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return array
	 */
	public function get_membership_ids() {
		$result = array();

		foreach ( $this->subscriptions as $subscription ) {
			$result[] = $subscription->membership_id;
		}

		return $result;
	}

	/**
	 * Add a new membership.
	 *
	 * If multiple membership is disabled, may move existing membership.
	 *
	 * Only add a membership if a user is not already a member.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int $membership_id The membership id to add to.
	 * @param string $gateway_id Optional. The gateway used to add the membership.
	 * @param int|string $move_from_id Optional. The membership id(s) to cancel.
	 *
	 * @return object|null $subscription
	 */
	public function add_membership( $membership_id, $gateway_id = 'admin', $move_from_id = 0 ) {
		$subscription = null;

		if ( MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			if ( ! $this->get_subscription( $membership_id ) ) {
				$subscription = MS_Model_Relationship::create_ms_relationship(
					$membership_id,
					$this->id,
					$gateway_id,
					$move_from_id
				);

				if ( 'admin' != $gateway_id ) {
					$subscription->get_current_invoice();
				}

				if ( MS_Model_Relationship::STATUS_PENDING !== $subscription->status ) {
					$this->subscriptions[] = $subscription;

					usort(
						$this->subscriptions,
						array( 'MS_Model_Relationship', 'sort_by_priority' )
					);
				}
			} else {
				$subscription = $this->get_subscriptions( $membership_id );
			}

			// Reset the status and start/expire dates when added by admin.
			if ( 'admin' == $gateway_id ) {
				$subscription->start_date = null; // Will calculate correct date.
				$subscription->trial_expire_date = null;
				$subscription->expire_date = null;
				$subscription->status = MS_Model_Relationship::STATUS_ACTIVE;
				$subscription->save();
			}
		}

		return apply_filters(
			'ms_model_member_add_membership',
			$subscription,
			$membership_id,
			$gateway_id,
			$move_from_id,
			$this
		);
	}

	/**
	 * Drop a membership.
	 *
	 * Only update the status to deactivated.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int $membership_id The membership id to drop.
	 */
	public function drop_membership( $membership_id ) {
		$subscription = $this->get_subscription( $membership_id, $key );
		if ( $subscription ) {
			do_action(
				'ms_model_membership_drop_membership',
				$subscription,
				$this
			);

			$subscription->deactivate_membership();
			unset( $this->subscriptions[$key] );
		}

		do_action(
			'ms_model_membership_drop_membership',
			$membership_id,
			$this
		);
	}

	/**
	 * Cancel a membership.
	 *
	 * The membership remains valid until expiration date.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int $membership_id The membership id to drop.
	 */
	public function cancel_membership( $membership_id ) {
		$subscription = $this->get_subscription( $membership_id );
		if ( $subscription ) {
			do_action(
				'ms_model_membership_cancel_membership',
				$subscription,
				$this
			);

			$subscription->cancel_membership();
		} else {
			// The membership might be on status "PENDING" which is not included
			// in $this->subscriptions.
			$subscription = MS_Model_Relationship::get_subscription(
				$this->id,
				$membership_id
			);

			if ( $subscription->user_id == $this->id ) {
				$subscription->cancel_membership();
			}
		}

		do_action(
			'ms_model_membership_cancel_membership',
			$membership_id,
			$this
		);
	}

	/**
	 * Move a membership.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int $old_membership_id The membership id to move from.
	 * @param int $mew_membership_id The membership id to move to.
	 */
	public function move_membership( $old_membership_id, $mew_membership_id ) {
		$old_subscription = $this->get_subscription( $old_membership_id );
		if ( $old_subscription ) {
			$new_subscription = MS_Model_Relationship::create_ms_relationship(
				$mew_membership_id,
				$this->id,
				$old_subscription->gateway_id,
				$old_membership_id
			);

			$this->cancel_membership( $old_membership_id );
			$this->subscriptions[] = $new_subscription;

			MS_Model_Event::save_event(
				MS_Model_Event::TYPE_MS_MOVED,
				$new_subscription
			);
		}

		do_action(
			'ms_model_membership_move_membership',
			$old_membership_id,
			$mew_membership_id,
			$this
		);
	}

	/**
	 * Check membership relationship status.
	 *
	 * Canceled status is allowed until it expires.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param int $membership_id Optional. The specific membership to verify.
	 *        If empty, verify against all memberships.
	 * @return bool True if has a valid membership.
	 */
	public function has_membership( $membership_id = 0 ) {
		$has_membership = false;

		// Allowed membership status to have access
		$allowed_status = apply_filters(
			'ms_model_member_allowed_status',
			array(
				MS_Model_Relationship::STATUS_ACTIVE,
				MS_Model_Relationship::STATUS_TRIAL,
				MS_Model_Relationship::STATUS_CANCELED,
			)
		);

		if ( self::is_normal_admin( $this->id ) ) {
			$has_membership = true;
		}

		if ( ! empty( $membership_id ) ) {
			$subscription = $this->get_subscription( $membership_id );
			// Membership-ID specified: Check if user has this membership
			if ( $subscription
				&& in_array( $subscription->get_status(), $allowed_status )
			) {
				$has_membership = true;
			}
		} elseif ( ! empty ( $this->subscriptions ) ) {
			// No membership-ID: Check if user has *any* membership
			foreach ( $this->subscriptions as $subscription ) {
				if ( $subscription->is_system() ) { continue; }
				if ( in_array( $subscription->get_status(), $allowed_status ) ) {
					$has_membership = true;
					break;
				}
			}
		}

		return apply_filters(
			'ms_model_member_has_membership',
			$has_membership,
			$membership_id,
			$this
		);
	}

	/**
	 * Return the subscription object for the specified membership.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  int|string $membership_id The specific membership to return.
	 *         Value 'priority' will return the subcription with lowest priority.
	 * @return MS_Model_Relationship The subscription object.
	 */
	public function get_subscription( $membership_id, &$key = -1 ) {
		$subscription = null;
		$key = -1;

		if ( 'priority' == $membership_id ) {
			// Find subscription with the lowest priority.
			$cur_priority = -1;
			foreach ( $this->subscriptions as $ind => $item ) {
				$membership = $item->get_membership();
				if ( ! $membership->active ) { continue; }
				if ( $cur_priority < 0 || $membership->priority < $cur_priority ) {
					$subscription = $item;
					$cur_priority = $membership->priority;
					$key = $ind;
				}
			}
		} elseif ( ! empty( $membership_id ) ) {
			// Membership-ID specified: Check if user has this membership
			foreach ( $this->subscriptions as $ind => $item ) {
				if ( $item->membership_id == $membership_id ) {
					$subscription = $item;
					$key = $ind;
					break;
				}
			}
		}

		return apply_filters(
			'ms_model_member_get_subscription',
			$subscription,
			$membership_id,
			$this
		);
	}

	/**
	 * Returns a list of memberships for all active subscriptions of the member.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	protected function get_active_memberships() {
		$active_memberships = array();

		$active_status = array(
			MS_Model_Relationship::STATUS_ACTIVE,
			MS_Model_Relationship::STATUS_TRIAL,
			MS_Model_Relationship::STATUS_CANCELED,
		);

		foreach ( $this->subscriptions as $sub ) {
			if ( $sub->is_base() ) { continue; }
			if ( ! in_array( $sub->status, $active_status ) ) { continue; }

			$membership = $sub->get_membership();
			$active_memberships[$membership->id] = $membership;
		}

		return $active_memberships;
	}

	/**
	 * Checks if the current user is allowed to subscribe to the specified
	 * membership.
	 *
	 * @since  1.0.1.0
	 * @api
	 * @param  int $membership_id A membership_id.
	 * @return bool Whether subscription is allowed or not.
	 */
	public function can_subscribe_to( $membership_id ) {
		static $Access_Flags = null;

		if ( null === $Access_Flags ) {
			$Access_Flags = array();
			$active_memberships = $this->get_active_memberships();
			$all_memberships = MS_Model_Membership::get_memberships();

			/**
			 * Controls how to handle conflicts in upgrade path settings when a
			 * member has multiple memberships.
			 *
			 * Default is true:
			 *     If one membership forbids the upgrade, then that's it.
			 *
			 * Custom set to false:
			 *     If one membership allows the upgrade, then allow it.
			 *
			 * @since 1.0.1.0
			 * @var   bool
			 */
			$prefer_forbidden = apply_filters(
				'ms_model_member_can_subscribe_to_prefer_forbidden',
				true
			);

			foreach ( $active_memberships as $membership ) {
				$base_id = $membership->id;
				if ( $membership->is_guest() || $membership->is_user() ) {
					$base_id = 'guest';
				}

				foreach ( $all_memberships as $ms ) {
					if ( isset( $active_memberships[$ms->id] ) ) { continue; }

					$is_allowed = $ms->update_allowed( $base_id );

					if ( ! isset( $Access_Flags[$ms->id] ) ) {
						$Access_Flags[$ms->id] = $is_allowed;
					} else {
						if ( $prefer_forbidden && ! $is_allowed ) {
							$Access_Flags[$ms->id] = $is_allowed;
						} elseif ( ! $prefer_forbidden && $is_allowed ) {
							$Access_Flags[$ms->id] = $is_allowed;
						}
					}
				}
			}
		}

		$result = true;
		if ( isset( $Access_Flags[$membership_id] ) ) {
			$result = $Access_Flags[$membership_id];
		}

		return apply_filters(
			'ms_model_member_can_subscribe_to',
			$result,
			$membership_id
		);
	}

	/**
	 * Returns an array of existing subscriptions that should be cancelled when
	 * the user signs up to the specified membership.
	 *
	 * @since  1.0.1.0
	 * @param  int $membership_id A membership ID.
	 * @return array Might be an empty array or a list of membership IDs.
	 */
	public function cancel_ids_on_subscription( $membership_id ) {
		$result = array();

		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		$active_memberships = $this->get_active_memberships();

		foreach ( $active_memberships as $ms ) {
			if ( $membership->update_replaces( $ms->id ) ) {
				$result[] = $ms->id;
			}
		}

		return $result;
	}

	/**
	 * Delete member usermeta.
	 *
	 * Delete all plugin related usermeta.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function delete_all_membership_usermeta() {
		$this->subscriptions = array();
		$this->gateway_profiles = array();
		$this->is_member = false;

		do_action(
			'ms_model_membership_delete_all_membership_usermeta',
			$this
		);
	}

	/**
	 * Returns the WP_User object that is linked to the current member
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return WP_User
	 */
	public function get_user() {
		return $this->wp_user;
	}

	/**
	 * Returns a value from the user-meta table.
	 *
	 * @since  1.0.1.0
	 * @api
	 * @param  string $key The meta-key.
	 * @return mixed The meta-value.
	 */
	public function get_meta( $key ) {
		return get_user_meta( $this->id, $key, true );
	}

	/**
	 * Updates a value in the user-meta table.
	 *
	 * @since 1.0.1.0
	 * @api
	 * @param string $key The meta-key.
	 * @param mixed $value The new meta-value.
	 */
	public function set_meta( $key, $value ) {
		update_user_meta( $this->id, $key, $value );
	}

	/**
	 * Verify if current object is valid.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return boolean True if is valid.
	 */
	public function is_valid() {
		$valid = ( $this->id > 0 );

		return apply_filters( 'ms_model_member_is_valid', $valid, $this );
	}

	/**
	 * Get gateway profile.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param string $gateway The gateway ID.
	 * @param string $field Optional. The field to retrive. Default to null,
	 *     returning all profile info.
	 *
	 * @return mixed The gateway profile info.
	 */
	public function get_gateway_profile( $gateway, $field = null ) {
		$profile = null;

		if ( ! isset( $this->gateway_profiles[ $gateway ] ) ) {
			$this->gateway_profiles[ $gateway ] = array();
		}

		if ( empty( $field ) ) {
			$profile = $this->gateway_profiles[ $gateway ];
		} else {
			if ( ! isset( $this->gateway_profiles[ $gateway ][ $field ] ) ) {
				$this->gateway_profiles[ $gateway ][ $field ] = '';
			}
			$profile = $this->gateway_profiles[ $gateway ][ $field ];
		}

		return apply_filters( 'ms_model_member_get_gateway_profile', $profile );
	}

	/**
	 * Set gateway profile.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param string $gateway The gateway ID.
	 * @param string $field The field name to save.
	 * @param mixed $value The field value to save.
	 */
	public function set_gateway_profile( $gateway, $field, $value ) {
		$this->gateway_profiles[ $gateway ][ $field ] = $value;

		do_action(
			'ms_model_member_set_gateway_profile',
			$gateway,
			$field,
			$value,
			$this
		);
	}

	/**
	 * Validate member info.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @return boolean True if validated.
	 * @throws Exception if not validated.
	 */
	public function validate_member_info() {
		$validation_errors = new WP_Error();

		if ( ! is_email( $this->email ) ) {
			$validation_errors->add(
				'emailnotvalid',
				__( 'The email address is not valid, sorry.', MS_TEXT_DOMAIN )
			);
		}

		if ( $this->password != $this->password2 ) {
			$validation_errors->add(
				'passmatch',
				__( 'Please ensure the passwords match.', MS_TEXT_DOMAIN )
			);
		}

		$errors = apply_filters(
			'ms_model_member_validate_member_info_errors',
			$validation_errors->get_error_messages()
		);

		if ( ! empty( $errors ) ) {
			throw new Exception( implode( '<br/>', $errors ) );
		} else {
			return true;
		}
	}

	/**
	 * Get specific property.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $name The name of a property to associate.
	 * @return mixed The value of a property.
	 */
	public function __get( $property ) {
		$value = null;

		if ( property_exists( $this, $property ) ) {
			$value = $this->$property;
		} else {
			switch ( $property ) {
				case 'full_name':
					if ( ! empty( $this->first_name ) || ! empty( $this->last_name ) ) {
						$value = trim( $this->first_name . ' ' . $this->last_name );
					} elseif ( ! empty( $this->name ) ) {
						$value = trim( $this->name );
					} else {
						$value = trim( $this->username );
					}
					break;
			}
		}

		return $value;
	}

	/**
	 * Set specific property.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'email':
					if ( is_email( $value ) ) {
						$this->$property = $value;
					}
					break;

				case 'username':
					$this->$property = sanitize_user( $value );
					break;

				case 'name':
				case 'first_name':
				case 'last_name':
					$this->$property = sanitize_text_field( $value );
					break;

				default:
					$this->$property = $value;
					break;
			}
		}
	}
}