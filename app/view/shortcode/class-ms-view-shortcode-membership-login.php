<?php

class MS_View_Shortcode_Membership_Login extends MS_View {
	
	protected $data;
	
	public function to_html() {
		$html = "";
		if( MS_Model_Member::is_logged_user() ) {
			return;
		}
		else {
			extract( $this->data );
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
			
			$html .= wp_login_form( array(
					'echo'     => false,
					'redirect' => ! empty( $redirect )
					? $redirect
					: home_url( $_SERVER['REQUEST_URI'] )
			) );
			
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
		} 
		return $html;
	}
}