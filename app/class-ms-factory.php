<?php
/**
 * Factory class for all Models.
 *
 * @since  1.0.0
 *
 * @package Membership2
 */
class MS_Factory {

	/**
	 * Holds a list of all singleton objects
	 *
	 * @since  1.0.0
	 *
	 * @var   array
	 */
	static private $Singleton = array();

	/**
	 * Used to cache the original blog-ID when using network-wide protection
	 *
	 * @since  1.0.0
	 *
	 * @var   array
	 */
	static private $Prev_Blog_Id = array();

	/**
	 * This is only used for Unit-Testing to reset all cached singleton
	 * instances before running a new test.
	 *
	 * @since  1.0.0
	 */
	static public function _reset() {
		self::$Singleton = array();
		wp_cache_flush();
	}

	/**
	 * Create an MS Object.
	 *
	 * @since  1.0.0
	 *
	 * @param string $class The class to create object from.
	 * @return object The created object.
	 */
	public static function create( $class, $init_arg = null ) {
		$singletons = array(
			'MS_Model_Pages',
			'MS_Model_Settings',
			'MS_Model_Addon',
			'MS_Model_Rule',
			'MS_Model_Simulate',
		);

		$class = trim( $class );

		if ( in_array( $class, $singletons ) ) {
			_doing_it_wrong(
				'MS_Factory::create()',
				'This class is a singleton and should be fetched via MS_Factory::load() -> ' . $class,
				'1.0.4.5'
			);
		}

		if ( class_exists( $class ) ) {
			if ( null === $init_arg ) {
				$obj = new $class();
			} else {
				$obj = new $class( $init_arg );
			}
		} else {
			throw new Exception( 'Class ' . $class . ' does not exist.' );
		}

		/*
		 * Assign a new unique-ID to the object right after loading it.
		 *
		 * Purpose:
		 * This helps us to spot duplicates of the same object.
		 * We can also identify objects that were not created by MS_Factory.
		 */
		$obj->_factory_id = uniqid( 'object-' );

		self::prepare_obj( $obj );

		return apply_filters(
			'ms_factory_create_'. $class,
			$obj
		);
	}

	/**
	 * Load a MS Object.
	 *
	 * @since  1.0.0
	 *
	 * @param string $class The class to load object from.
	 * @param int $model_id Retrieve model object using ID.
	 * @return object The retrieved model.
	 */
	public static function load( $class, $model_id = 0, $context = null ) {
		$model = null;
		$class = trim( $class );
		$model_id = intval( $model_id );

		$key = strtolower( $class . '-' . $model_id );
		if ( null !== $context ) {
			$key .= '-' . $context;
		}

		if ( class_exists( $class ) && ! isset( self::$Singleton[$key] ) ) {
			/*
			 * We create a new object here so we can test via instanceof if
			 * the object has a certain parent class.
			 *
			 * The created object might be replaced by the load_from_...
			 * function.
			 *
			 * Tipp: The __constructor() functions of these objects should not
			 * exist or contain very lightweight code, never attach any
			 * filters/hooks, etc. as the object can be dumped a few lines later.
			 */
			$model = new $class( $model_id );
			$model->before_load();

			if ( $model instanceof MS_Model_Option ) {
				$model = self::load_from_wp_option( $model );
			} elseif ( $model instanceof MS_Model_CustomPostType ) {
				$model = self::load_from_wp_custom_post_type( $model, $model_id );
			} elseif ( $model instanceof MS_Model_Member ) {
				$model = self::load_from_wp_user( $model, $model_id );
			} elseif ( $model instanceof MS_Model_Transient ) {
				$model = self::load_from_wp_transient( $model, $model_id );
			}

			/*
			 * Assign a new unique-ID to the object right after loading it.
			 *
			 * Purpose:
			 * This helps us to spot duplicates of the same object.
			 * We can also identify objects that were not created by MS_Factory.
			 */
			$model->_factory_id = uniqid( $key . '-' );

			$model->after_load();

			// Store the new object in our singleton collection.
			self::set_singleton( $model, $key, $model_id );

			self::prepare_obj( self::$Singleton[$key] );
		}

		if ( ! isset( self::$Singleton[$key] ) ) {
			self::$Singleton[$key] = null;
		}

		return self::$Singleton[$key];
	}

