<?php
/**
 * Display an edit form where a single member can be added or details of a
 * member can be edited.
 *
 * @since 1.0.1.0
 */
class MS_View_Member_Editor extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.1.0
	 * @return string
	 */
	public function to_html() {

		if ( 'add' == $this->data['action'] ) {
			$title = __( 'Add or Select Member', MS_TEXT_DOMAIN );
			$groups = $this->prepare_fields_add();
		} else {
			$title = __( 'Edit Member', MS_TEXT_DOMAIN );
			$groups = $this->prepare_fields_edit();
		}

		ob_start();
		?>
		<div class="ms-wrap ms-add-member">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title_icon_class' => 'wpmui-fa wpmui-fa-user',
					'title' => $title,
					'desc' => '',
				)
			);
			?>
			<div class="ms-settings ms-add-member">
			<?php foreach ( $groups as $class => $fields ) : ?>
				<div class="ms-field-group ms-group-<?php echo esc_attr( $class ); ?>">
				<div class="ms-field-group-inner">
				<form method="post">
				<?php
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>
				</div></div>
				</form>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Input fields displayed in the "Add or Select Member" screen.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	public function prepare_fields_add() {
		$action_add = MS_Controller_Member::ACTION_ADD_MEMBER;
		$action_select = MS_Controller_Member::ACTION_SELECT_MEMBER;

		$fields = array();
		$fields['create'] = array(
			'title' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'class' => 'group-title',
				'value' => __( 'Create a new WordPress user', MS_TEXT_DOMAIN ),
			),
			'username' => array(
				'id' => 'username',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'User name', MS_TEXT_DOMAIN ) . ' *',
				'after' => ' ',
				'class' => 'required',
			),
			'email' => array(
				'id' => 'email',
				'type' => MS_Helper_Html::INPUT_TYPE_EMAIL,
				'title' => __( 'Email address', MS_TEXT_DOMAIN ) . ' *',
				'after' => ' ',
				'class' => 'required',
			),
			'first_name' => array(
				'id' => 'first_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'First name', MS_TEXT_DOMAIN ),
			),
			'last_name' => array(
				'id' => 'last_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Last name', MS_TEXT_DOMAIN ),
			),
			'password' => array(
				'id' => 'password',
				'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'title' => __( 'Password', MS_TEXT_DOMAIN ),
			),
			'info' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => __( 'We create a new WordPress user without sending a confirmation email.', MS_TEXT_DOMAIN ),
				'class' => 'info-field',
			),
			'sep' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'button' => array(
				'id' => 'btn_create',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Create user', MS_TEXT_DOMAIN ) . ' &raquo;',
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_add,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_add ),
			),
		);

		$fields['select'] = array(
			'title' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'class' => 'group-title',
				'value' => __( 'Select an existing WordPress user', MS_TEXT_DOMAIN ),
			),
			'select_user' => array(
				'id' => 'select_user',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'title' => __( 'Existing WordPress users', MS_TEXT_DOMAIN ),
				'class' => 'manual-init no-auto-init widefat',
			),
			'sep' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'button' => array(
				'id' => 'btn_select',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Select', MS_TEXT_DOMAIN ) . ' &raquo;',
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_select,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_select ),
			),
		);

		return apply_filters(
			'ms_view_member_editor_fields_add',
			$fields
		);
	}

	/**
	 * Input fields displayed in the "Edit Member" screen.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	public function prepare_fields_edit() {
		$user_id = $this->data['user_id'];

		$fields = array();
		$fields['editor'] = array(
			'name' => array(
				'id' => 'name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Name Your Membership:', MS_TEXT_DOMAIN ),
				'value' => $membership->name,
				'class' => 'ms-text-large',
				'placeholder' => __( 'Choose a name that will identify this membership...', MS_TEXT_DOMAIN ),
				'label_type' => 'h3',
			),
		);

		return apply_filters(
			'ms_view_member_editor_fields_edit',
			$fields
		);
	}
}
