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
 * Renders Admin Bar's simulation.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 */
class MS_View_Adminbar extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();
		$sim = $this->data['subscription'];
		$mem = $sim->get_membership();
		$pay_types = $mem->get_payment_types();

		if ( is_admin() ) {
			$toggle_icon = 'dashicons-arrow-down';
			$toggle_state = 'collapsed';
		} else {
			$toggle_icon = 'dashicons-arrow-up';
			$toggle_state = '';
		}

		$details = lib2()->session->get( 'ms-access' );
		$denied_url = false;
		$deciding_membership = false;
		$deciding_rule = false;

		if ( isset( $details[1] ) && ! $details[1]['has_access'] ) {
			$denied_url = $details[1]['url'];
			$deciding_membership = $details[1]['deciding_membership'];
			$deciding_rule = $details[1]['deciding_rule'];
		} elseif ( isset( $details[0] ) && $details[0]['has_access'] ) {
			$deciding_membership = $details[0]['deciding_membership'];
			$deciding_rule = $details[0]['deciding_rule'];
		}

		ob_start();
		$this->output_scripts();
		?>
		<div class="ms-sim-info <?php echo esc_attr( $toggle_state ); ?>">
			<div class="ms-sim-block">
				<h4 class="toggle-wrap">
					<?php _e( 'Simulation Overview', MS_TEXT_DOMAIN ); ?>
					<span class="toggle"><i class="dashicons <?php echo esc_attr( $toggle_icon ); ?>"></i></span>
				</h4>
				<div class="ms-sim-body">
					<form id="view-site-as" method="POST" class="inside">
					<table cellspacing="0" cellpadding="0" width="100%" border="0">
						<tr>
							<th><?php _e( 'View as', MS_TEXT_DOMAIN ); ?></th>
							<td><?php MS_Helper_Html::html_element( $fields['membership_id'] ) ?></td>
						</tr>
						<?php if ( $this->data['datepicker'] ) : ?>
						<tr>
							<th><?php _e( 'View on', MS_TEXT_DOMAIN ); ?></th>
							<td><?php MS_Helper_Html::html_element( $fields['simulate_date'] ) ?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th>&nbsp;</th>
							<td><button class="button"><?php _e( 'Update', MS_TEXT_DOMAIN ); ?></button></td>
						</tr>
					</table>
					<?php
					MS_Helper_Html::html_element( $fields['action_field'] );
					MS_Helper_Html::html_element( $fields['nonce_field'] );
					?>
					</form>

					<h4 class="inside">
						<?php _e( 'Simulated Membership', MS_TEXT_DOMAIN ); ?>
					</h4>
					<table cellspacing="0" cellpadding="0" width="100%" border="0" class="inside">
						<tr>
							<th><?php _e( 'Membership', MS_TEXT_DOMAIN ); ?></th>
							<td style="white-space: nowrap"><?php echo esc_html( $mem->name ); ?></td>
						</tr>
						<tr>
							<th><?php _e( 'Type', MS_TEXT_DOMAIN ); ?></th>
							<td><?php echo esc_html( $mem->get_type_description() ); ?></td>
						</tr>
						<tr>
							<th><?php _e( 'Start Date', MS_TEXT_DOMAIN ); ?></th>
							<td><?php echo esc_html( $sim->start_date ); ?></td>
						</tr>
						<tr>
							<th><?php _e( 'Expire Date', MS_TEXT_DOMAIN ); ?></th>
							<td><?php echo esc_html( $sim->expire_date ); ?></td>
						</tr>
						<?php if ( $this->data['datepicker'] ) : ?>
						<tr>
							<th><?php _e( 'Simulated Date', MS_TEXT_DOMAIN ); ?></th>
							<td><?php echo esc_html( MS_Helper_Period::current_date() ); ?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th><?php _e( 'Status', MS_TEXT_DOMAIN ); ?></th>
							<td><?php
							if ( MS_Model_Relationship::STATUS_ACTIVE == $sim->status ) {
								$status_class = 'ms-sim-active';
							} else {
								$status_class = 'ms-sim-inactive';
							}
							printf(
								'<span class="%1$s">%2$s</span>',
								$status_class,
								$sim->status
							);
							?></td>
						</tr>
						<tr>
							<th><?php _e( 'Payment model', MS_TEXT_DOMAIN ); ?></th>
							<td><?php echo esc_html( $pay_types[ $mem->payment_type ] ); ?></td>
						</tr>
						<tr>
							<th><?php _e( 'Payment details', MS_TEXT_DOMAIN ); ?></th>
							<td><?php echo esc_html( strip_tags( $sim->get_payment_description( null, true ) ) ); ?></td>
						</tr>
					</table>
				</div>
				<div class="ms-sim-footer">
					<div class="inside">
					<?php $this->output_deciding_info( $denied_url, $deciding_membership, $deciding_rule ); ?>
					</div>
					<?php MS_Helper_Html::html_element( $fields['exit_button'] ); ?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_view_admin_bar_to_html',
			$html,
			$this
		);
	}

	/**
	 * Prepare html fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function prepare_fields() {
		// The ID of the main system membership.
		$base_id = MS_Model_Membership::get_base()->id;
		$sorted_memberships = array();
		$memberships = MS_Model_Membership::get_memberships(
			array( 'include_base' => 1 )
		);

		foreach ( $memberships as $membership ) {
			if ( $base_id == $membership->id ) {
				$label = __( '- No membership / Visitor -', MS_TEXT_DOMAIN );
			} else {
				$label = $membership->name;
				if ( ! $membership->active ) {
					$label .= ' ' . __( '(Inactive)', MS_TEXT_DOMAIN );
				}
			}

			$sorted_memberships[ $membership->id ] = $label;
		}
		asort( $sorted_memberships );

		$fields = array(
			'exit_button' => array(
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Exit Test Mode', MS_TEXT_DOMAIN ),
				'url' => MS_Controller_Adminbar::get_simulation_exit_url(),
				'class' => 'button',
			),

			'action_field' => array(
				'name'   => 'action',
				'value'  => 'ms_simulate',
				'type'   => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			),

			'membership_id' => array(
				'id'     => 'ab-membership-id',
				'name'   => 'membership_id',
				'value'  => $this->data['membership_id'],
				'type'   => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $sorted_memberships,
			),

			'nonce_field' => array(
				'id'     => '_wpnonce',
				'value'  => wp_create_nonce( 'ms_simulate' ),
				'type'   => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			),

			'simulate_date' => array(
				'id' => 'simulate_date',
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => $this->data['simulate_date'],
				'class' => 'ms-admin-bar-date ms-date',
			),

			'simulate_submit' => array(
				'id' => 'simulate_submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Go', MS_TEXT_DOMAIN ),
				'class' => 'ms-admin-bar-submit',
			),
		);

		return apply_filters(
			'ms_view_admin_bar_prepare_fields',
			$fields,
			$this
		);
	}

	/**
	 * Output details on which membership denied or allowed access to the
	 * current page
	 *
	 * @since  1.1.0
	 * @param  string $denied_url URL of the denied page (empty if not denied)
	 * @param  int $membership_id Membership_id
	 * @param  array $rules List of Rule_types
	 */
	protected function output_deciding_info( $denied_url, $membership_id, $rules ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		$rule_title = MS_Model_Rule::get_rule_type_titles();

		if ( ! empty( $denied_url ) ) { ?>
			<div class="ms-sim-denied">
			<?php
			printf(
				__( 'Access denied by %s', MS_TEXT_DOMAIN ),
				'<b>' . esc_html( $membership->name ) . '</b>'
			);
			?>
			</div>
			<ul class="ms-sim-rules">
			<?php
			printf(
				'<li><a href="%1$s">%1$s</a></li>',
				$denied_url
			);
			foreach ( $rules as $rule_type ) {
				printf(
					'<li><a href="%1$s" target="_blank">%3$s %2$s</a></li>',
					MS_Controller_Plugin::get_admin_url(
						'protection',
						array(
							'tab' => $rule_type,
							'membership_id' => $membership->id,
						)
					),
					$rule_title[$rule_type],
					__( 'Denied by Rule:', MS_TEXT_DOMAIN )
				);
			}
			?>
		<?php } elseif ( $membership->is_valid() ) { ?>
			<div class="ms-sim-allowed">
			<?php
			printf(
				__( 'Access granted by %s', MS_TEXT_DOMAIN ),
				'<b>' . esc_html( $membership->name ) . '</b>'
			);
			?>
			</div>
			<ul class="ms-sim-rules">
			<?php
			foreach ( $rules as $rule_type ) {
				printf(
					'<li><a href="%1$s" target="_blank">%3$s %2$s</a></li>',
					MS_Controller_Plugin::get_admin_url(
						'protection',
						array(
							'tab' => $rule_type,
							'membership_id' => $membership->id,
						)
					),
					$rule_title[$rule_type],
					__( 'Allowed by Rule:', MS_TEXT_DOMAIN )
				);
			}
			?>
			</ul>
		<?php } else { ?>
			<div class="ms-sim-public">
			<?php _e( 'Unprotected', MS_TEXT_DOMAIN ); ?>
			</div>
		<?php
		}
	}

	/**
	 * Output the JS and CSS needed for simulation infos
	 *
	 * @since  1.1.0
	 */
	protected function output_scripts() {
		?>
		<style>
		.ms-sim-info {
			position: fixed;
			top: 40px;
			right: 10px;
			border: 1px solid rgba( 224, 102, 102, 0.5 );
			box-shadow: 0 1px 2px rgba(128,0,0,0.15);
			padding: 0;
			font: 13px sans-serif;
			width: 360px;
			z-index: 999999;
			overflow: auto;
			bottom: 20px;
			opacity: 0.8;
		}
		.ms-sim-info:hover {
			border-color: #E06666;
			opacity: 1;
		}
		.ms-sim-info:hover .ms-sim-block {
			opacity: 1;
			background: #FCE0E0;
		}
		.ms-sim-info .ms-sim-block {
			background: #FFF;
			opacity: 0.4;
			min-height: 100%;
			padding: 45px 10px 10px;
			box-sizing: border-box;
		}
		.ms-sim-info select,
		.ms-sim-info input {
			font-size: 13px;
			margin: 0;
			line-height: 20px;
			padding: 2px 10px;
			height: 26px;
			border: 1px solid #CCC;
			background: #FFF;
			color: #444;
			border-radius: 0;
			font-family: sans-serif;
			box-sizing: border-box;
			vertical-align: middle;
		}
		.ms-sim-info h4 {
			padding: 0;
			margin: -10px -10px 10px -10px;
			border-bottom: 1px solid rgba(0,0,0,0.2);
			background: #FFF;
			color: #C00;
			text-align: center;
			font-weight: bold;
			height: 34px;
			line-height: 34px;
			position: relative;
		}
		.ms-sim-info h4.toggle-wrap {
			cursor: pointer;
			position: fixed;
			top: 40px;
			right: 10px;
			width: 360px;
			margin: 0;
			z-index: 10;
		}
		.ms-sim-info h4 .toggle {
			position: absolute;
			right: 0;
			top: 0;
			bottom: 0;
			width: 34px;
			color: #AAA;
		}
		.ms-sim-info h4 .toggle .dashicons {
			margin: 7px;
		}
		.ms-sim-info h4.inside {
			border-top: 1px solid #E06666;
			margin-top: 0;
			background: rgba(255,255,255,0.75);
		}
		.ms-sim-info.collapsed {
			bottom: auto;
		}
		.ms-sim-info.collapsed .inside {
			display: none;
		}
		.ms-sim-info table {
			margin: 0 0 20px 0;
			padding: 0;
			border: 0;
		}
		.ms-sim-info td,
		.ms-sim-info th {
			padding: 5px;
			border: 0;
			background: transparent;
			text-align: left;
		}
		.ms-sim-info th {
			width: 40%;
		}
		.ms-sim-info td {
			width: 60%;
		}
		.ms-sim-info table tr:nth-child(odd) td,
		.ms-sim-info table tr:nth-child(odd) th {
			background: rgba(0,0,0,0.05);
		}
		.ms-sim-info .button {
			display: inline-block;
			text-decoration: none;
			font-size: 13px;
			line-height: 28px;
			height: 28px;
			margin: 0;
			padding: 0 10px 1px;
			cursor: pointer;
			border-width: 1px;
			border-style: solid;
			-webkit-appearance: none;
			-webkit-border-radius: 3px;
			border-radius: 3px;
			white-space: nowrap;
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			box-shadow: inset 0 1px 0 #fff, 0 1px 0 rgba( 0, 0, 0, 0.08 );
			vertical-align: top;
			background: #fafafa;
			border-color: #999;
			color: #222;
		}
		.ms-sim-info .ms-sim-public,
		.ms-sim-info .ms-sim-denied,
		.ms-sim-info .ms-sim-allowed {
			padding: 5px;
			color: #FFF;
			text-align: center;
			background: rgba(0,128,0,0.4);
			margin-bottom: 5px;
		}
		.ms-sim-info .ms-sim-denied {
			background: #C33;
		}
		.ms-sim-info .ms-sim-active {
			color: #060;
		}
		.ms-sim-info .ms-sim-inactive {
			color: #F00;
			font-weight: bold;
		}
		.ms-sim-info .ms-sim-rules {
			margin: 0 0 20px 0;
			padding: 0;
			list-style: none;
		}
		.ms-sim-info .ms-sim-rules li {
			padding: 3px 10px;
			margin: 0;
			display: block;
		}

		.ui-datepicker {
			width: 17em;
			padding: .2em .2em 0;
			display: none;
		}
		.ui-datepicker .ui-datepicker-header {
			position: relative;
			padding: .2em 0;
		}
		.ui-datepicker .ui-datepicker-prev,
		.ui-datepicker .ui-datepicker-next {
			position: absolute;
			top: 2px;
			width: 1.8em;
			height: 1.8em;
		}
		.ui-datepicker .ui-datepicker-prev-hover,
		.ui-datepicker .ui-datepicker-next-hover {
			top: 1px;
		}
		.ui-datepicker .ui-datepicker-prev {
			left: 2px;
		}
		.ui-datepicker .ui-datepicker-next {
			right: 2px;
		}
		.ui-datepicker .ui-datepicker-prev-hover {
			left: 1px;
		}
		.ui-datepicker .ui-datepicker-next-hover {
			right: 1px;
		}
		.ui-datepicker .ui-datepicker-title {
			margin: 0 2.3em;
			line-height: 1.8em;
			text-align: center;
		}
		.ui-datepicker .ui-datepicker-title select {
			font-size: 1em;
			margin: 1px 0;
		}
		.ui-datepicker select.ui-datepicker-month,
		.ui-datepicker select.ui-datepicker-year {
			width: 49%;
		}
		.ui-datepicker table {
			width: 100%;
			font-size: .9em;
			border-collapse: collapse;
			margin: 0 0 .4em;
		}
		.ui-datepicker th {
			padding: .7em .3em;
			text-align: center;
			font-weight: bold;
			border: 0;
		}
		.ui-datepicker td {
			border: 0;
			padding: 1px;
		}
		.ui-datepicker td span,
		.ui-datepicker td a {
			display: block;
			padding: .2em;
			text-align: right;
			text-decoration: none;
		}
		.ui-datepicker .ui-datepicker-buttonpane {
			background-image: none;
			margin: .7em 0 0 0;
			padding: 0 .2em;
			border-left: 0;
			border-right: 0;
			border-bottom: 0;
		}
		.ui-datepicker .ui-datepicker-buttonpane button {
			float: right;
			margin: .5em .2em .4em;
			cursor: pointer;
			padding: .2em .6em .3em .6em;
			width: auto;
			overflow: visible;
		}
		.ui-datepicker .ui-datepicker-buttonpane button.ui-datepicker-current {
			float: left;
		}
		.wpmui-datepicker-wrapper {
			display: inline-block;
			position: relative;
		}
		.wpmui-datepicker-wrapper .wpmui-icon {
			position: absolute;
			right: 0;
			top: 0;
			height: 28px;
			width: 21px;
			line-height: 28px;
			text-align: left;
			opacity: .5;
			cursor: pointer;
			color: #444;
		}
		.ui-datepicker.wpmui-datepicker {
			padding: 0;
			border: none;
			background: transparent;
			z-index: 20;
			width: 250px;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-header,
		.ui-datepicker.wpmui-datepicker.ui-corner-all {
			border-radius: 0;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-prev,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-prev.ui-state-hover,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-next,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-next.ui-state-hover,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-title {
			line-height: 2.5em;
			height: 2.5em;
			padding: 0;
			border: 0;
			background: transparent;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-prev.ui-datepicker-prev-hover {
			top: 2px;
			left: 2px;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-next.ui-datepicker-next-hover {
			top: 2px;
			right: 2px;
		}
		.ui-datepicker .ui-datepicker-prev span,
		.ui-datepicker .ui-datepicker-next span {
			display: block;
			position: absolute;
			left: 50%;
			margin-left: -8px;
			top: 50%;
			margin-top: -8px;
		}
		.ui-icon-circle-triangle-e {
			background-position: -48px -192px;
		}
		.ui-icon-circle-triangle-w {
			background-position: -80px -192px;
		}
		.ui-datepicker.wpmui-datepicker .ui-icon {
			width: 16px;
			height: 16px;
			text-indent: -99999px;
			overflow: hidden;
			background-repeat: no-repeat;
			background-image: url(<?php echo MS_Plugin::instance()->url; ?>/app/assets/images/ui-icons-white.png);
			cursor: pointer;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-header {
			border: none;
			background: #4F83AA;
			color: #FFFFFF;
			font-size: 0.9em;
			font-weight: normal;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody td a,
		.ui-datepicker.wpmui-datepicker td span,
		.ui-datepicker.wpmui-datepicker td a {
			padding: 8px;
			text-align: center;
			text-decoration: none;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar {
			padding: 0;
			background: #375D7A;
			width: 250px;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar thead th {
			padding: 3px 5px;
			font-size: 0.75em;
			font-weight: normal;
			color: #FFFFFF;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-state-default,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-widget-content .ui-state-default,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-widget-header .ui-state-default {
			border: 0;
			background: #223D52;
			color: #FFFFFF;
		}
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-state-active,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-widget-content .ui-state-active,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-widget-header .ui-state-active,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-state-hover,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-widget-content .ui-state-hover,
		.ui-datepicker.wpmui-datepicker .ui-datepicker-calendar tbody .ui-widget-header .ui-state-hover {
			background: #4F83AA;
		}
		.wpmui-datepicker {
			z-index: 1000000 !important;
		}
		</style>
		<script>
		jQuery(function(){
			jQuery( '.ms-sim-info .toggle-wrap' ).click(function() {
				var el = jQuery( this );
				el.find( '.dashicons' ).toggleClass( 'dashicons-arrow-up dashicons-arrow-down' );
				el.closest( '.ms-sim-info' ).toggleClass( 'collapsed' );
			});
		});
		</script>
		<?php
	}
}