	/**
	 * Allows us to manually set/replace a cached singleton object.
	 * This function was introduced to store the simulation subscription as
	 * a singleton with subscription ID -1
	 *
	 * @since  1.0.0
	 * @param string $key
	 * @param any $obj
	 */
	static public function set_singleton( $obj, $key = null, $model_id = null ) {
		$class = get_class( $obj );

		if ( null === $model_id ) {
			$model_id = intval( $obj->id );
		}

		if ( null === $key ) {
			$key = strtolower( $class . '-' . $model_id );
		}

		// This flag is used by MS_Model::store_singleton()
		if ( property_exists( $obj, '_in_cache' ) ) {
			$obj->_in_cache = true;
		}

		$obj = apply_filters(
			'ms_factory_set-' . strtolower( $class ),
			$obj,
			$model_id
		);

		$obj = apply_filters(
			'ms_factory_set',
			$obj,
			$class,
			$model_id
		);

		self::$Singleton[ $key ] = $obj;
	}

	/**
	 * Clears the factory cache.
	 *
	 * @since  1.0.0
	 */
	static public function clear() {
		wp_cache_flush();
	}

	/**
	 * Initialize the object after it was created or loaded.
	 *
	 * @since  1.0.0
	 * @param  MS_Hook &$obj Any Membership2 object to initialize.
	 */
	static private function prepare_obj( &$obj ) {
		static $Init_Obj = array();
		static $Init_Class = array();

		// This case only happens during plugin-updates but needs to be handled.
		if ( is_a( $obj, '__PHP_Incomplete_Class' ) ) {
			return false;
		}

		// Prepare each single object that was created.
		if ( method_exists( $obj, 'prepare_obj' ) ) {
			if ( ! isset( $Init_Obj[$obj->_factory_id] ) ) {
				$Init_Obj[$obj->_factory_id] = true;
				$obj->prepare_obj();
			}
		}

		// Prepare the first object of each class-type (i.e. "prepare-once").
		if ( method_exists( $obj, 'prepare_class' ) ) {
			$class = get_class( $obj );

			if ( ! isset( $Init_Class[$class] ) ) {
				$Init_Class[$class] = true;
				$obj->prepare_class();
			}
		}
	}

	/**
	 * Load MS_Model_Option object.
	 *
	 * MS_Model_Option objects are singletons.
	 * To support network-wide protection we use our convenience function
	 * self::get_option().
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_option $model The empty model instance.
	 * @return MS_Model_Option The retrieved object.
	 */
	protected static function load_from_wp_option( $model ) {
		$class = get_class( $model );

		$option_key = $model->option_key();
		$cache = wp_cache_get( $option_key, 'MS_Model_Option' );

		if ( $cache ) {
			$model = $cache;
		} else {
			$settings = self::get_option( $option_key );
			self::populate_model( $model, $settings );
		}

		return apply_filters(
			'ms_factory_load_from_wp_option',
			$model,
			$class
		);
	}

	/**
	 * Load MS_Model_Transient object.
	 *
	 * MS_Transient objects are singletons.
	 * To support network-wide protection we use our convenience function
	 * self::get_transient().
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Transient $model The empty model instance.
	 * @return MS_Model_Transient The retrieved object.
	 */
	public static function load_from_wp_transient( $model ) {
		$option_key = $model->option_key();
		$cache = wp_cache_get( $option_key, 'MS_Model_Transient' );

		if ( $cache ) {
			$model = $cache;
		} else {
			$settings = self::get_transient( $option_key );
			self::populate_model( $model, $settings );
		}

		return apply_filters(
			'ms_factory_load_from_wp_transient',
			$model,
			$option_key
		);
	}

