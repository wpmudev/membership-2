<?php
/**
 * This file defines the MS_Controller_Admin_Bar class.
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
 * Controller to add functionality to the admin toolbar.
 *
 * Used extensively for simulating memberships and content access.
 * Adds ability for Membership users to test the behaviour for their end-users.
 *
 * @since 1.0.0
 * 
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Admin_Bar extends MS_Controller {

	/**
	 * Details on current simulation mode
	 *
	 * @since 1.0.0
	 * 
	 * @var MS_Model_Simulate
	 */
	protected $simulate = null;

	/**
	 * List of all available memberships
	 * 
	 * @since 1.0.0
	 * 
	 * @var MS_Model_Membership[]
	 */
	protected $memberships = null;

	/**
	 * Prepare the Admin Bar simulator.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->simulate = MS_Factory::load( 'MS_Model_Simulate' );
		$this->memberships = MS_Model_Membership::get_memberships( array( 'include_visitor' => 1 ) );

		/* Hide WP toolbar in fron end to not admin users */
		if ( ! $this->is_admin_user() && MS_Plugin::instance()->settings->hide_admin_bar ) {
			add_filter( 'show_admin_bar', '__return_false' );
			$this->add_action( 'wp_before_admin_bar_render', 'customize_toolbar_front', 999 );
			$this->add_action( 'admin_head-profile.php', 'customize_admin_sidebar', 999 );
		}

		/* Customize WP toolbar for admin users */
		if ( $this->is_admin_user() ) {
			$this->add_action( 'wp_before_admin_bar_render', 'customize_toolbar', 999 );
			$this->add_action( 'add_admin_bar_menus', 'admin_bar_manager' );
			$this->add_action( 'admin_enqueue_scripts', 'enqueue_scripts' );
			$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
		}
	}

	/**
	 * Customize the Admin Toolbar.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_before_admin_bar_render
	 *
	 * @since 1.0.0
	 */
	public function customize_toolbar() {

		if ( MS_Model_Member::is_admin_user() && MS_Plugin::is_enabled() && ! is_network_admin() ) {
			if ( $this->simulate->is_simulating() ) {
				$this->remove_admin_bar_nodes();
				$this->add_view_site_as_node();
				$this->add_simulator_nodes();
				$this->add_exit_test_node();
			}
			else {
				$this->add_test_membership_node();
			}
		}
	}

	/**
	 * Process GET and POST requests
	 *
	 * **Hooks Actions: **
	 *
	 * * add_admin_bar_menus
	 *
	 * @since 1.0.0
	 */
	public function admin_bar_manager() {
		
		/* Check for memberhship id simulation GET request */
		if ( isset( $_GET['membership_id'] ) && $this->verify_nonce( 'ms_simulate-' . $_GET['membership_id'], 'GET' ) ) {
			$this->simulate->membership_id = $_GET['membership_id'];
			$this->simulate->save();
			wp_safe_redirect( wp_get_referer() );
		}
		
		/* Check for simulation periods/dates in POST request */
		$isset = array( 'simulate_submit', 'simulate_type' ) ;
		if ( $this->validate_required( $isset, 'POST', false ) ) {

			$this->simulate->type = $_POST['simulate_type'];
			
			if ( MS_Model_Simulate::TYPE_PERIOD == $this->simulate->type ) {
				$isset = array( 'period_unit', 'period_type' );
				if ( $this->validate_required( $isset, 'POST', false ) ) {
					$this->simulate->period = array( 'period_unit' => $_POST['period_unit'], 'period_type' => $_POST['period_type'] );
				}
				
			}
			elseif ( MS_Model_Simulate::TYPE_DATE == $this->simulate->type ) {
				if ( ! empty( $_POST['simulate_date'] ) ) {
					$this->simulate->date = $_POST['simulate_date'];
				}
			}
			
			$this->simulate->save();
			wp_safe_redirect( wp_get_referer() );
		}
	}

	/**
	 * Remove all Admin Bar nodes.
	 *
	 * @since 1.0.0
	 * 
	 * @param string[] $exclude The node IDs to exclude.
	 */
	private function remove_admin_bar_nodes( $exclude = array() ) {
		global $wp_admin_bar;

		$nodes = $wp_admin_bar->get_nodes();

		$exclude = apply_filters( 'ms_controller_admin_bar_remove_admin_bar_nodes_exclude', $exclude, $nodes );

		if ( is_array( $nodes ) ) {
			foreach ( $nodes as $node ) {
				if ( is_array( $exclude ) && ! in_array( $node->id, $exclude ) ) {
					$wp_admin_bar->remove_node( $node->id );
				}
			}
		}
		
		do_action( 'ms_controller_admin_bar_remove_admin_bar_nodes', $nodes, $exclude );
	}

	/**
	 * Add simulation nodes.
	 *
	 * @since 1.0.0
	 * 
	 */
	private function add_simulator_nodes() {
		global $wp_admin_bar;

		if ( $this->simulate->is_simulating() ) {

			$membership = MS_Factory::load( 'MS_Model_Membership', $this->simulate->membership_id );
			$title = null;
			$html = null;
			
			$data = array();
			$data['simulate_type'] = $this->simulate->type;
			$data['period_unit'] = null;
			$data['period_type'] = null;
			$data['simulate_date'] = null;

			if( $this->simulate->type ) {
				if ( MS_Model_Simulate::TYPE_DATE == $this->simulate->type ) {
					$data['simulate_date'] = $this->simulate->date;
					$title = __( 'View on: ', MS_TEXT_DOMAIN );
				}
				elseif ( MS_Model_Simulate::TYPE_PERIOD == $this->simulate->type ) {
					$data['period_unit'] = $this->simulate->period['period_unit'];
					$data['period_type'] = $this->simulate->period['period_type'];
					$title = __( 'View in: ', MS_TEXT_DOMAIN );
				}
				
				$view = MS_Factory::create( 'MS_View_Admin_Bar' );
				$view->data = apply_filters( 'ms_view_admin_bar_data', $data );
				$html = $view->to_html();
			}
						
			$wp_admin_bar->add_menu(
				apply_filters(
					'ms_controller_admin_bar_simulate_node',
					array(
						'id'     => 'membership-simulate-period',
						'title'  => $title,
						'href'   => '',
						'meta'   => array(
							'html'  => $html,
							'class' => apply_filters(
								'ms_controller_admin_bar_simulate_period_class',
								'membership-simulate-period'
							),
							'title' => __( 'Simulate period', MS_TEXT_DOMAIN ),
						),
					)
				)
			);
		}
	}

	/**
	 * Add 'View site as' node.
	 *
	 * Switches simulation views.
	 *
	 * @since 1.0.0
	 * 
	 */
	private function add_view_site_as_node() {
		global $wp_admin_bar;

		$title = __( 'View site as: ', MS_TEXT_DOMAIN );

		$select_groups = array();
		$parents = array();
		$current = null;

		foreach ( $this->memberships as $membership ) {
			$item_parent = $membership->get_parent();
			if ( $item_parent && ! isset( $parents[ $item_parent->id ] ) ) {
				$parents[ $item_parent->id ] = $item_parent;
			}

			// Create nonce fields
			$nonce = wp_create_nonce( 'ms_simulate-' . $membership->id );

			// Create options for <select>
			if ( empty( $select_groups[ $membership->parent_id ] ) || ! is_array( $select_groups[ $membership->parent_id ] ) ) {
				$select_groups[ $membership->parent_id ] = array();
			}
			$select_groups[ $membership->parent_id ][ $membership->id ] = array(
				'id' => $membership->id,
				'selected' => ( $this->simulate->membership_id == $membership->id ),
				'nonce' => $nonce,
				'label' => $membership->name,
			);

			if ( $this->simulate->membership_id == $membership->id ) {
				$current = $membership;
			}
		}

		// Remove parents from the available members-list.
		foreach ( $parents as $parent_id => $data ) {
			unset( $select_groups[0][ $parent_id ] );
		}

		$action_field = array(
			'name'   => 'action',
			'value'  => 'ms_simulate',
			'type'   => MS_Helper_Html::INPUT_TYPE_HIDDEN,
		);
		$membership_field = array(
			'id'     => 'ab-membership-id',
			'name'   => 'membership_id',
			'value'  => $this->simulate->membership_id,
			'type'   => MS_Helper_Html::INPUT_TYPE_HIDDEN,
		);
		$nonce_field = array(
			'id'     => '_wpnonce',
			'name'   => '_wpnonce',
			'value'  => '',
			'type'   => MS_Helper_Html::INPUT_TYPE_HIDDEN,
		);

		ob_start();
		?>
		<form id="view-site-as" method="GET">
			<select id="view-as-selector" class="ms-field-input ms-select ab-select" name="view-as-selector">
			<?php foreach ( $select_groups as $parent_id => $group ) {
				if ( $parent_id ) {
					printf(
						'<optgroup label="%1$s">',
						esc_attr( $parents[ $parent_id ]->name )
					);
				}
				foreach ( $group as $option ) {
					printf(
						'<option value="%1$s" nonce="%2$s" %3$s>%4$s</option>',
						esc_attr( $option['id'] ),
						esc_attr( $option['nonce'] ),
						selected( $option['selected'], true, false ),
						esc_html( $option['label'] )
					);
				}
			} ?>
			</select>
			<?php
			MS_Helper_Html::html_element( $action_field );
			MS_Helper_Html::html_element( $membership_field );
			MS_Helper_Html::html_element( $nonce_field );

			// Display information on the currently selected membership.
			if ( $current ) {
				if ( $current->parent_id ) {
					$group = $parents[ $current->parent_id ]->name;
					$desc = $parents[ $current->parent_id ]->get_type_description();
				} else {
					$group = '';
					$desc = $current->get_type_description();
				}
				printf(
					'<span class="ms-simulate-info">%1$s <small>%2$s</small></span>',
					esc_html( $desc ),
					esc_html( $group )
				);
			}
			?>
		</form>
		<?php

		$html = ob_get_clean();

		$wp_admin_bar->add_node(
			apply_filters(
				'ms_controller_admin_bar_add_view_site_as_node',
				array(
					'id'     => 'membership-simulate',
					'title'  => $title,
					'meta'   => array(
						'html'  => $html,
						'class' => apply_filters( 'ms_controller_admin_bar_view_site_as_class', 'membership-view-site-as' ),
						'title' => __( 'Select a membership to view your site as', MS_TEXT_DOMAIN ),
					),
				)
			)
		);
	}

	/**
	 * Add 'Test Memberships' node.
	 *
	 * @since 1.0.0
	 * 
	 */
	private function add_test_membership_node() {
		global $wp_admin_bar;

		$id = ! empty( $this->memberships ) ? $this->memberships[0]->id : false;

		if ( $id ) {

			$link_url = wp_nonce_url(
				admin_url( "?action=ms_simulate&membership_id={$id}", ( is_ssl() ? 'https' : 'http' ) ),
				"ms_simulate-{$id}"
			);

			$wp_admin_bar->add_node(
				apply_filters(
					'ms_controller_admin_bar_add_test_membership_node',
					array(
						'id'     => 'ms-test-memberships',
						'title'  => __( 'Test Memberships', MS_TEXT_DOMAIN ),
						'href'   => $link_url,
						'meta'   => array(
							'class'    => 'ms-test-memberships',
							'title'    => __( 'Membership Simulation Menu', MS_TEXT_DOMAIN ),
							'tabindex' => '1',
						),
					)
				)
			);
		}
	}

	/**
	 * Add 'Test Memberships' node.
	 *
	 * @since 1.0.0
	 * 
	 */
	private function add_exit_test_node() {
		global $wp_admin_bar;

		/* reset simulation */
		$id = 0;
		$link_url = wp_nonce_url(
			admin_url( "?action=ms_simulate&membership_id={$id}", ( is_ssl() ? 'https' : 'http' ) ),
			"ms_simulate-{$id}"
		);

		$wp_admin_bar->add_node(
			apply_filters(
				'ms_controller_admin_bar_add_exit_test_node',
				array(
					'id'     => 'ms-exit-memberships',
					'title'  => __( 'Exit Test Mode', MS_TEXT_DOMAIN ),
					'href'   => $link_url,
					'meta'   => array(
						'class'    => 'ms-exit-memberships',
						'title'    => __( 'Membership Simulation Menu', MS_TEXT_DOMAIN ),
						'tabindex' => '1',
					),
				)
			)
		);
	}

	/**
	 * Customize the Admin Toolbar for front end users.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_before_admin_bar_render
	 *
	 * @since 1.0.0
	 * 
	 */
	public function customize_toolbar_front() {
		if ( ! $this->is_admin_user() ) {
			$this->remove_admin_bar_nodes();
		}
	}

	/**
	 * Customize the Admin sidebar for front end users.
	 *
	 * **Hooks Actions: **
	 *
	 * * admin_head-profile.php
	 *
	 * @since 1.0.0
	 * 
	 */
	public function customize_admin_sidebar() {
		global $menu;

		if ( ! $this->is_admin_user() ) {
			foreach ( $menu as $key => $menu_item ) {
				unset( $menu[ $key ] );
			}
		}
	}

	/**
	 * Enqueues necessary scripts and styles.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_enqueue_scripts
	 * * admin_enqueue_scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_localize_script(
			'ms-controller-admin-bar',
			'ms',
			array(
				'switching_text' => __( 'Switching...', MS_TEXT_DOMAIN ),
			)
		);

		wp_enqueue_script( 'ms-controller-admin-bar' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_enqueue_style( 'ms-public' );

	}
}