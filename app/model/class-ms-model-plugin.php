<?php
/**
 * M2 Model file.
 *
 * @package Membership2
 * @subpackage Model
 */

/**
 * Main class for protection.
 *
 * @since  1.0.0
 */
class MS_Model_Plugin extends MS_Model {

	/**
	 * Current Member object.
	 *
	 * @since  1.0.0
	 *
	 * @var string $member
	 */
	public $member;

	/**
	 * Full admin menu, used by the Adminside rule.
	 * This property cannot be initialized in the rule-model itself because the
	 * rule is loaded long after the menu is rendered and therefore does not
	 * have access to the full list of menu items.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $admin_menu = array();

	/**
	 * The number of members processed per batch
	 *
	 * @since 1.0.2.6
	 *
	 * @var $_process_per_batch
	 */
	private $_process_per_batch = 500;

	/**
	 * Prepare object.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		do_action( 'ms_model_plugin_constructor', $this );

                /**
                 * Define MS_PROCESS_PER_BATCH, set the number of members will be processed per batch
                 *
                 * Default value is 500
                 */
		if ( defined( 'MS_PROCESS_PER_BATCH' ) && MS_PROCESS_PER_BATCH ) {
			$this->_process_per_batch = intval( MS_PROCESS_PER_BATCH );
		}

		// Upgrade membership database if needs to.
		MS_Model_Upgrade::init();

		/*
		 * Create our own copy of the full admin menu to be used in the
		 * Membership2 settings.
		 *
		 * These hooks are only executed in the admin side.
		 */
		$this->add_action( '_network_admin_menu', 'store_admin_menu', 1 );
		$this->add_action( '_user_admin_menu', 'store_admin_menu', 1 );
		$this->add_action( '_admin_menu', 'store_admin_menu', 1 );

		$this->add_action( 'network_admin_menu', 'store_admin_menu', 99999 );
		$this->add_action( 'user_admin_menu', 'store_admin_menu', 99999 );
		$this->add_action( 'admin_menu', 'store_admin_menu', 99999 );

		// Register all Add-ons and load rules BEFORE the user is initialized.
		$this->add_action( 'ms_load_member', 'load_addons', 1 );
		$this->add_action( 'ms_load_member', 'load_rules', 1 );

		// Setup the page protection AFTER the user was initialized.
		$this->add_action( 'ms_init_done', 'setup_rules', 1 );
		$this->add_action( 'ms_init_done', 'setup_protection', 2 );
		$this->add_action( 'ms_init_done', 'setup_admin_protection', 3 );

		/*
		 * Some plugins (such as MarketPress) can trigger the set_current_user
		 * action hook before this object is initialized. To ensure correct
		 * loading order we use the `init` hook, which is called directly after
		 * the correct set_current_user call.
		 *
		 * Most of the plugin logic requires the current user to be known,
		 * that's why we do a explicit check here to make sure we have a valid
		 * user.
		 */

		// Initialize the current member
		$this->run_action( 'init', 'init_member', 11 ); // Load AFTER the default hook.

		/*
		 * ******************************************************************* *
		 *   Hooks below are only set up when Content Protection is enabled
		 * ******************************************************************* *
		 */

		if ( ! MS_Plugin::is_enabled() ) { return; }

		// Setup the CRON hooks.
		$this->run_action( 'init', 'setup_cron_services', 1 );
		$this->add_filter( 'cron_schedules', 'cron_time_period' );
		$this->add_filter( 'ms_run_cron_services', 'run_cron_services' );
		$this->add_action( 'ms_cron_check_membership_status', 'check_membership_status' );
		$this->add_action( 'ms_toggle_cron', 'setup_cron_services', 1 );

		$this->add_action( 'template_redirect', 'protect_current_page', 1 );

		// Init gateways and communications to register actions/filters.
		$this->run_action( 'init', array( 'MS_Model_Gateway', 'get_gateways' ), 2 );
		$this->run_action( 'init', array( 'MS_Model_Communication', 'init' ), 2 );