	/**
	 * Load MS_Model_CustomPostType Objects.
	 *
	 * Load from post and postmeta.
	 * For network-wide protection we get the data from first blog
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_CustomPostType $model The empty model instance.
	 * @param int $model_id The model id to retrieve.
	 *
	 * @return MS_Model_CustomPostType The retrieved object.
	 */
	protected static function load_from_wp_custom_post_type( $model, $model_id = 0 ) {
		$class = get_class( $model );

		if ( ! empty( $model_id ) ) {
			$cache = wp_cache_get( $model_id, $class );

			if ( $cache ) {
				$model = $cache;
			} else {
				self::select_blog();
				$post = get_post( $model_id );

				if ( ! empty( $post ) && $model->get_post_type() === $post->post_type ) {
					$post_meta = get_post_meta( $model_id );
					$post_meta['id'] = array( $post->ID );
					$post_meta['description'] = array( $post->post_content );
					$post_meta['user_id'] = array( $post->post_author );
					self::populate_model( $model, $post_meta, true );

					/**
					 * Allow child classes of the CustomPostType model to load
					 * custom values from the posts/postmeta table
					 *
					 * @since  1.0.1.0
					 */
					$model->load_meta_data( $post_meta );
					$model->load_post_data( $post );
				} else {
					$model->id = 0;
				}
				self::revert_blog();
			}
		}

		return apply_filters(
			'ms_factory_load_from_custom_post_type',
			$model,
			$class,
			$model_id
		);
	}

	/**
	 * Load MS_Model_Member Object.
	 *
	 * Load from user and user meta.
	 * This data is always network-wide.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Member $model The empty member instance.
	 * @param int $user_id The user/member ID.
	 *
	 * @return MS_Model_Member The retrieved object.
	 */
	protected static function load_from_wp_user( $model, $user_id, $name = null ) {
		$class = get_class( $model );
		$cache = wp_cache_get( $user_id, $class );

		if ( $cache ) {
			$model = $cache;
		} else {
			$wp_user = new WP_User( $user_id, $name );

			if ( ! empty( $wp_user->ID ) ) {
				$member_details = get_user_meta( $user_id );

				$model->id = $wp_user->ID;
				$model->username = $wp_user->user_login;
				$model->email = $wp_user->user_email;
				$model->name = $wp_user->display_name;
				$model->first_name = $wp_user->first_name;
				$model->last_name = $wp_user->last_name;
				$model->wp_user = $wp_user;

				if ( ! $model->name ) {
					if ( $model->first_name ) {
						$model->name = $model->first_name . ' ' . $model->last_name;
					} else {
						$model->name = $wp_user->user_login;
					}
					$model->name = ucwords( strtolower( $model->name ) );
				}
				$model->name = trim( $model->name );

				/**
				 * Manually customize the display name of the user via a filter.
				 *
				 * @since  1.0.1.2
				 * @param  string $name The default display name used by M2.
				 * @param  WP_User $wp_user The user object used to populate the name.
				 */
				$model->name = apply_filters(
					'ms_model_user_set_name',
					$model->name,
					$wp_user
				);

				// Remove automatic populated values from metadata, if present.
				unset( $member_details['ms_username'] );
				unset( $member_details['ms_email'] );
				unset( $member_details['ms_name'] );
				unset( $member_details['ms_first_name'] );
				unset( $member_details['ms_last_name'] );

				self::populate_model( $model, $member_details, 'ms_' );

				// Load membership_relationships
				$model->subscriptions = MS_Model_Relationship::get_subscriptions(
					array( 'user_id' => $model->id )
				);
			}
		}

		return apply_filters(
			'ms_factory_load_from_wp_user',
			$model,
			$class,
			$user_id
		);
	}

	//
	// =========================================================================
	//   Public helper functions
	// =========================================================================
	//

