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
	 * Create an MS Object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class The class to create object from.
	 *
	 * @return object The created object.
	 */
	public static function create( $class ) {
		$class = trim( $class );

		if ( class_exists( $class ) ) {
			$obj = new $class();
		}
		else {
			throw new Exception( 'Class ' . $class . ' does not exist.' );
		}

		return apply_filters( 'ms_factory_create_'. $class, $obj );
	}

	/**
	 * Load a MS Object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class The class to load object from.
	 * @param int $model_id Retrieve model object using ID.
	 *
	 * @return object The retrieved model.
	 */
	public static function load( $class, $model_id = 0 ) {
		$model = null;

		if ( class_exists( $class ) ) {
			$model = new $class();

			if ( $model instanceof MS_Model_Option ) {
				$model = self::load_from_wp_option( $model );
			}
			elseif ( $model instanceof MS_Model_Custom_Post_Type ) {
				$model = self::load_from_wp_custom_post_type( $model, $model_id );
			}
			elseif ( $model instanceof MS_Model_Member ) {
				$args = func_get_args();

				$name = null;
				if ( ! empty( $args[2] ) ) {
					$name = $args[2];
				}
				$model = self::load_from_wp_user( $model, $model_id, $name );
			}
			elseif ( $model instanceof MS_Model_Transient ) {
				$model = self::load_from_wp_transient( $model, $model_id );
			}
		}

		return apply_filters(
			'ms_factory_load_' . $class,
			$model,
			$model_id
		);
	}

	/**
	 * Load MS_Model_Option object.
	 *
	 * MS_Model_Option objects are singletons.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_option $model The empty model instance.
	 *
	 * @return MS_Model_Option The retrieved object.
	 */
	protected static function load_from_wp_option( $model ) {
		$class = get_class( $model );

		if ( empty( $model->instance ) ) {
			$model->before_load();
			$cache = wp_cache_get( $class, 'MS_Model_Option' );

			if ( $cache ) {
				$model = $cache;
			}
			else {
				$settings = get_option( $class );

				$fields = $model->get_object_vars();
				foreach ( $fields as $field => $val ) {
					if ( in_array( $field, $model->ignore_fields ) ) {
						continue;
					}
					if ( isset( $settings[ $field ] ) ) {
						$model->set_field( $field, $settings[ $field ] );
					}
				}
			}

			$model->after_load();
			$model->instance = $model;
		}
		else {
			$model = $model->instance;
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
	 *
	 * @return MS_Model_Transient The retrieved object.
	 */
	public static function load_from_wp_transient( $model ) {
		$class = get_class( $model );

		if ( empty( $model->instance ) ) {
			$model->before_load();
			$cache = wp_cache_get( $class, 'MS_Model_Transient' );

			if ( $cache ) {
				$model = $cache;
			}
			else {
				$settings = get_transient( $class );
				$fields = $model->get_object_vars();

				foreach ( $fields as $field => $val ) {
					if ( in_array( $field, $model->ignore_fields ) ) {
						continue;
					}

					if ( isset( $settings[ $field ] ) ) {
						$model->set_field( $field, $settings[ $field ] );
					}
				}

				$model->after_load();
				$model->instance = $model;
			}
		}
		else {
			$model = $model->instance;
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
		$model->before_load();
		$class = get_class( $model );

		if ( ! empty( $model_id ) ) {
			$cache = wp_cache_get( $model_id, $class );

			if ( $cache ) {
				$model = $cache;
			}
			else {
				$post = get_post( $model_id );

				if ( ! empty( $post ) && $model->post_type == $post->post_type ) {
					$post_meta = get_post_meta( $model_id );
					$fields = $model->get_object_vars();

					foreach ( $fields as $field => $val ) {
						if ( in_array( $field, $model->ignore_fields ) ) {
							continue;
						}

						if ( isset( $post_meta[ $field ][ 0 ] ) ) {
							$model->set_field(
								$field,
								maybe_unserialize( $post_meta[ $field ][ 0 ] )
							);
						}
					}

					$model->id = $post->ID;
					$model->description = $post->post_content;
					$model->user_id = $post->post_author;
				}
			}
		}

		$model->after_load();

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
		}
		else {
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

				$model->is_admin = $model->is_admin_user( $user_id );

				$fields = $model->get_object_vars();

				foreach ( $fields as $field => $val ) {
					if ( in_array( $field, $model->ignore_fields ) ) {
						continue;
					}

					if ( isset( $member_details[ 'ms_' . $field ][0] ) ) {
						$model->set_field(
							$field,
							maybe_unserialize( $member_details[ 'ms_' . $field ][0] )
						);
					}
				}

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
}