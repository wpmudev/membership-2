<?php
if(!class_exists('M_Help')) {

	class M_Help {
		// The screen we want to access help for
		var $screen = false;

		function __construct( &$screen = false ) {

			$this->screen = $screen;

			$this->set_global_sidebar_content();
			//print_r($screen);

		}

		function M_Help( &$screen = false ) {
			$this->__construct( $screen );
		}

		function show() {



		}

		function attach() {

			switch($this->screen->id) {

				case 'toplevel_page_membership':					$this->dashboard_help();
																	break;

				case 'membership_page_membershipmembers':			$this->members_help();
																	break;

				case 'membership_page_membershiplevels':			$this->levels_help();
																	break;

				case 'membership_page_membershipsubs':				$this->subs_help();
																	break;

				case 'membership_page_membershipgateways':			$this->gateways_help();
																	break;

				case 'membership_page_membershipcommunication':		$this->communication_help();
																	break;

				case 'membership_page_membershipurlgroups':			$this->urlgroups_help();
																	break;

				case 'membership_page_membershippings':				$this->pings_help();
																	break;

				case 'membership_page_membershipoptions':			$this->options_help();
																	break;

				case 'membership_page_membershipaddons':			$this->addons_help();
																	break;

			}

		}

		// Specific help content creation functions

		function set_global_sidebar_content() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.sidebar.php'));
			$help = ob_get_clean();

			$this->screen->set_help_sidebar( $help );
		}

		function dashboard_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.dashboard.php'));
			$help = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'overview',
				'title'   => __( 'Overview' ),
				'content' => $help,
			) );

		}

		function members_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.members.php'));
			$help = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'members',
				'title'   => __( 'Members', 'membership' ),
				'content' => $help,
			) );

		}

		function levels_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.levels.php'));
			$help = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.levelsedit.php'));
			$helpedit = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'levels',
				'title'   => __( 'Levels', 'membership' ),
				'content' => $help,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'edit',
				'title'   => __( 'Adding / Editing', 'membership' ),
				'content' => $helpedit,
			) );



		}

		function subs_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.subs.php'));
			$help = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.subsedit.php'));
			$helpedit = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'subscriptions',
				'title'   => __( 'Subscriptions' , 'membership' ),
				'content' => $help,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'edit',
				'title'   => __( 'Adding / Editing' , 'membership' ),
				'content' => $helpedit,
			) );

		}

		function gateways_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.gateways.php'));
			$help = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'gateways',
				'title'   => __( 'Gateways', 'membership' ),
				'content' => $help,
			) );

		}

		function communication_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.communication.php'));
			$help = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.communicationedit.php'));
			$helpedit = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'communications',
				'title'   => __( 'Communications', 'membership' ),
				'content' => $help,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'edit',
				'title'   => __( 'Adding / Editing', 'membership' ),
				'content' => $helpedit,
			) );

		}

		function urlgroups_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.urlgroups.php'));
			$help = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.urlgroupsedit.php'));
			$helpedit = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'urlgroups',
				'title'   => __( 'URL Groups', 'membership' ),
				'content' => $help,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'edit',
				'title'   => __( 'Adding / Editing', 'membership' ),
				'content' => $helpedit,
			) );

		}

		function pings_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.pings.php'));
			$help = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.pingsedit.php'));
			$helpedit = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.pingshistory.php'));
			$helphistory = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'pings',
				'title'   => __( 'Pings', 'membership' ),
				'content' => $help,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'edit',
				'title'   => __( 'Adding / Editing', 'membership' ),
				'content' => $helpedit,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'history',
				'title'   => __( 'Ping History', 'membership' ),
				'content' => $helphistory,
			) );

		}

		function options_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.options.php'));
			$help = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.optionspages.php'));
			$helppages = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.optionscontent.php'));
			$helpcontent = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.optionsdownloads.php'));
			$helpdownloads = ob_get_clean();

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.optionsextras.php'));
			$helpextras = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'options',
				'title'   => __( 'General Options', 'membership' ),
				'content' => $help,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'pages',
				'title'   => __( 'Membership Pages', 'membership' ),
				'content' => $helppages,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'content',
				'title'   => __( 'Content Protection', 'membership' ),
				'content' => $helpcontent,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'downloads',
				'title'   => __( 'Downloads / Media', 'membership' ),
				'content' => $helpdownloads,
			) );

			$this->screen->add_help_tab( array(
				'id'      => 'extras',
				'title'   => __( 'Extras', 'membership' ),
				'content' => $helpextras,
			) );

		}

		function addons_help() {

			ob_start();
			include_once(membership_dir('membershipincludes/help/help.addons.php'));
			$help = ob_get_clean();

			$this->screen->add_help_tab( array(
				'id'      => 'addons',
				'title'   => __( 'Add-ons', 'membership' ),
				'content' => $help,
			) );

		}

		function advanced_holder() {
			/*
			$help = '<p>' . __( 'Welcome to your WordPress Dashboard! This is the screen you will see when you log in to your site, and gives you access to all the site management features of WordPress. You can get help for any screen by clicking the Help tab in the upper corner.' ) . '</p>';

			get_current_screen()->add_help_tab( array(
				'id'      => 'overview',
				'title'   => __( 'Overview' ),
				'content' => $help,
			) );

			// Help tabs

			$help  = '<p>' . __('The left-hand navigation menu provides links to all of the WordPress administration screens, with submenu items displayed on hover. You can minimize this menu to a narrow icon strip by clicking on the Collapse Menu arrow at the bottom.') . '</p>';
			$help .= '<p>' . __('Links in the Toolbar at the top of the screen connect your dashboard and the front end of your site, and provide access to your profile and helpful WordPress information.') . '</p>';

			get_current_screen()->add_help_tab( array(
				'id'      => 'help-navigation',
				'title'   => __('Navigation'),
				'content' => $help,
			) );

			$help  = '<p>' . __('You can use the following controls to arrange your Dashboard screen to suit your workflow. This is true on most other administration screens as well.') . '</p>';
			$help .= '<p>' . __('<strong>Screen Options</strong> - Use the Screen Options tab to choose which Dashboard boxes to show, and how many columns to display.') . '</p>';
			$help .= '<p>' . __('<strong>Drag and Drop</strong> - To rearrange the boxes, drag and drop by clicking on the title bar of the selected box and releasing when you see a gray dotted-line rectangle appear in the location you want to place the box.') . '</p>';
			$help .= '<p>' . __('<strong>Box Controls</strong> - Click the title bar of the box to expand or collapse it. In addition, some box have configurable content, and will show a &#8220;Configure&#8221; link in the title bar if you hover over it.') . '</p>';

			get_current_screen()->add_help_tab( array(
				'id'      => 'help-layout',
				'title'   => __('Layout'),
				'content' => $help,
			) );

			$help  = '<p>' . __('The boxes on your Dashboard screen are:') . '</p>';
			$help .= '<p>' . __('<strong>Right Now</strong> - Displays a summary of the content on your site and identifies which theme and version of WordPress you are using.') . '</p>';
			$help .= '<p>' . __('<strong>Recent Comments</strong> - Shows the most recent comments on your posts (configurable, up to 30) and allows you to moderate them.') . '</p>';
			$help .= '<p>' . __('<strong>Incoming Links</strong> - Shows links to your site found by Google Blog Search.') . '</p>';
			$help .= '<p>' . __('<strong>QuickPress</strong> - Allows you to create a new post and either publish it or save it as a draft.') . '</p>';
			$help .= '<p>' . __('<strong>Recent Drafts</strong> - Displays links to the 5 most recent draft posts you&#8217;ve started.') . '</p>';
			$help .= '<p>' . __('<strong>WordPress Blog</strong> - Latest news from the official WordPress project.') . '</p>';
			$help .= '<p>' . __('<strong>Other WordPress News</strong> - Shows the <a href="http://planet.wordpress.org" target="_blank">WordPress Planet</a> feed. You can configure it to show a different feed of your choosing.') . '</p>';
			$help .= '<p>' . __('<strong>Plugins</strong> - Features the most popular, newest, and recently updated plugins from the WordPress.org Plugin Directory.') . '</p>';

			get_current_screen()->add_help_tab( array(
				'id'      => 'help-content',
				'title'   => __('Content'),
				'content' => $help,
			) );

			unset( $help );

			get_current_screen()->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.wordpress.org/Dashboard_Screen" target="_blank">Documentation on Dashboard</a>' ) . '</p>' .
				'<p>' . __( '<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>' ) . '</p>'
			);

			*/
		}



	}

}
?>