	/**
	 * Populate fields of the model
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model $model
	 * @param  array $settings
	 * @param  bool $postmeta
	 */
	static public function populate_model( &$model, $settings, $postmeta = false ) {
		$fields = $model->get_object_vars();
		$class = get_class( $model );
		$vars = get_class_vars( $class );
		$saved_data = array();

		$ignore = isset( $vars['ignore_fields'] ) ? $vars['ignore_fields'] : array();
		$ignore[] = 'instance'; // Don't deserialize the double-serialized model!
		$ignore[] = 'actions';
		$ignore[] = 'filters';
		$ignore[] = 'ignore_fields';

		foreach ( $fields as $field => $val ) {
			if ( '_' === $field[0] || in_array( $field, $ignore ) ) {
				continue;
			}

			$value = null;

			if ( false === $postmeta ) {
				if ( isset( $settings[ $field ] ) ) {
					$value = $settings[ $field ];
				} elseif ( isset( $settings[ '_' . $field ] ) ) {
					$value = $settings[ '_' . $field ];
				}
			} elseif ( true === $postmeta ) {
				if ( isset( $settings[ $field ][0] ) ) {
					$value = $settings[ $field ][ 0 ];
				} elseif ( isset( $settings[ '_' . $field ][0] ) ) {
					$value = $settings[ '_' . $field ][ 0 ];
				}
			} elseif ( is_string( $postmeta ) ) {
				if ( isset( $settings[ $postmeta . $field ][0] ) ) {
					$value = $settings[ $postmeta . $field ][ 0 ];
				} elseif ( isset( $settings[ '_' . $postmeta . $field ][0] ) ) {
					$value = $settings[ '_' . $postmeta . $field ][ 0 ];
				}
			}

			if ( $value ) {
				$value = maybe_unserialize( $value );
			}

			$saved_data[ $field ] = $value;
			if ( null !== $value ) {
				$model->set_field( $field, $value );
			}
		}

		$model->_saved_data = $saved_data;

		/**
		 * Filter the serialized data collection before it is returned.
		 *
		 * Typically it is written to database right after this function call,
		 * so this hook allows us to modify data before it's written to the DB.
		 *
		 * @var object $model The completely populated object.
		 * @var string $class Class name of the object.
		 * @var array $settings The source data (serialized array).
		 * @var bool|string $postmeta The post-meta flag defines how the
		 *      $settings array is formatted.
		 */
		$model = apply_filters(
			'ms_factory_populate',
			$model,
			$class,
			$settings,
			$postmeta
		);

		$model = apply_filters(
			'ms_factory_populate-' . strtolower( $class ),
			$model,
			$settings,
			$postmeta
		);
	}

	/**
	 * Converts an MS_Model into an array
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model $model
	 * @return array
	 */
	static public function serialize_model( &$model ) {
		$data = array();
		$ignore = array();
		$class = get_class( $model );

		if ( is_object( $model ) ) {
			if ( method_exists( $model, '__sleep' ) ) {
				$fields = array_flip( $model->__sleep() );
			} else {
				$fields = $model->get_object_vars();
			}

			$vars = get_class_vars( get_class( $model ) );

			$ignore = isset( $vars['ignore_fields'] ) ? $vars['ignore_fields'] : array();
			$ignore[] = 'instance'; // Don't double-serialize the model!
			$ignore[] = 'actions';
			$ignore[] = 'filters';
			$ignore[] = 'ignore_fields';
		} else {
			// Value does not need to be serialized.
			return $model;
		}

		foreach ( $fields as $field => $dummy ) {
			if ( '_' === $field[0] || in_array( $field, $ignore ) ) {
				continue;
			}

			$data[ $field ] = $model->$field;
		}

		/**
		 * Filter the serialized data collection before it is returned.
		 *
		 * Typically it is written to database right after this function call,
		 * so this hook allows us to modify data before it's written to the DB.
		 *
		 * @var array $data Serialized data array.
		 * @var string $class Class name of the source object.
		 * @var object $model The source object (unserialized)
		 */
		$data = apply_filters(
			'ms_factory_serialize',
			$data,
			$class,
			$model
		);

		$data = apply_filters(
			'ms_factory_serialize-' . strtolower( $class ),
			$data,
			$model
		);

		ksort( $data );
		return $data;
	}

	//
	// =========================================================================
	//   Wrappers and convenience functions
	// =========================================================================
	//

	/**
	 * Wrapper to get an option value (regards network-wide protection mode)
	 *
	 * @since  1.0.0
	 * @param  string $key Option Key
	 * @return mixed Option value
	 */
	static public function get_option( $key ) {
		if ( MS_Plugin::is_network_wide() ) {
			$settings = get_site_option( $key );
		} else {
			$settings = get_option( $key );
		}

		return $settings;
	}

