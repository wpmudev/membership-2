<?php
/**
 * This file defines the MS_Factory object.
 *
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
 * Factory class for all Models.
 *
 * @since 1.0.0
 *
 * @package Membership
 */
class MS_Factory {

	/**
	 * Holds a list of all singleton objects
	 *
	 * @since  1.0.4.5
	 *
	 * @var array
	 */
	static protected $singleton = array();

	/**
	 * Create an MS Object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class The class to create object from.
	 * @return object The created object.
	 */
	public static function create( $class ) {
		$singletons = array(
			'MS_Model_Pages',
			'MS_Model_Settings',
			'MS_Model_Addon',
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
			$obj = new $class();
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

		/**
		 * Option to initialize objects.
		 *
		 * @since  1.1
		 */
		if ( method_exists( $obj, 'prepare_obj' ) ) {
			$obj->prepare_obj();
		}

		return apply_filters(
			'ms_factory_create_'. $class,
			$obj
		);
	}

	/**
	 * Load a MS Object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class The class to load object from.
	 * @param int $model_id Retrieve model object using ID.
	 * @return object The retrieved model.
	 */
	public static function load( $class, $model_id = 0, $context = null ) {
		$model = null;
		$class = trim( $class );
		$model_id = absint( $model_id );

		$key = strtolower( $class . '-' . $model_id );
		if ( null !== $context ) {
			$key .= '-' . $context;
		}

		if ( class_exists( $class ) && ! isset( self::$singleton[$key] ) ) {
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
			} elseif ( $model instanceof MS_Model_Custom_Post_Type ) {
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

			// Store the new object
			self::$singleton[$key] = apply_filters(
				'ms_factory_load_' . $class,
				$model,
				$model_id
			);

			/**
			 * New option to initialize objects as early as possible
			 *
			 * @since  1.1
			 */
			self::$singleton[$key]->prepare_obj( $key );
		}

		if ( ! isset( self::$singleton[$key] ) ) {
			self::$singleton[$key] = null;
		}

		return self::$singleton[$key];
	}

	/**
	 * Clears the factory cache.
	 *
	 * @since  1.0.4.5
	 */
	static public function clear() {
		wp_cache_flush();
	}

	/**
	 * Load MS_Model_Option object.
	 *
	 * MS_Model_Option objects are singletons.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_option $model The empty model instance.
	 * @return MS_Model_Option The retrieved object.
	 */
	protected static function load_from_wp_option( $model ) {
		$class = get_class( $model );

		$cache = wp_cache_get( $class, 'MS_Model_Option' );

		if ( $cache ) {
			$model = $cache;
		} else {
			$settings = get_option( $class );
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
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Transient $model The empty model instance.
	 * @return MS_Model_Transient The retrieved object.
	 */
	public static function load_from_wp_transient( $model ) {
		$class = get_class( $model );
		$cache = wp_cache_get( $class, 'MS_Model_Transient' );

		if ( $cache ) {
			$model = $cache;
		} else {
			$settings = get_transient( $class );
			self::populate_model( $model, $settings );
		}

		return apply_filters(
			'ms_factory_load_from_wp_transient',
			$model,
			$class
		);
	}

	/**
	 * Load MS_Model_Custom_Post_Type Objects.
	 *
	 * Load from post and postmeta.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Custom_Post_Type $model The empty model instance.
	 * @param int $model_id The model id to retrieve.
	 *
	 * @return MS_Model_Custom_Post_Type The retrieved object.
	 */
	protected static function load_from_wp_custom_post_type( $model, $model_id = 0 ) {
		$class = get_class( $model );

		if ( ! empty( $model_id ) ) {
			$cache = wp_cache_get( $model_id, $class );

			if ( $cache ) {
				$model = $cache;
			} else {
				$post = get_post( $model_id );

				if ( ! empty( $post ) && $model->post_type === $post->post_type ) {
					$post_meta = get_post_meta( $model_id );
					self::populate_model( $model, $post_meta, true );

					$model->id = $post->ID;
					$model->description = $post->post_content;
					$model->user_id = $post->post_author;
				} else {
					$model->id = 0;
				}

				if ( is_array( $model->_subobjects ) ) {
					foreach ( $model->_subobjects as $obj ) {
						if ( property_exists( $model, $obj ) ) {
							$items = $model->$obj;
							foreach ( $items as $m_key => $m_obj ) {
								$items[$m_key]->_prepared = false;
							}
							$model->$obj = $items;
						}
					}
				}
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
	 *
	 * @since 1.0.0
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
				$model->name = $wp_user->user_nicename;
				$model->first_name = $wp_user->first_name;
				$model->last_name = $wp_user->last_name;
				$model->wp_user = $wp_user;

				self::populate_model( $model, $member_details, 'ms_' );

				// Load membership_relationships
				$model->ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships(
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

	/**
	 * Populate fields of the model
	 *
	 * @since  1.0.4.5
	 *
	 * @param  MS_Model $model
	 * @param  array $settings
	 * @param  bool $postmeta
	 */
	static public function populate_model( &$model, $settings, $postmeta = false ) {
		$fields = $model->get_object_vars();

		$ignore = $model::$ignore_fields;
		$ignore[] = 'instance'; // Don't deserialize the double-serialized model!
		$ignore[] = 'actions';
		$ignore[] = 'filters';
		$ignore[] = 'ignore_fields';

		foreach ( $fields as $field => $val ) {
			if ( $field[0] === '_' || in_array( $field, $ignore ) ) {
				continue;
			}

			$value = null;

			if ( false === $postmeta ) {
				if ( isset( $settings[ $field ] ) ) {
					$value = $settings[ $field ];
				}
			} else if ( true === $postmeta ) {
				if ( isset( $settings[ $field ][0] ) ) {
					$value = maybe_unserialize( $settings[ $field ][ 0 ] );
				}
			} else if ( is_string( $postmeta ) ) {
				if ( isset( $settings[ $postmeta . $field ][0] ) ) {
					$value = maybe_unserialize( $settings[ $postmeta . $field ][ 0 ] );
				}
			}

			if ( null !== $value ) {
				$model->set_field( $field, $value );
			}
		}
	}

	/**
	 * Converts an MS_Model into an array
	 *
	 * @since  1.0.4.5
	 *
	 * @param  MS_Model $model
	 * @return array
	 */
	static public function serialize_model( &$model ) {
		$data = array();
		$ignore = array();

		if ( is_object( $model ) ) {
			if ( method_exists( $model, '__sleep' ) ) {
				$fields = array_flip( $model->__sleep() );
			} else {
				$fields = $model->get_object_vars();
			}

			if ( isset( $model::$ignore_fields ) && is_array( $model::$ignore_fields ) ) {
				$ignore = $model::$ignore_fields;
				$ignore[] = 'instance'; // Don't double-serialize the model!
				$ignore[] = 'actions';
				$ignore[] = 'filters';
				$ignore[] = 'ignore_fields';
			}
		} else {
			// Value does not need to be serialized.
			return $model;
		}

		foreach ( $fields as $field => $dummy ) {
			if ( $field[0] === '_' || in_array( $field, $ignore ) ) {
				continue;
			}

			$data[ $field ] = $model->$field;
		}

		ksort( $data );
		return $data;
	}
}