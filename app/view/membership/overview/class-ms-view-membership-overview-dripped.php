<?php

class MS_View_Membership_Overview_Dripped extends MS_View_Membership_Overview_Simple {

	public function available_content_panel_data() {
		$relative = array();
		$absolute = array();
		$membership = $this->data['membership'];
		$protected_content = MS_Model_Membership::get_base();
		$rule_types = MS_Model_Rule::get_dripped_rule_types();

		foreach ( $rule_types as $rule_type ) {
			$rule = $membership->get_rule( $rule_type );
			$contents = $rule->get_contents( array( 'protected_content' => 1 ) );

			foreach ( $contents as $content ) {
				if ( $rule->has_dripped_rules( $content->id ) ) {
					$infos = $rule->dripped[$content->id];
					$key = false;
					$content->date = $rule->get_dripped_description( $content->id );
					$content->icon = 'visibility';

					switch ( $infos['type'] ) {
						case MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION:
							$content->icon = 'clock';
							$key = MS_Helper_Period::get_period_in_days(
								$infos['delay_unit'],
								$infos['delay_type']
							);
							$key = 100000 + ($key * 1000);
							while ( isset( $relative[ $key ] ) ) { $key += 1; }
							$relative[$key] = $content;
							break;

						case MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE:
							$key = preg_replace( '/[^0-9]/', '', $infos['date'] );
							if ( ! $rule->has_access( $content->id ) ) {
								$content->icon = 'lock';
							}
							// Fall through

						case MS_Model_Rule::DRIPPED_TYPE_INSTANTLY:
						default:
							if ( empty( $key ) ) { $key = 0; }

							$key = ($key * 1000);
							while ( isset( $relative[ $key ] ) ) { $key += 1; }
							$absolute[$key] = $content;
							break;
					}
				}
			}
		}
		?>
		<div class="clear"></div>

		<div class="cf">
			<div class="ms-half ms-available-soon space">
				<div class="ms-bold">
					<i class="dashicons dashicons-calendar ms-low"></i>
					<?php _e( 'Available on a specific date:', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="inside">
					<?php $this->content_box( $absolute ); ?>
				</div>
			</div>

			<div class="ms-half ms-available">
				<div class="ms-bold">
					<i class="dashicons dashicons-clock ms-low"></i>
					<?php _e( 'Relative to registration:', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="inside">
					<?php $this->content_box( $relative ); ?>
				</div>
			</div>
		</div>

		<div class="cf">
			<div class="ms-half">
				<div class="inside">
					<div class="ms-protection-edit-wrapper">
						<?php
						$edit_url = esc_url_raw(
							add_query_arg(
								array(
									'page' => 'protected-content-setup',
									'step' => MS_Controller_Membership::STEP_PROTECTED_CONTENT,
									'tab' => $rule->rule_type,
									'membership_id' => $membership->id,
								)
							)
						);
						MS_Helper_Html::html_element(
							array(
								'id' => 'edit_dripped',
								'type' => MS_Helper_Html::TYPE_HTML_LINK,
								'value' => __( 'Edit Dripped Content', MS_TEXT_DOMAIN ),
								'url' => $edit_url,
								'class' => 'wpmui-field-button button',
							)
						);

						if ( ! $membership->is_free ) {
							$payment_url = esc_url_raw(
								add_query_arg(
									array(
										'step' => MS_Controller_Membership::STEP_PAYMENT,
										'edit' => 1,
									)
								)
							);
							MS_Helper_Html::html_element(
								array(
									'id' => 'setup_payment',
									'type' => MS_Helper_Html::TYPE_HTML_LINK,
									'value' => __( 'Payment Options', MS_TEXT_DOMAIN ),
									'url' => $payment_url,
									'class' => 'wpmui-field-button button',
								)
							);
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Echo a content list as 2-column table that show Content-Title and the
	 * Available date.
	 * Used by Dripped-Content view.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $contents List of content items to display.
	 */
	protected function content_box( $contents ) {
		$row_class = '';
		ksort( $contents );
		$rule_titles = MS_Model_Rule::get_rule_type_titles();

		$edit_url = esc_url_raw(
			add_query_arg(
				array(
					'page' => 'protected-content-setup',
					'step' => MS_Controller_Membership::STEP_PROTECTED_CONTENT,
					'tab' => $rule->rule_type,
				)
			)
		);
		$edit_link = array(
			'id' => 'edit_dripped',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
		);

		?>
		<table class="ms-list-table ms-list-date widefat">
			<thead>
				<tr>
					<th class="col-icon">&nbsp;</th>
					<th class="col-text"><?php _e( 'Rule', MS_TEXT_DOMAIN ); ?></th>
					<th class="col-text"><?php _e( 'Protected Item', MS_TEXT_DOMAIN ); ?></th>
					<th class="col-date"><?php _e( 'Access', MS_TEXT_DOMAIN ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $contents as $id => $content ) :
				$row_class = ($row_class == 'alternate' ? '' : 'alternate' );
				?>
				<tr class="<?php echo esc_attr( $row_class . ' ' . $content->icon ); ?>">
					<td class="col-icon">
						<i class="dashicons dashicons-<?php echo esc_attr( $content->icon ); ?>"></i>
					</td>
					<td class="col-text col-type">
						<?php
						$edit_link['url'] = esc_url_raw(
							add_query_arg(
								'tab',
								$content->type,
								$edit_url
							)
						);
						$edit_link['value'] = $rule_titles[ $content->type ];
						MS_Helper_Html::html_element( $edit_link );
						?>
					</td>
					<td class="col-text">
						<?php MS_Helper_Html::content_tag( $content, 'span' ); ?>
					</td>
					<td class="col-date">
						<?php echo '' . $content->date; ?>
					</td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
		<?php
	}
}