	/**
	 * Wrapper to delete an option value (regards network-wide protection mode)
	 *
	 * @since  1.0.0
	 * @param  string $key Option Key
	 */
	static public function delete_option( $key ) {
		if ( MS_Plugin::is_network_wide() ) {
			delete_site_option( $key );
		} else {
			delete_option( $key );
		}
	}

	/**
	 * Wrapper to update an option value (regards network-wide protection mode)
	 *
	 * @since  1.0.0
	 * @param  string $key Option Key
	 * @param  mixed $value New option value
	 */
	static public function update_option( $key, $value ) {
		if ( MS_Plugin::is_network_wide() ) {
			update_site_option( $key, $value );
		} else {
			update_option( $key, $value );
		}
	}

	/**
	 * Wrapper to get an transient value (regards network-wide protection mode)
	 *
	 * @since  1.0.0
	 * @param  string $key Transient Key
	 * @return mixed Transient value
	 */
	static public function get_transient( $key ) {
		if ( MS_Plugin::is_network_wide() ) {
			$transient = get_site_transient( $key );
		} else {
			$transient = get_transient( $key );
		}

		return $transient;
	}

	/**
	 * Wrapper to delete an transient value (regards network-wide protection mode)
	 *
	 * @since  1.0.0
	 * @param  string $key Transient Key
	 */
	static public function delete_transient( $key ) {
		if ( MS_Plugin::is_network_wide() ) {
			delete_site_transient( $key );
		} else {
			delete_transient( $key );
		}
	}

	/**
	 * Wrapper to update an transient value (regards network-wide protection mode)
	 *
	 * @since  1.0.0
	 * @param  string $key Transient Key
	 * @param  mixed $value New transient value
	 */
	static public function set_transient( $key, $value, $expiration ) {
		if ( MS_Plugin::is_network_wide() ) {
			set_site_transient( $key, $value, $expiration );
		} else {
			set_transient( $key, $value, $expiration );
		}
	}

	/**
	 * When network wide protection is enabled this will temporarily switch
	 * to the main blog to access or change data.
	 *
	 * Use revert_blog() when done!!
	 * This function is a performance-wise much better alternative to the
	 * built-in function switch_to_blog() because it does not run all the
	 * initialization logic (update user-roles, etc) when switching a blog.
	 *
	 * @since  1.0.0
	 */
	static public function select_blog( $site_id = null ) {
		global $wpdb;

		if ( MS_Plugin::is_network_wide() ) {
			if ( null === $site_id ) {
				if ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
					$site_id = BLOG_ID_CURRENT_SITE;
				} else {
					$site_id = 1;
				}
			}
			self::$Prev_Blog_Id[] = $GLOBALS['blog_id'];

			if ( $GLOBALS['blog_id'] != $site_id ) {
				$GLOBALS['blog_id'] = $site_id;
				$wpdb->set_blog_id( $GLOBALS['blog_id'] );
				$GLOBALS['table_prefix'] = $wpdb->get_blog_prefix();
			}
		}
	}

	/**
	 * Reverts back to the original blog during network wide protection.
	 *
	 * @since  1.0.0
	 */
	static public function revert_blog() {
		global $wpdb;

		if ( MS_Plugin::is_network_wide() ) {
			$site_id = array_pop( self::$Prev_Blog_Id );

			if ( $site_id != $GLOBALS['blog_id'] ) {
				$GLOBALS['blog_id'] = $site_id;
				$wpdb->set_blog_id( $GLOBALS['blog_id'] );
				$GLOBALS['table_prefix'] = $wpdb->get_blog_prefix();
			}
		}
	}

	/**
	 * Returns the blog-id that was loaded by the user. This will return the
	 * original blog-id, even when switched to a different blog by calling
	 * self::select_blog()
	 *
	 * @since  1.0.0
	 * @return int The requested blog-ID.
	 */
	static public function current_blog_id() {
		$blog_id = get_current_blog_id();

		if ( count( self::$Prev_Blog_Id ) > 0 ) {
			$blog_id = self::$Prev_Blog_Id[0];
		}

		return $blog_id;
	}

}