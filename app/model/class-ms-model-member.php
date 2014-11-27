<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Member model.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Member extends MS_Model {

	/**
	 * Members search constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SEARCH_ONLY_MEMBERS = 'only members';
	const SEARCH_NOT_MEMBERS = 'not_members';
	const SEARCH_ALL_USERS = 'all_users';

	/**
	 * Member's Membership Relationships.
	 *
	 * @since 1.0.0
	 *
	 * @var array {
	 *     @type int $membership_id The membership ID.
	 *     @type MS_Model_Membership_Relationship The membership relationship model object.
	 * }
	 */
	protected $ms_relationships = array();

	/**
	 * Admin member indicator.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $is_admin = false;

	/**
	 * Is member indicator.
	 *
	 * Only members have access to memberships.
	 * False indicates blocked members (if signed up for a membership).
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $is_member = false;

	/**
	 * Active status.
	 *
	 * Staus to activate or deactivate a user independently of the membership
	 * status. False indicates blocked members (if signed up for a membership).
	 * For further use. (For temporary member blocking).
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * Member's username.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $user_login.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Member's email.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $user_email.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Member's name.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $user_nicename.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Member's first name.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $first_name
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $first_name;

	/**
	 * Member's last name.
	 *
	 * Mapped from wordpress $wp_user object.
	 * @see WP_User $last_name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $last_name;

	/**
	 * Member's password.
	 *
	 * Used when registering.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Member's password confirmation.
	 *
	 * Used when registering.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $password2;

	/**
	 * Member's gateway profiles info.
	 *
	 * Save gateway IDs.
	 *
	 * @since 1.0.0
	 *
	 * @var array {
	 *     Return structure: $gateway[ $field ] => $value;
	 *
	 *     @type string $gateway_id The gateway id.
	 *     @type string $field The field to store.
	 *     @type mixed $value The field value to store.
	 * }
	 */
	protected $gateway_profiles;

	/**
	 * Don't persist this fields.
	 *
	 * @since 1.0.0
	 *
	 * @var string[] The fields to ignore when persisting.
	 */
	public $ignore_fields = array(
		'ms_relationships',
		'id',
		'name',
		'username',
		'email',
		'name',
		'first_name',
		'last_name',
		'password',
		'password2',
		'actions',
		'filters',
		'ignore_fields',
	);

	/**
	 * Get current member.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Member The current member.
	 */
	public static function get_current_member() {
		return MS_Factory::load( 'MS_Model_Member', get_current_user_id() );
	}

	/**
	 * Save member.
	 *
	 * Create a new user is id is empty.
	 * Save member fields to wp_user and wp_usermeta tables.
	 * Set cache for further use in MS_Factory::load.
	 * The usermeta are prefixed with 'ms_'.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Member The saved member object.
	 */
	public function save() {
		if ( empty( $this->id ) ) {
			$this->create_new_user();
		}

		$user_details = get_user_meta( $this->id );
		$fields = get_object_vars( $this );

		foreach ( $fields as $field => $val ) {
			if ( in_array( $field, $this->ignore_fields ) ) {
				continue;
			}

			if ( isset( $this->$field )
				&& ( ! isset( $user_details[ "ms_$field" ][0] )
					|| $user_details[ "ms_$field" ][0] != $this->$field
				)
			) {
				update_user_meta( $this->id, "ms_$field", $this->$field );
			}
		}

		if ( isset( $this->username ) ) {
			$wp_user = new stdClass();
			$wp_user->ID = $this->id;
			$wp_user->nickname = $this->username;
			$wp_user->user_nicename = $this->username;
			$wp_user->first_name = $this->first_name;
			$wp_user->last_name = $this->last_name;
			$wp_user->display_name = $this->username;

			if ( ! empty( $this->password )
				&& $this->password == $this->password2
			) {
				$wp_user->user_pass = $this->password;
			}
			wp_update_user( get_object_vars( $wp_user ) );
		}

		$class = get_class( $this );
		wp_cache_set( $this->id, $this, $class );

		return apply_filters( 'ms_model_member_save', $this );
	}

	/**
	 * Create new WP user.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 */
	private function create_new_user() {
		$validation_errors = new WP_Error();

		$required = array(
			'username' => __( 'Username', MS_TEXT_DOMAIN ),
			'email' => __( 'Email address', MS_TEXT_DOMAIN ),
			'password'   => __( 'Password', MS_TEXT_DOMAIN ),
			'password2'  => __( 'Password confirmation', MS_TEXT_DOMAIN ),
		);

		foreach ( $required as $field => $message ) {
			if ( empty( $this->$field ) ) {
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

		$validation_errors = apply_filters(
			'ms_model_membership_create_new_user_validation_errors',
			$validation_errors
		);

		$result = apply_filters(
			'wpmu_validate_user_signup',
			array(
				'user_name' => $this->username,
				'orig_username' => $this->username,
				'user_email' => $this->email,
				'errors' => $validation_errors,
			)
		);

		$validation_errors = $result['errors'];
		$errors = $validation_errors->get_error_messages();

		if ( ! empty( $errors ) ) {
			throw new Exception( implode( '<br/>', $errors ) );
		}
		else {
			$user_id = wp_create_user( $this->username, $this->password, $this->email );

			if ( is_wp_error( $user_id ) ) {
				$validation_errors->add( 'userid', $user_id->get_error_message() );

				throw new Exception(
					implode( '<br/>', $validation_errors->get_error_messages() )
				);
			}
			$this->id = $user_id;
		}

		do_action( 'ms_model_member_create_new_user', $this );
	}

	/**
	 * Sign on user.
	 *
	 * @since 1.0.0
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
	 * Get members total count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query user args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @return int The count.
	 */
	public static function get_members_count( $args = null ) {
		$args = self::get_query_args( $args, self::SEARCH_ONLY_MEMBERS );
		$wp_user_search = new WP_User_Query( $args );

		return apply_filters(
			'ms_model_member_get_members_count',
			$wp_user_search->get_total()
		);
	}

	/**
	 * Get members.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query user args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_User_Query
	 * @return MS_Model_Member[] The selected members.
	 */
	public static function get_members( $args = null ) {
		$members = array();

		$args = self::get_query_args( $args, self::SEARCH_ONLY_MEMBERS );
		$wp_user_search = new WP_User_Query( $args );
		$users = $wp_user_search->get_results();
		MS_Helper_Debug::log( $args );
		MS_Helper_Debug::log( $users );
		foreach ( $users as $user_id ) {
			$members[] = MS_Factory::load( 'MS_Model_Member', $user_id );
		}

		return apply_filters( 'ms_model_member_get_members', $members );
	}

	/**
	 * Get usernames.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
				'number' => 10,
				'offset' => 0,
				'fields' => 'ID',
			)
		);

		$args = WDev()->get_array( $args );
		WDev()->load_fields( $args, 'meta_query');
		$args['meta_query'] = WDev()->get_array( $args['meta_query'] );

		switch ( $search_option ) {
			case self::SEARCH_ONLY_MEMBERS:
				$args['meta_query']['is_member'] = array(
					'key'   => 'ms_is_member',
					'value' => true,
				);
				break;

			case self::SEARCH_NOT_MEMBERS:
				$args['meta_query']['relation'] = 'OR';
				$args['meta_query']['is_member'] = array(
					'key'     => 'ms_is_member',
					'compare' => 'NOT EXISTS',
				);
				$args['meta_query']['is_member1'] = array(
					'key'     => 'ms_is_member',
					'value'   => false,
				);
				break;

			case self::SEARCH_ALL_USERS:
			default:
				break;
		}

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_member_get_query_args',
			$args,
			$defaults
		);
	}

	/**
	 * Add a new membership.
	 *
	 * If multiple membership is disabled, may move existing membership.
	 *
	 * Only add a membership if a user is not already a member.
	 *
	 * @since 1.0.0
	 *
	 * @param int $membership_id The membership id to add to.
	 * @param string $gateway_id Optional. The gateway used to add the membership.
	 * @param int $move_from_id Optional. The membership id to move from if any.
	 *
	 * @return object|null $ms_relationship
	 */
	public function add_membership( $membership_id, $gateway_id = 'admin', $move_from_id = 0 ) {
		$ms_relationship = null;

		if ( MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			if ( ! array_key_exists( $membership_id,  $this->ms_relationships ) ) {
				$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship(
					$membership_id,
					$this->id,
					$gateway_id,
					$move_from_id
				);

				if ( 'admin' != $gateway_id ) {
					MS_Model_Invoice::get_current_invoice( $ms_relationship );
				}
				if ( MS_Model_Membership_Relationship::STATUS_PENDING != $ms_relationship->status ) {
					$this->ms_relationships[ $membership_id ] = $ms_relationship;
				}
			}
			else {
				$ms_relationship = $this->ms_relationships[ $membership_id ];
			}
		}

		return apply_filters(
			'ms_model_member_add_membership',
			$ms_relationship,
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
	 * @since 1.0.0
	 *
	 * @param int $membership_id The membership id to drop.
	 */
	public function drop_membership( $membership_id ) {
		if ( array_key_exists( $membership_id,  $this->ms_relationships ) ) {
			do_action(
				'ms_model_membership_drop_membership',
				$this->ms_relationships[ $membership_id ],
				$this
			);

			$this->ms_relationships[ $membership_id ]->deactivate_membership( false );
			unset( $this->ms_relationships[ $membership_id ] );
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
	 * @since 1.0.0
	 *
	 * @param int $membership_id The membership id to drop.
	 */
	public function cancel_membership( $membership_id ) {
		if ( array_key_exists( $membership_id,  $this->ms_relationships ) ) {
			do_action(
				'ms_model_membership_cancel_membership',
				$this->ms_relationships[ $membership_id ],
				$this
			);

			$this->ms_relationships[ $membership_id ]->cancel_membership( false );
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
	 * @since 1.0.0
	 *
	 * @param int $move_from_id The membership id to move from.
	 * @param int $move_to_id The membership id to move to.
	 */
	public function move_membership( $move_from_id, $move_to_id ) {
		if ( array_key_exists( $move_from_id,  $this->ms_relationships ) ) {
			$move_from = $this->ms_relationships[ $move_from_id ];
			$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship(
				$move_to_id,
				$this->id,
				$move_from->gateway_id,
				$move_from_id
			);

			$this->cancel_membership( $move_from_id );
			$this->ms_relationships[ $move_to_id ] = $ms_relationship;

			MS_Model_Event::save_event(
				MS_Model_Event::TYPE_MS_MOVED,
				$this->ms_relationships[ $move_to_id ]
			);
		}

		do_action( 'ms_model_membership_move_membership', $move_from_id, $move_to_id, $this );
	}

	/**
	 * Check membership relationship status.
	 *
	 * Canceled status is allowed until it expires.
	 *
	 * @since 1.0.0
	 *
	 * @param int $membership_id Optional. The specific membership to verify. If empty, verify against all memberships.
	 * @return bool True if has a valid membership.
	 */
	public function has_membership( $membership_id = 0 ) {
		$has_membership = false;

		// Allowed membership status to have access
		$allowed_status = apply_filters(
			'membership_model_member_allowed_status',
			array(
				MS_Model_Membership_Relationship::STATUS_ACTIVE,
				MS_Model_Membership_Relationship::STATUS_TRIAL,
				MS_Model_Membership_Relationship::STATUS_CANCELED,
			)
		);
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );

		if ( $this->is_admin && ! $simulate->is_simulating() ) {
			$has_membership = true;
		}

		if ( ! empty( $membership_id ) ) {
			if ( array_key_exists( $membership_id,  $this->ms_relationships )
				&& in_array( $this->ms_relationships[ $membership_id ]->get_status(), $allowed_status )
			) {
				$has_membership = true;
			}
		}
		elseif ( ! empty ( $this->ms_relationships ) ) {
			foreach ( $this->ms_relationships as $membership_relationship ) {
				if ( in_array( $membership_relationship->get_status(), $allowed_status ) ) {
					$has_membership = true;
				}
			}
		}

		return apply_filters(
			'membership_model_member_has_membership',
			$has_membership,
			$membership_id,
			$this
		);
	}

	/**
	 * Delete member usermeta.
	 *
	 * Delete all plugin related usermeta.
	 *
	 * @since 1.0.0
	 */
	public function delete_all_membership_usermeta() {
		$this->ms_relationships = array();
		$this->gateway_profiles = array();
		$this->is_member = false;

		do_action(
			'ms_model_membership_delete_all_membership_usermeta',
			$this
		);
	}

	/**
	 * Verify is user is logged in.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if user is logged in.
	 */
	public static function is_logged_user() {
		$logged = is_user_logged_in();

		return apply_filters( 'ms_model_member_is_logged_user', $logged );
	}

	/**
	 * Verify is user is Admin user.
	 *
	 * @since 1.0.0
	 *
	 * @todo modify this when implementing network/multisites handling.
	 *
	 * @param int|bool $user_id Optional. The user ID. Default to current user.
	 * @param string $capability The capability to check for admin users.
	 * @return boolean True if user is admin.
	 */
	public static function is_admin_user( $user_id = false, $capability = 'manage_options' ) {
		$is_admin = false;

		if ( is_super_admin( $user_id ) ) {
			$is_admin = true;
		}

		$capability = apply_filters(
			'ms_model_member_is_admin_user_capability',
			$capability
		);

		if ( ! empty( $capability ) ) {
			$wp_user = null;

			if ( empty( $user_id ) ) {
				$wp_user = wp_get_current_user();
			}
			else {
				$wp_user = new WP_User( $user_id );
			}

			$is_admin = $wp_user->has_cap( $capability );
		}

		return apply_filters(
			'ms_model_member_is_admin_user',
			$is_admin,
			$user_id
		);
	}

	/**
	 * Get Admin users emails.
	 *
	 * @since 1.0.0
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
	 * Get user's username.
	 *
	 * @since 1.0.0
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
	 * Verify if current object is valid.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
		}
		else {
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
			MS_Helper_Debug::log( 'no password match' );
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
		}
		else {
			return true;
		}
	}

	/**
	 * Set specific property.
	 *
	 * @since 1.0.0
	 *
	 * @access public
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

		do_action( 'ms_model_member__set_after', $property, $value, $this );
	}
}