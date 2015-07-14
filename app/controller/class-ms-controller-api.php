<?php
/**
 * Exposes the public API.
 *
 * The simplest way to use the API is via an WordPress action `ms_init` that
 * runs some code as soon as the API becomes available:
 *
 *     // Run some code as early as possible.
 *     add_action( 'ms_init', 'my_api_hook' );
 *     function my_api_hook( $api ) {
 *         $memberships = $api->list_memberships();
 *     }
 *
 * **Recommended implementation structure**
 *
 *     class My_Membership2_Implementation {
 *         protected $api = null;
 *
 *         // Function is always executed. Will create 1 Implementation object.
 *         static public function setup() {
 *             static $Inst = null;
 *             if ( null === $Inst ) {
 *                 $Inst = new My_Membership2_Implementation();
 *             }
 *         }
 *
 *         // Function set up the api hook.
 *         private function __construct() {
 *             add_action( 'ms_init', array( $this, 'init' ) );
 *         }
 *
 *         // Function is only run when Membership2 is present + active.
 *         public function init( $api ) {
 *             $this->api = $api;
 *             // The main init code should come here now!
 *         }
 *
 *         // Add other event handlers and helper functions.
 *         // You can use $this->api in other functions to access the API object.
 *     }
 *     My_Membership2_Implementation::setup();
 *
 * ----------------
 *
 * We also add the WordPress filter `ms_active` to check if the plugin is
 * enabled and loaded. As long as this filter returns `false` the API cannot
 * be used:
 *
 *     // Check if the API object is available.
 *     if ( apply_filters( 'ms_active', false ) ) { ... }
 *
 * To directly access the API object use the property `MS_Plugin::$api`.
 * Note: Before `ms_active` is called `MS_Plugin::$api` will return false.
 * This should only be used in rare cases when you know that the API is
 * available; it's better to use the action `ms_init` for API access.
 *
 *     // Same as above.
 *     if ( MS_Plugin::$api ) { ... }
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Api extends MS_Controller {

	/**
	 * A reference to the Membership2 settings object.
	 *
	 * @since  1.0.0
	 * @api
	 * @var MS_Model_Settings
	 */
	public $settings = null;

	/**
	 * Stores a list of custom payment gateways.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @var array
	 */
	protected $gateways = array();

	/**
	 * Construct Settings manager.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function __construct() {
		parent::__construct();

		$this->settings = MS_Plugin::instance()->settings;

		/**
		 * Simple check to allow other plugins to quickly find out if
		 * Membership2 is loaded and the API was initialized.
		 *
		 * Example:
		 *   if ( apply_filters( 'ms_active', false ) ) { ... }
		 */
		add_filter( 'ms_active', '__return_true' );

		/**
		 * Make the API controller accessible via MS_Plugin::$api
		 */
		MS_Plugin::set_api( $this );

		/**
		 * Notify other plugins that Membership2 is ready.
		 */
		do_action( 'ms_init', $this );
	}

	/**
	 * Returns either the current member or the member with the specified id.
	 *
	 * If the specified user does not exist then false is returned.
	 *
	 *     // Useful functions of the Member object:
	 *     $member->has_membership( $membership_id )
	 *     $member->get_subscription( $membership_id )
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  int $user_id User_id
	 * @return MS_Model_Member|false The Member model.
	 */
	public function get_member( $user_id ) {
		$user_id = absint( $user_id );
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );

		if ( ! $member->is_valid() ) {
			$member = false;
		}

		return $member;
	}

	/**
	 * Returns the Member object of the current user.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return MS_Model_Member The Member model.
	 */
	public function get_current_member() {
		$member = MS_Model_Member::get_current_member();

		return $member;
	}

	/**
	 * Returns a single membership object.
	 *
	 * Other plugins can store and accuess custom settings for each membership:
	 *
	 *     // Create a custom value in the membership
	 *     $membership->set_custom_data( 'the_key', 'the_value' );
	 *     $membership->save(); // Custom data is now saved to database.
	 *
	 *     // Access and delete the custom value
	 *     $value = $membership->get_custom_data( 'the_key' );
	 *     $membership->delete_custom_data( 'the_key' );
	 *     $membership->save(); // Custom data is now deleted from database.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  int $membership_id A specific membership ID.
	 * @return MS_Model_Membership The membership object.
	 */
	public function get_membership( $membership_id ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		return $membership;
	}

	/**
	 * Returns a single subscription object of the specified user.
	 *
	 * If the user did not subscribe to the given membership then false is
	 * returned.
	 *
	 * Each subscription also offers custom data fields
	 * (see the details in get_membership() for details)
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  int $user_id The user ID.
	 * @param  int $membership_id A specific membership ID.
	 * @return MS_Model_Relationship|false The subscription object.
	 */
	public function get_subscription( $user_id, $membership_id ) {
		$subscription = false;

		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		if ( $member && $member->has_membership( $membership_id ) ) {
			$subscription = $member->get_subscription( $membership_id );
		}

		return $subscription;
	}

	/**
	 * Returns a list of all available Memberships.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  bool $list_all If set to true then also private and internal
	 *         Memberships (e.g. Guest Membership) are included.
	 *         Default is false which returns only memberships that a guest user
	 *         can subscribe to.
	 * @return MS_Model_Membership[] List of all available Memberships.
	 */
	public function list_memberships( $list_all = false ) {
		$args = array(
			'include_base' => false,
			'include_guest' => true,
		);
		$list = MS_Model_Membership::get_memberships( $args );

		if ( ! $list_all ) {
			foreach ( $list as $key => $item ) {
				if ( ! $item->active ) { unset( $list[$key] ); }
				elseif ( $item->private ) { unset( $list[$key] ); }
			}
		}

		return $list;
	}

	/**
	 * Create your own payment gateway and hook it up with Memberhip 2 by using
	 * this function!
	 *
	 * Creating your own payment gateway requires good php skills. To get you
	 * started follow these steps:
	 *
	 * 1. Copy the folder "/app/gateway/manual" to the "wp-contents/plugins"
	 *    folder, and name it "membership-mygateway"
	 *
	 * 2. Rename all files inside the "membership-mygateway" and replace the
	 *    term "manual" with "mygateway"
	 *
	 * 3. Edit all files, rename the class names inside the files to
	 *    "_Mygateway" (replacing "_Manual")
	 *
	 * 4. In class MS_Gateway_Mygateway make following changes:
	 *   - Set the value of const ID to "mygateway"
	 *
	 *   - Change the assigned name in function "after_load"
	 *
	 *   - Add a plugin header to the file, e.g.
	 *     /*
	 *      * Plugin name: Membership 2 Mygateway
	 *      * /
	 *
	 *   - Add the following line to the bottom of the file:
	 *     add_action( 'ms_init', 'mygateway_register' );
	 *     function mygateway_register( $api ) {
	 *         $api->register_payment_gateway(
	 *             MS_Gateway_Mygateway::ID,
	 *             'MS_Gateway_Mygateway'
	 *         )
	 *     }
	 *
	 * Now you have created a new plugin that registers a custom payment gateway
	 * for Membership 2! Implementing the payment logic is up to you - you can
	 * get a lot of insight by reviewing the existing payment gateways.
	 *
	 * @since 1.0.1.0
	 * @api
	 *
	 * @param string $id The ID of the new gateway.
	 * @param string $class The Class-name of the new gateway.
	 */
	public function register_payment_gateway( $id, $class ) {
		$this->gateways[$id] = $class;
		$this->add_action( 'ms_model_gateway_register', '_register_gateways' );
	}

	/**
	 * Internal filter callback function that registers custom payment gateways.
	 *
	 * @since  1.0.1.0
	 * @internal
	 *
	 * @param  array $gateways List of payment gateways.
	 * @return array New list of payment gateways.
	 */
	public function _register_gateways( $gateways ) {
		foreach ( $this->gateways as $id => $class ) {
			$gateways[$id] = $class;
		}

		return $gateways;
	}

	/**
	 * Membership2 has a nice integrated debugging feature. This feature can be
	 * helpful for other developers so this API offers a simple way to access
	 * the debugging feature.
	 *
	 * Also note that all membership objects come with the built-in debug
	 * function `$obj->dump()` to quickly analyze the object.
	 *
	 *     // Example of $obj->dump() usage
	 *     $user = MS_Plugin::$api->current_member();
	 *     $user->dump();
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  mixed $data The value to dump to the output stream.
	 */
	public function debug( $data ) {
		lib2()->debug->enable();
		lib2()->debug->dump( $data );
	}

}