		// Old plugin is enabled? Show a warning!
		if ( class_exists( 'M_Membership' ) ) {
			lib3()->ui->admin_message(
				__( '<b>Warning</b>: The old version of the Membership plugin is active and causes conflicts with the new Membership 2 plugin. Please disable the old Membership plugin.', 'membership2' ),
				'red'
			);
		}

	}

	/**
	 * Initialise current member.
	 *
	 * Get current member and membership relationships.
	 * If user is not logged in (visitor), assign a visitor membership.
	 * If user is logged in but has not any memberships, assign a default membership.
	 * Deactivated users (active == false) get visitor membership assigned.
	 *
	 * @since  1.0.0
	 */
	public function init_member() {
		do_action( 'ms_load_member', $this );

		$this->member = MS_Model_Member::get_current_member();

		if ( MS_Plugin::is_enabled() ) {
			if ( ! is_user_logged_in() ) {
				// If a Guest-Membership exists we also assign it to the user.
				$ms_guest = MS_Model_Membership::get_guest();
				if ( $ms_guest->is_valid() && $ms_guest->active ) {
					$this->member->add_membership( $ms_guest->id );
				}
			} elseif ( ! $this->member->has_membership() ) {
				// Apply User-Membership to logged-in users without subscriptions.
				$ms_user = MS_Model_Membership::get_user();
				if ( $ms_user->is_valid() && $ms_user->active ) {
					$this->member->add_membership( $ms_user->id );
				}
			} elseif ( ! $this->member->is_member ) {
				$this->member->subscriptions = array();
			}

			// No subscription: Assign the base membership, which only denies access.
			if ( ! $this->member->has_membership() ) {
				$this->member->add_membership(
					MS_Model_Membership::get_base()->id
				);
			}
		}

		/**
		 * At this point the plugin is initialized and we are here:
		 *   - All Add-Ons are registered
		 *   - All Rules are registered
		 *   - Know the current User
		 *     - All Subscriptions/Memberships of the user are loaded
		 *     - System memberships are already assigned (guest/base)
		 *   - Payment gateways are registered
		 *   - Communication settings are loaded
		 *
		 * Next we tell everybody that we are ready to get serious!
		 *
		 * What happens next:
		 *   1. All Membership-Rules are initialized/merged
		 *   2. Front-End Protection is applied
		 *   3. Admin-Side Protection is applied
		 */

		do_action( 'ms_init_done', $this );
	}

	/**
	 * Returns an array with access-information on the current page/user
	 *
	 * @since  1.0.0
	 *
	 * @return array {
	 *     Access information
	 *
	 *     @type bool $has_access If the current user can view the current page.
	 *     @type array $memberships List of active membership-IDs the user has
	 *         registered to.
	 * }
	 */
	public function get_access_info() {
		static $Info = null;

		if ( null === $Info ) {
			$Info = array(
				'has_access' => null,
				'is_admin' => false,
				'memberships' => array(),
				'url' => MS_Helper_Utility::get_current_url(),
			);

			// The ID of the main system membership.
			$base_id = MS_Model_Membership::get_base()->id;

			$simulation = $this->member->is_simulated_user() || isset( $_GET['explain'] ) && 'access' == $_GET['explain'];
			if ( $simulation ) { $Info['reason'] = array(); }

			if ( $this->member->is_normal_admin() ) {
				// Admins have access to ALL memberships.
				$Info['is_admin'] = true;
				$Info['has_access'] = true;

				if ( $simulation ) {
					$Info['reason'][] = __( 'Allow: Admin-User always has access', 'membership2' );
				}

				$memberships = MS_Model_Membership::get_memberships();
				foreach ( $memberships as $membership ) {
					$Info['memberships'][] = $membership->id;
				}
			} else {
				/*
				 * A non-admin visitor is only guaranteed access to special
				 * Membership2 pages:
				 * Registration, Login, etc.
				 */
				$ms_page = MS_Model_Pages::current_page();
				if ( $ms_page ) {
					$Info['has_access'] = true;

					if ( $simulation ) {
						$Info['reason'][] = __( 'Allow: This is a Membership Page', 'membership2' );
					}
				}

				// Build a list of memberships the user belongs to and check permission.
				foreach ( $this->member->subscriptions as $subscription ) {
					// Verify status of the membership.
					// Only active, trial or canceled (until it expires) status memberships.
					if ( ! $this->member->has_membership( $subscription->membership_id ) ) {
						if ( $simulation ) {
							$Info['reason'][] = sprintf(
								__( 'Skipped: Not a member of "%s"', 'membership2' ),
								$subscription->get_membership()->name
							);
						}

						continue;
					}

					if ( $base_id !== $subscription->membership_id ) {
						$Info['memberships'][] = $subscription->membership_id;
					}

					// If permission is not clear yet then check current membership...
					if ( true !== $Info['has_access'] ) {
						$membership = $subscription->get_membership();
						$access = $membership->has_access_to_current_page();

						if ( null === $access ) {
							if ( $simulation ) {
								$Info['reason'][] = sprintf(
									__( 'Ignored: Membership "%s"', 'membership2' ),
									$membership->name
								);
								$Info['reason'][] = $membership->_access_reason;
							}
							continue;
						}

						if ( $simulation ) {
							$Info['reason'][] = sprintf(
								__( '%s: Membership "%s"', 'membership2' ),
								$access ? __( 'Allow', 'membership2' ) : __( 'Deny', 'membership2' ),
								$membership->name
							);

							$Info['deciding_membership'] = $membership->id;
							if ( $access ) {
								$Info['deciding_rule'] = $membership->_allow_rule;
							} else {
								$Info['deciding_rule'] = $membership->_deny_rule;
							}
							$Info['reason'][] = $membership->_access_reason;
						}

						$Info['has_access'] = $access;
					}
				}

				if ( null === $Info['has_access'] ) {
					$Info['has_access'] = true;

					if ( $simulation ) {
						$Info['reason'][] = __( 'Allow: Page is not protected', 'membership2' );
					}
				}

				// "membership-id: 0" means: User does not belong to any membership.
				if ( ! count( $Info['memberships'] ) ) {
					$Info['memberships'][] = 0;
				}
			}

			$Info = apply_filters( 'ms_model_plugin_get_access_info', $Info );

			if ( $simulation ) {
				$access = lib3()->session->get_clear( 'ms-access' );
				lib3()->session->add( 'ms-access', $Info );
				for ( $i = 0; $i < 9; $i += 1 ) {
					if ( isset( $access[ $i ] ) ) {
						lib3()->session->add( 'ms-access', $access[ $i ] );
					}
				}

				if ( WP_DEBUG && isset( $_GET['explain'] ) && 'access' == $_GET['explain'] ) {
					echo '<style>code{background:#EEE;background:rgba(0,0,0,0.1);padding:1px 4px;}</style>';
					echo '<h3>Note</h3>';
					echo '<p>To disable the URL param <code>?explain=access</code> you have to set <code>WP_DEBUG</code> to false.</p>';
					echo '<hr><h3>Recent Access checks</h3>';

					lib3()->debug->stacktrace_off();
					foreach ( $access as $item ) {
						if ( $item['has_access'] ) {
							$label = __( 'Allow', 'membership2' );
						} else {
							$label = __( 'Deny', 'membership2' );
						}
						printf(
							'<a href="%1$s">%1$s</a>: <strong>%2$s</strong>',
							esc_url( $item['url'] ),
							esc_attr( $label )
						);
						// Intended debug output, leave it here.
						lib3()->debug->dump( $item );
					}
					wp_die( '' );
				}
			}
		}

		return $Info;
	}

	/**
	 * Checks member permissions and protects current page.
	 *
	 * Related Action Hooks:
	 * - template_redirect
	 *
	 * @since  1.0.0
	 */
	public function protect_current_page() {
		do_action( 'ms_model_plugin_protect_current_page_before', $this );
                
		if( defined( 'MS_PROTECTED_MESSAGE_REVERSE_RULE' ) && MS_PROTECTED_MESSAGE_REVERSE_RULE ) {
			$allowed_memberships = array();
			$memberships = MS_Model_Membership::get_membership_ids();
			foreach( $memberships as $membership_id ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
				if( $membership->has_access_to_current_page() ) {
					$allowed_memberships[$membership->priority] = $membership_id;
				}
			}
			ksort( $allowed_memberships );
			$protected_membership_id = reset( $allowed_memberships );
		}

		// Admin user has access to everything.
		if ( $this->member->is_normal_admin() ) {
			return;
		}

		$access = $this->get_access_info();

		if ( ! $access['has_access'] ) {

                        if ( $auth = filter_input( INPUT_GET, 'auth' ) ) {
                                //set cookie when mapped domains
                                $user_id = wp_validate_auth_cookie( $auth, 'auth' );
                                if ( $user_id ) {
                                        wp_set_auth_cookie( $user_id );

                                        wp_redirect( get_permalink() );
                                        exit;
                                }
                        }

			MS_Model_Pages::create_missing_pages();
			$no_access_page_url = MS_Model_Pages::get_page_url(
				MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT,
				false
			);
			$current_page_url = MS_Helper_Utility::get_current_url();

			// Don't (re-)redirect the protection page.
			if ( ! MS_Model_Pages::is_membership_page( null, MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT ) ) {
                            if( defined( 'MS_PROTECTED_MESSAGE_REVERSE_RULE' ) && MS_PROTECTED_MESSAGE_REVERSE_RULE ) {
				$no_access_page_url = esc_url_raw(
					add_query_arg(
						array( 'redirect_to' => urlencode( $current_page_url ), 'membership_id' => $protected_membership_id ),
						$no_access_page_url
					)
				);
                            }else{
                                $no_access_page_url = esc_url_raw(
					add_query_arg(
						array( 'redirect_to' => urlencode( $current_page_url ) ),
						$no_access_page_url
					)
				);
                            }

                            $no_access_page_url = apply_filters(
                                    'ms_model_plugin_protected_content_page',
                                    $no_access_page_url
                            );
                            wp_safe_redirect( $no_access_page_url );

                            exit;
			}
		}

		do_action( 'ms_model_plugin_protect_current_page_after', $this );
	}

	/**
	 * Load all the Add-ons.
	 *
	 * Related Action Hooks:
	 * - ms_load_member
	 *
	 * @since  1.0.0
	 */
	public function load_addons() {
		do_action( 'ms_load_addons', $this );

		// Initialize all Add-ons.
		MS_Model_Addon::get_addons();
	}

	/**
	 * Load all the rules that are used by the plugin.
	 *
	 * Related Action Hooks:
	 * - ms_load_member
	 *
	 * @since  1.0.0
	 */
	public function load_rules() {
		do_action( 'ms_load_rules', $this );

		$rule_types = MS_Model_Rule::get_rule_types();
		$base = MS_Model_Membership::get_base();

		foreach ( $rule_types as $rule_type ) {
			$rule = $base->get_rule( $rule_type );
		}
	}

	/**
	 * Load all the rules that are used by the plugin.
	 *
	 * Related Action Hooks:
	 * - ms_init_done
	 *
	 * @since  1.0.0
	 * @throws Exception When function is called too early.
	 */
	public function setup_rules() {
		// Make sure we stick to the correct workflow.
		if ( ! did_action( 'ms_init_done' ) ) {
			throw new Exception( 'setup_rules() is called too early.', 1 );
			return;
		}

		do_action( 'ms_initialize_rules', $this );

		$rule_types = MS_Model_Rule::get_rule_types();

		foreach ( $this->member->subscriptions as $subscription ) {
			foreach ( $rule_types as $rule_type ) {
				$rule = $subscription->get_membership()->get_rule( $rule_type );
			}
		}
	}

	/**
	 * Setup initial protection for the front-end.
	 *
	 * Hide menu and pages, protect media donwload and feeds.
	 * Protect feeds.
	 *
	 * Related Action Hooks:
	 * - ms_init_done
	 *
	 * @since  1.0.0
	 * @throws Exception When function called too early.
	 */
	public function setup_protection() {
		if ( is_admin() ) { return; }

		// Make sure we stick to the correct workflow.
		if ( ! did_action( 'ms_init_done' ) ) {
			throw new Exception( 'setup_protection() is called too early.', 1 );
			return;
		}

		do_action( 'ms_setup_protection', $this );

		// If multi membership addon is active and member has at least one membership with allow access then no need to protect content
		$disable_protection = false;
		if ( ! $this->member->is_normal_admin() && MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {

			foreach ( $this->member->subscriptions as $subscription ) {
				// Verify status of the membership.
				// Only active, trial or canceled (until it expires) status memberships.
				if ( ! $this->member->has_membership( $subscription->membership_id ) ) {
					continue;
				}

				$membership = $subscription->get_membership();
				$membership->initialize( $subscription );
						
				$disable_protection = $disable_protection || $membership->has_access_to_content( MS_Rule_Content_Model::MORE_LIMIT );
			}

		}		

		// Search permissions through all memberships joined.
		foreach ( $this->member->subscriptions as $subscription ) {
			// Verify status of the membership.
			// Only active, trial or canceled (until it expires) status memberships.
			if ( ! $this->member->has_membership( $subscription->membership_id ) ) {
				continue;
			}

			$membership = $subscription->get_membership();
			$membership->initialize( $subscription );

			// Protection is not applied for Admin users.
			if ( ! $this->member->is_normal_admin() && ! $disable_protection ) {
				$membership->protect_content();
			}
		}

		do_action( 'ms_setup_protection_done', $this );
	}

	/**
	 * Setup initial protection for the admin-side.
	 *
	 * Related Action Hooks:
	 * - ms_init_done
	 *
	 * @since  1.0.0
	 * @throws Exception When function is called too early.
	 */
	public function setup_admin_protection() {
		if ( ! is_admin() ) { return; }

		// Make sure we stick to the correct workflow.
		if ( ! did_action( 'ms_init_done' ) ) {
			throw new Exception( 'setup_admin_protection() is called too early.', 1 );
			return;
		}

		do_action( 'ms_setup_admin_protection', $this );

		// Search permissions through all memberships joined.
		foreach ( $this->member->subscriptions as $subscription ) {
			// Verify status of the membership.
			// Only active, trial or canceled (until it expires) status memberships.
			if ( ! $this->member->has_membership( $subscription->membership_id ) ) {
				continue;
			}

			$membership = $subscription->get_membership();
			$membership->initialize( $subscription );

			// Protection is not applied for Admin users.
			if ( ! $this->member->is_normal_admin() ) {
				$membership->protect_admin_content();
			}
		}

		do_action( 'ms_setup_admin_protection_done', $this );
	}

	/**
	 * Config cron time period. This is actually not displayed anywhere but
	 * used only in function setup_cron_services()
	 *
	 * Related Action Hooks:
	 * - cron_schedules
	 *
	 * @since  1.0.0
	 * @param  array $periods Default Cron-Job period values; we add new ones.
	 * @return array Modified list of Cron-Job periods.
	 */
	public function cron_time_period( $periods ) {
		if ( ! is_array( $periods ) ) {
			$periods = array();
		}

		$periods['6hours'] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display' => __( 'Every 6 Hours', 'membership2' ),
		);
		$periods['30mins'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display' => __( 'Every 30 Mins', 'membership2' ),
		);

		return apply_filters(
			'ms_model_plugin_cron_time_period',
			$periods
		);
	}

	/**
	 * Runs a single Membership 2 cron service and then re-schedules it.
	 * This function is used to manually trigger the cron services.
	 *
	 * @since  1.0.0
	 * @param  string $hook Cron-Job to run on next page load.
	 */
	public function run_cron_services( $hook ) {
		wp_clear_scheduled_hook( $hook );
		$this->setup_cron_services( $hook );

		// Note that we only remove the cron job and add it again.
		// As a result the job is re-scheduled with current timestamp and
		// therefore it will be executed instantly.
	}

	/**
	 * Setup cron plugin services.
	 *
	 * Setup cron to call actions.
	 * The action-hook is called via the WordPress Cron implementation on a
	 * regular basis - this hooks are set up only once.
	 *
	 * The Cron jobs can be manually executed by opening the admin page
	 * "Membership2 > Settings" and adding URL param "&run_cron=1"
	 *
	 * @since  1.0.0
	 * @param  string $reschedule Optional. Hook to re-schedule.
	 */
	public function setup_cron_services( $reschedule = null ) {
		do_action( 'ms_model_plugin_setup_cron_services_before', $this );

		$jobs = array(
			'ms_cron_check_membership_status' => '6hours',
			'ms_cron_process_communications' => 'hourly',
		);
		
		$settings = MS_Factory::load( 'MS_Model_settings' );

		foreach ( $jobs as $hook => $interval ) {
			if ( $settings->enable_cron_use ) {
				if ( ! wp_next_scheduled( $hook ) || $hook == $reschedule ) {
					wp_schedule_event( time(), $interval, $hook );
				}
			} else {
				if ( wp_next_scheduled( $hook ) ) {
					wp_clear_scheduled_hook( $hook );
				}
			}
			
		}

		do_action( 'ms_model_plugin_setup_cron_services_after', $this );
	}

	/**
	 * Check membership status.
	 *
	 * Execute actions when time/period condition are met.
	 * E.g. change membership status, add communication to queue, create invoices.
	 *
	 * @since  1.0.0
	 */
	public function check_membership_status() {
		do_action( 'ms_model_plugin_check_membership_status_before', $this );

		if ( $this->member->is_simulated_user() ) {
			return;
		}

		/*
		 * For performance reasons we only process a small batch at once.
		 * Here we find out, which subscriptions should be processed during
		 * the current request.
		 */
		$offset = (int) MS_Factory::get_option( 'ms_batch_check_offset_flag' );

		// Find the next X subscriptions from DB.
		$args = apply_filters(
			'ms_model_plugin_check_membership_status_get_subscription_args',
			array(
				'status' => 'valid',
				'orderby' => 'ID',
				'posts_per_page' => $this->_process_per_batch,
				'offset' => $offset,
				'nopaging' => false,
			)
		);
		$subscriptions = MS_Model_Relationship::get_subscriptions( $args );

		if ( count( $subscriptions ) < $this->_process_per_batch ) {
			// We processed all subscriptions. Clean up.
			MS_Factory::delete_option( 'ms_batch_check_offset_flag' );
		} else {
			// We did not process all subscriptions. Remember where to continue.
			MS_Factory::update_option(
				'ms_batch_check_offset_flag',
				$offset + $this->_process_per_batch
			);

			// Re-scheduling the cron job will run it again on next page load.
			$hook = 'ms_cron_check_membership_status';
			wp_clear_scheduled_hook( $hook );
			$this->setup_cron_services( $hook );
		}
                
                $_SESSION['m2_status_check'] = 'inv';

		// Perform the actual status checks!
		foreach ( $subscriptions as $subscription ) {
			$subscription->check_membership_status();
		}
                
		do_action( 'ms_model_plugin_check_membership_status_after', $this );
	}

	/**
	 * Copies the full WordPress Admin menu before any restriction is applied
	 * by WordPress or an Plugin. This menu-information is used on the
	 * Membership2/Accessible Content settings pages
	 *
	 * @since  1.0.0
	 * @global array $menu
	 * @global array $submenu
	 */
	public function store_admin_menu() {
		global $menu, $submenu;

		if ( ! isset( $this->admin_menu['main'] ) ) {
			$this->admin_menu = array(
				'main' => $menu,
				'sub' => $submenu,
			);
		} else {
			foreach ( $menu as $pos => $item ) {
				$this->admin_menu['main'][ $pos ] = $item;
			}
			foreach ( $submenu as $parent => $item ) {
				$this->admin_menu['sub'][ $parent ] = $item;
			}
			ksort( $this->admin_menu['main'] );
		}
	}

	/**
	 * Returns the previously stored admin menu items.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_admin_menu() {
		return $this->admin_menu;
	}
}
