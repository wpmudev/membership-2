<?php

class MS_View_Shortcode_Membership_Login extends MS_View {

	public function to_html() {
		$html = '';
		if ( MS_Model_Member::is_logged_user() ) {
			return '';
		}
		else {
			extract( $this->data );
			if ( $header ) {
				$html .= $this->login_header_html();
			}
			if ( ! empty( $holder ) ) {
				$html .= "<{$holder} class='{$holderclass}'>";
			}
			if ( ! empty( $item ) ) {
				$html .= "<{$item} class='{$itemclass}'>";
			}
			$html .= $prefix;
			// The title
			if ( ! empty( $wrapwith ) ) {
				$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
			}
			$html .= wp_login_form(
				array(
					'echo'     => false,
					'redirect' => ! empty( $redirect ) ? $redirect : MS_Helper_Utility::get_current_page_url(),
				)
			);
			if ( ! empty( $lostpass ) ) {
				$html .= sprintf( '<a href="%s">%s</a>', esc_url( $lostpass ), __( 'Lost password?', MS_TEXT_DOMAIN ) );
			}
			if ( ! empty( $wrapwith ) ) {
				$html .= "</{$wrapwith}>";
			}
			$html .= $postfix;
			if ( ! empty( $item ) ) {
				$html .= "</{$item}>";
			}
			if ( ! empty( $holder ) ) {
				$html .= "</{$holder}>";
			}
			if ( $register ) {
				$html .= wp_register( '', '', false );
			}
		}
		return $html;
	}

	private function login_header_html() {
		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<legend><?php echo $this->data['title']; ?></legend>
			<div class="ms-alert-box ms-alert-error">
				<?php _e( 'You are not currently logged in. Please login to access the page.', MS_TEXT_DOMAIN ); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
}