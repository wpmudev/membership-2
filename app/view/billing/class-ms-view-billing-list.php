<?php
/**
 * Renders Billing/Transaction History.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage View
 */
class MS_View_Billing_List extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$this->check_simulation();

		$buttons = array();

		$module = 'billing';
		if ( isset( $_GET['show'] ) ) {
			$module = $_GET['show'];
		}

		if ( ! $module ) {
			// Show a message if there are error-state transactions.
			$args = array( 'state' => 'err' );
			$error_count = MS_Model_Transactionlog::get_item_count( $args );

			if ( $error_count ) {
				if ( 1 == $error_count ) {
					$message = __( 'One transaction failed. Please %2$sreview the logs%3$s and decide if you want to ignore the transaction or manually assign it to an invoice.', MS_TEXT_DOMAIN );
				} else {
					$message = __( '%1$s transactions failed. Please %2$sreview the logs%3$s and decide if you want to ignore the transaction or manually assign it to an invoice.', MS_TEXT_DOMAIN );
				}
				$review_url = MS_Controller_Plugin::get_admin_url(
					'billing',
					array(
						'show' => 'logs',
						'state' => 'err',
					)
				);

				lib2()->ui->admin_message(
					sprintf(
						$message,
						$error_count,
						'<a href="' . $review_url . '">',
						'</a>'
					),
					'err'
				);
			}
		}

		// Decide which list to display in the Billings page.
		switch ( $module ) {
			// Transaction logs.
			case 'logs':
				$title = __( 'Transaction Logs', MS_TEXT_DOMAIN );

				$listview = MS_Factory::create( 'MS_Helper_ListTable_TransactionLog' );
				$listview->prepare_items();

				$buttons[] = array(
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => MS_Controller_Plugin::get_admin_url(
						'billing'
					),
					'value' => __( 'Show Invoices', MS_TEXT_DOMAIN ),
					'class' => 'button',
				);
				break;

			// M1 Migration matching.
			case 'matching':
				$title = __( 'Automatic Transaction Matching', MS_TEXT_DOMAIN );

				$listview = MS_Factory::create( 'MS_Helper_ListTable_TransactionMatching' );
				$listview->prepare_items();

				$buttons[] = array(
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => MS_Controller_Plugin::get_admin_url(
						'billing'
					),
					'value' => __( 'Show Invoices', MS_TEXT_DOMAIN ),
					'class' => 'button',
				);
				$buttons[] = array(
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => MS_Controller_Plugin::get_admin_url(
						'billing',
						array( 'show' => 'logs' )
					),
					'value' => __( 'Show Transaction Logs', MS_TEXT_DOMAIN ),
					'class' => 'button',
				);

				break;

			// Default billings list.
			case 'billing':
			default:
				$title = __( 'Billing', MS_TEXT_DOMAIN );

				$listview = MS_Factory::create( 'MS_Helper_ListTable_Billing' );
				$listview->prepare_items();

				$buttons[] = array(
					'id' => 'add_new',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => MS_Controller_Plugin::get_admin_url(
						'billing',
						array(
							'action' => MS_Controller_Billing::ACTION_EDIT,
							'invoice_id' => 0,
						)
					),
					'value' => __( 'Create new Invoice', MS_TEXT_DOMAIN ),
					'class' => 'button',
				);
				$buttons[] = array(
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => MS_Controller_Plugin::get_admin_url(
						'billing',
						array( 'show' => 'logs' )
					),
					'value' => __( 'Show Transaction Logs', MS_TEXT_DOMAIN ),
					'class' => 'button',
				);

				if ( ! empty( $_GET['gateway_id'] ) ) {
					$gateway = MS_Model_Gateway::factory( $_GET['gateway_id'] );
					if ( $gateway->name ) {
						$title .= ' - ' . $gateway->name;
					}
				}
				break;
		}

		if ( 'matching' != $module ) {
			if ( MS_Model_Import::can_match() ) {
				$buttons[] = array(
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => MS_Controller_Plugin::get_admin_url(
						'billing',
						array( 'show' => 'matching' )
					),
					'value' => __( 'Setup automatic matching', MS_TEXT_DOMAIN ),
					'class' => 'button',
				);
			}
		}

		// Default list view part - dislay prepared values from above.
		ob_start();
		?>

		<div class="wrap ms-wrap ms-billing">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => $title,
					'title_icon_class' => 'wpmui-fa wpmui-fa-credit-card',
				)
			);
			?>
			<div>
				<?php
				foreach ( $buttons as $button ) {
					MS_Helper_Html::html_element( $button );
				}
				?>
			</div>
			<?php
			$listview->views();
			$listview->search_box(
				__( 'User', MS_TEXT_DOMAIN ),
				'search'
			);
			?>
			<form action="" method="post">
				<?php $listview->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_view_billing_list',
			$html,
			$this
		);
	}
}