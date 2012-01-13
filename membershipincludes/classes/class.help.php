<?php
if(!class_exists('M_Help')) {

	class M_Help {

		function __construct( $screen_id = false ) {
		}

		function M_Help( $screen_id = false ) {
			$this->__construct();
		}

		function show() {

			$html = $this->get();

			echo $html;

		}

		function get() {

			switch($screen_id) {

				case 'toplevel_page_membership':					$help = $this->dashboard_help();
																	break;

				case 'membership_page_membershiplevels':			$help = $this->levels_help();
																	break;

				case 'membership_page_membershipsubs':				$help = $this->subs_help();
																	break;

				case 'membership_page_membershipgateways':			$help = $this->gateways_help();
																	break;

				case 'membership_page_membershipcommunication':		$help = $this->communication_help();
																	break;

				case 'membership_page_membershipurlgroups':			$help = $this->urlgroups_help();
																	break;

				case 'membership_page_membershippings':				$help = $this->pings_help();
																	break;

				case 'membership_page_membershipoptions':			$help = $this->options_help();
																	break;

				case 'membership_page_membershipaddons':			$help = $this->addons_help();
																	break;

			}


		}

		// Specific help content creation functions

		function dashboard_help() {

			$html = '';

			return $html;

		}

		function levels_help() {

			$html = '';

			return $html;

		}

		function subs_help() {

			$html = '';

			return $html;

		}

		function gateways_help() {

			$html = '';

			return $html;

		}

		function communication_help() {

			$html = '';

			return $html;

		}

		function urlgroups_help() {

			$html = '';

			return $html;

		}

		function pings_help() {

			$html = '';

			return $html;

		}

		function options_help() {

			$html = '';

			return $html;

		}

		function addons_help() {

			$html = '';

			return $html;

		}



	}

}
?>