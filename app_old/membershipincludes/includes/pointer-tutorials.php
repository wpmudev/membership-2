<?php
/*
Pointer Tutorials Module
By Aaron Edwards (Incsub)
http://uglyrobot.com/

Copyright 2011-2012 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

----- How to Use ------
It is best to call this in the admin_init action hook. Here is an example:

	//show the tutorial
	add_action( 'admin_init', 'tutorial' );
	
	function tutorial() {
		//load the file
		require_once( dirname(__FILE__) . '/includes/pointer-tutorials.php' );
		
		//create our tutorial, with default redirect prefs
		$tutorial = new Pointer_Tutorial(__('My Tutorial', 'mytextdomain'), 'my_tutorial', true, false);
		
		//add our textdomain that matches the current plugin
		$tutorial->set_textdomain = 'mytextdomain';
		
		//add the capability a user must have to view the tutorial
		$tutorial->set_capability = 'manage_options';
		
		//optionally add some custom css. This example give our title a red background and loads up our modified pointer image sprite to the up arrow will be red too
		$tutorial->add_style('.my_tutorial-pointer .wp-pointer-content h3 {	background-color: #b12c15; }
													.my_tutorial-pointer .wp-pointer-arrow { background-image: url("'.plugins_url( 'includes/images/arrow-pointer-red.png' , __FILE__ ).'"); }');
		
		//optional shortcut to add a custom icon, just pass a url
		$tutorial->add_icon( plugins_url( 'includes/images/my-logo-white.png' , __FILE__ ) );
		
		//start registering steps. Note the 'content' argument is very important, and should be escaped with esc_js() as it will go in JSON
		$tutorial->add_step(admin_url('index.php'), 'index.php', '#wpmudev_widget', __('Step Number One', 'mytextdomain'), array(
				'content'  => '<p>' . esc_js( __('On each category page, plugins and themes are listed in an easy to read grid format.', 'mytextdomain') ) . '</p>',
				'position' => array( 'edge' => 'bottom', 'align' => 'left' ),
			));
		$tutorial->add_step(admin_url('index.php'), 'index.php', '#toplevel_page_wpmudev', __('Step Number Two', 'mytextdomain'), array(
				'content'  => '<p>' . esc_js( __('On each category page, plugins and themes are listed in an easy to read grid format.', 'mytextdomain') ) . '</p>',
				'position' => array( 'edge' => 'top', 'align' => 'right' ),
			));
		$tutorial->add_step(admin_url('index.php'), 'index.php', '#wdv-release-install', __('Step Number Three', 'mytextdomain'), array(
				'content'  => '<p>' . esc_js( __('On each category page, plugins and themes are listed in an easy to read grid format.', 'mytextdomain') ) . '</p>',
				'position' => array( 'edge' => 'left', 'align' => 'top' ),
			));
		
		//second page steps
		$tutorial->add_step(admin_url('admin.php?page=my-plugin'), 'toplevel_page_wpmudev', '.nav-tab-wrapper', __('Step Number Four', 'mytextdomain'), array(
				'content'  => '<p>' . esc_js( __('On each category page, plugins and themes are listed in an easy to read grid format.', 'mytextdomain') ) . '</p>',
				'position' => array( 'edge' => 'top', 'align' => 'center' ),
			));
		$tutorial->add_step(admin_url('admin.php?page=my-plugin'), 'toplevel_page_wpmudev', '.wdv-grid-wrap .themepost:not(.installed):first', __('Step Number Five', 'mytextdomain'), array(
				'content'  => '<p>' . esc_js( __('On each category page, plugins and themes are listed in an easy to read grid format.', 'mytextdomain') ) . '</p>',
				'position' => array( 'edge' => 'left', 'align' => 'center' ),
			));
		$tutorial->add_step(admin_url('admin.php?page=my-plugin'), 'toplevel_page_wpmudev', '.wdv-grid-wrap .themepost:not(.installed):first .themescreens .metainfo a', __('Step Number Six', 'mytextdomain'), array(
				'content'  => '<p>' . esc_js( __('On each category page, plugins and themes are listed in an easy to read grid format.', 'mytextdomain') ) . '</p>',
				'position' => array( 'edge' => 'top', 'align' => 'left' ),
			));
		
		//start the tutorial
		$tutorial->initialize();
		
		You may want to later show a link to restart the tutorial, or start at a certain step. You can grab a link for that via start_link($step). 
		$step = 0; //Note that steps start at 0, then 1,2,3 etc.
		$link = $tutorial->start_link($step);
	}

Have fun!
*/

if ( !class_exists( 'Pointer_Tutorial' ) ) {
	
	/*
	* class Pointer_Tutorial
	*
	* @author Aaron Edwards (Incsub)
	* @version 1.0
	* @requires WP 3.3
	*
	*	@param string $tutorial_name Required: The name of this tutorial. Used for user settings and css classes.
	*	@param bool $redirect_first_load Optional: Set to true to redirect and show first step for those who have not completed the tutorial. Default true
	*	@param bool $force_completion Optional: Set to true to redirect and show the current step for those who have not completed the tutorial. Basically forces the tutorial to be completed or dismissed. Default false.
	*/
	class Pointer_Tutorial {
		
		private $registered_pointers = array();
		private $page_pointers = array();
		private $tutorial_name = '';
		private $tutorial_key = '';
		private $admin_css = '';
		private $textdomain = 'pointers';
		private $capability = 'manage_options';
		
		//these are public in case you need to change them directly after registering the tutorial
		public $redirect_first_load = true;
		public $force_completion = false;
		public $hide_dismiss = false; //hides the dismiss tutorial link
		public $hide_step = false; //hides the current step label
		
		/*
		 * function __construct
		 *
		 *	Create your tutorial using this method. 
		 * 
		 *	@param string $tutorial_key Required: The key of this tutorial. Used for user settings and css classes. Should not be changed.
		 *	@param string $tutorial_name Required: The nice name of this tutorial. Should be i18n.
		 *	@param bool $redirect_first_load Optional: Set to true to redirect and show first step for those who have not completed the tutorial. Default true
		 *	@param bool $force_completion Optional: Set to true to redirect and show the current step for those who have not completed the tutorial. Basically forces the tutorial to be completed or dismissed. Default false.
		 */
		function __construct( $tutorial_key, $tutorial_name = '', $redirect_first_load = true, $force_completion = false ) {
			global $wp_version;
			
			//requires WP 3.3
			if ( version_compare($wp_version, '3.3-beta4', '<') )
				return false;
			
			$this->tutorial_key = sanitize_key( $tutorial_key );
			$this->tutorial_name = empty($tutorial_name) ? __('Tutorial', $this->textdomain) : trim($tutorial_name);
			$this->redirect_first_load = $redirect_first_load;
			$this->force_completion = $force_completion;
		}
		
		/*
		 * function add_step
		 *
		 *	Register your individual steps using this method. 
		 * 
		 *	@param string $url Required: The admin url of the step. Can be just index.php, but better to pass a full url from admin_url() or network_admin_url() functions.
		 *	@param string $hook Required: This is the wordpress hook suffix for the page. This is returned by add_menu_page() or can be nabbed from the $hook_suffix global
		 *	@param string $selector Required: The jQuery selector to attach the pointer to. It should only select one DOM element.
		 *	@param string $title Optional: The title of the pointer. Leave empty to add no title/icon. No HTML allowed.
		 *	@param array|string $args Required: The javascript arguments for the pointer jQuery plugin. content, position, pointerClass, pointerWidth, etc.
		 */
		public function add_step( $url, $hook, $selector, $title, $args ) {
			
			//add title if given
			if ( !empty($title) )
				$args['content'] = '<h3>' . esc_js($title) . '</h3>' . $args['content'];
			
			//if urls are incomplete calculate them
			if ( strpos( $url, '://' ) === false )
				$url = is_network_admin() ? network_admin_url($url) : admin_url($url);
			
			//register the pointer	
			$this->registered_pointers[] = array( 'url' => $url, 'hook' => $hook, 'selector' => $selector, 'title' => $title, 'args' => $args );
		}
		
		/*
		 * function set_capability
		 *
		 *	Customizes the capability the user requires to view this tutorial.
		 * 
		 *	@param string $capability the wordpress capability. Defaults to manage_options
		 */
		public function set_capability( $capability ) {
			$this->capability = trim( $capability );
		}
		
		/*
		 * function set_textdomain
		 *
		 *	Customizes the textdomain for translating buttons and such.
		 * 
		 *	@param string $domain the textdomain for i18n
		 */
		public function set_textdomain( $domain ) {
			$this->textdomain = trim( $domain );
		}
		
		/*
		 * function add_style
		 *
		 *	A shortcut to customize the css for the entire tutorial. Use this to change colors, fonts, etc.
		 * 
		 *	@param string $css the css selectors and attributes that will be printed inside <style> tags
		 */
		public function add_style( $css ) {
			$this->admin_css .= "\n" . trim($css);
		}
		
		/*
		 * function add_icon
		 *
		 *	A shortcut to override the title with a custom icon of your choosing for the entire tutorial.
		 *	If you need to customize the icons for individual steps use add_style.
		 * 
		 *	@param string $url Url to the icon image file. Should be 32x32 normally
		 */
		public function add_icon( $url ) {
			//$this->add_style( '.wpmudev_dashboard-pointer .wp-pointer-content h3:before { background-image: url("' . $url . '"); }' );
			
			// Changed this to allow for icons outside dashboard.
			// Also, !important is needed because of the removed selector specificity
			$this->add_style( '.wp-pointer-content h3:before { background-image: url("' . $url . '") !important; }' );
		}
		
		/*
		 * function initialize
		 *
		 *	Call after setting up the tutorial to initialize it and make it active
		 */
		public function initialize() {
			
			if ( !current_user_can($this->capability) )
				return false;
			
			$this->catch_tutorial_start(); //load start listener
			
			$current_step = get_user_meta( get_current_user_id(), "current-{$this->tutorial_key}-step", true );
			
			// entire tutorial has been dismissed
			if ( intval($current_step) >= count($this->registered_pointers) )
				return;
			
			if ( is_admin() && !defined('DOING_AJAX') ) {
				//if first load redirect is true and on first step force us there
				if ( $this->redirect_first_load && $current_step == '' && strpos( $this->registered_pointers[0]['url'], $_SERVER['REQUEST_URI'] ) === false ) {
					update_user_meta( get_current_user_id(), "current-{$this->tutorial_key}-step", 0 ); //set to 0 so that it won't redirect again
					wp_redirect( $this->registered_pointers[0]['url'] );
					exit;
				}
				
				//if force_completion is true and on first step force us there
				$current_step = intval($current_step);
				if ( $this->force_completion && strpos( $this->registered_pointers[$current_step]['url'], $_SERVER['REQUEST_URI'] ) === false ) {
					wp_redirect( $this->registered_pointers[$current_step]['url'] );
					exit;
				}
			}
			
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( "wp_ajax_dismiss-{$this->tutorial_key}-pointer", array( &$this, 'ajax_dismiss' ) );
		}
		
		/*
		 * function start_link
		 *
		 *	Returns a url that can be linked to that when clicked starts the tutorial at a given step.
		 *	Must be called after steps are registered.
		 * 
		 *	@param int $step What step to link to start at. Defaults to first step.
		 *	@return string|bool Url to put in a link or false if they don't have that capability.
		 */
		public function start_link($step = 0) {
			if ( !current_user_can($this->capability) )
				return false;
			
			return add_query_arg( array($this->tutorial_key.'-start' => $step), $this->registered_pointers[$step]['url'] );
		}
		
		/*
		 * function restart	
		 *
		 * Restarts the tutorial at the given step with a redirect if neccessary.
		 * Must be called before headers are sent and after steps are registered.
		 *
		 * 	@param int $step What step to link to start at. Defaults to first step.
		 */
		public function restart($step = 0) {
			update_user_meta( get_current_user_id(), "current-{$this->tutorial_key}-step", $step );
			$this->force_completion = true; //set temporarily so it will redirect if necessary
		}
		
		
		
		
		/* ---------------- Private Internal Methods ---------------- */
		/* ---------------------------------------------------------- */
		
		/**
		 * Initializes the new feature pointers.
		 *
		 */
		function enqueue_scripts( $hook_suffix ) {
                        global $post;
                        
			// Get current step
			$current_step = (int) get_user_meta( get_current_user_id(), "current-{$this->tutorial_key}-step", true );
			
			//get first step for the current page
			$first_step = $current_step;
			$i = $current_step;
			while ($i >= 0) {
				if ( $this->registered_pointers[$i]['hook'] != $hook_suffix ) {
					break; //drop out
				} else {
					$first_step = $i;
				}
				$i--;
			}
			
			//get last step for the current page
			$last_step = $current_step;
			$i = $current_step;
			while ($i < count($this->registered_pointers)) {
				if ( $this->registered_pointers[$i]['hook'] != $hook_suffix ) {
					break; //drop out
				} else {
					$last_step = $i;
				}
				$i++;
			}
			
			//get the slice of current page pointers
			$this->page_pointers = array_slice( $this->registered_pointers, $first_step, ($last_step - $first_step) + 1, true );
                        
                        foreach ($this->page_pointers as $k => $pointer) {
                            if ( isset($post) && $post && isset($post->post_type) && isset($pointer['args']) &&
                                 isset($pointer['args']['post_type']) && $pointer['args']['post_type'] != $post->post_type ) {
                                unset($this->page_pointers[$k]);
                            }
                        }
                        
			//skip if no page pointers for this page
			if ( !count($this->page_pointers) )
				return;
			
                        $current_page = array_slice($this->page_pointers, 0);
                        
			//add any custom css
			add_action( 'admin_print_styles-'.$current_page[0]['hook'], array(&$this, 'admin_styles') );
			
			// Bind pointer print function
			add_action( 'admin_footer-'.$current_page[0]['hook'], array( &$this, 'print_footer_list' ) );
	
			// Add pointers script and style to queue
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
		}
		
		/**
		 * Prints the admin css.
		 *
		 */
		function admin_styles() {
			if ( !empty( $this->admin_css ) ) {
				echo '<style type="text/css">
.wp-pointer-buttons a.prev { float: left; }
.wp-pointer-buttons a.dismiss {	color: #FFFFFF; font-size: 10px; position: absolute; right: 3px; top: 1px; }
.wp-pointer-buttons span.tut-step {	font-size: 9px; font-size: 9px; left: 0; bottom: -3px; position: absolute; text-align: center; width: 100%; }';
				echo $this->admin_css;
				echo "\n</style>\n";
			}
		}
		
		/**
		 * Handles the AJAX step complete callback.
		 *
		 */
		function ajax_dismiss() {
			if ( !is_numeric($_POST['pointer']) )
				die( '0' );
			
			if ('next' == $_POST['step']) {				
				$pointer = intval($_POST['pointer']) + 1;
			} else if ('prev' == $_POST['step']) {
				$pointer = intval($_POST['pointer']) - 1;
			} else if (!$this->hide_dismiss) {
				$pointer = count($this->registered_pointers);	//dismissing tutorial, so set to last step		
			} else {
				die( '0' );
			}
		
			update_user_meta( get_current_user_id(), "current-{$this->tutorial_key}-step", $pointer );
			die( '1' );
		}
		
		/**
		 * Listens for clicks to start/restart a tutorial, or jumping to a step.
		 *
		 */
		function catch_tutorial_start() {
			if ( is_admin() && isset($_GET[$this->tutorial_key.'-start']) )
				$this->restart( intval($_GET[$this->tutorial_key.'-start']) );
		}
		
		/**
		 * Print the pointer javascript data in the footer.
		 */
		function print_footer_list() {
			// Get current step
			$current_step = (int) get_user_meta( get_current_user_id(), "current-{$this->tutorial_key}-step", true );
			?>
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
			<?php
			$count = 0;
			foreach ( $this->page_pointers as $pointer_id => $settings) {
				$count++;
				
				extract( $settings );
				
				//add our tutorial class for styling
				if (empty($args['pointerClass']))
					$args['pointerClass'] = $this->tutorial_key . '-pointer';
				else
					$args['pointerClass'] .= ' ' . $this->tutorial_key . '-pointer';
				
				//add our buttons
				
				//get next link thats on a different page
				$next_link = '';
				$next_pointer = '';
				$next_name = __('Next &raquo;', $this->textdomain);
				$last_step = false;
				if ( $count >= count($this->page_pointers) && isset($this->registered_pointers[$pointer_id+1]) ) {
					$next_url = $this->registered_pointers[$pointer_id+1]['url'];
					$next_link = ", function() { window.location = '$next_url'; }";
					$next_title = $this->registered_pointers[$pointer_id+1]['title'];
				} else if ( isset($this->page_pointers[$pointer_id+1]) ) {
					$next_pointer = $this->page_pointers[$pointer_id+1]['selector'];
					$next_pointer_id = $pointer_id + 1;
					// Added scrolling to next pointer.
					// This will also fix positioning/orientation bugs that happen in some cases
					// when the pointer is close to being off-screen.
					$next_pointer = "$(window).scrollTop($('$next_pointer').offset().top-300); $('$next_pointer').pointer( options$next_pointer_id ).pointer('open').focus();";
					$next_title = $this->page_pointers[$pointer_id+1]['title'];
				} else {
					$next_name = __('Dismiss', $this->textdomain);
					$next_title = sprintf(__('Dismiss %s', $this->textdomain), $this->tutorial_name);
					$last_step = true;
				}
				
				$prev_link = '';
				$prev_pointer = '';
				$prev_name = __('&laquo; Previous', $this->textdomain);
				if ( $count == 1 && isset($this->registered_pointers[$pointer_id-1]) ) { //if first step for the page and theres a previous page
					$prev_url = $this->registered_pointers[$pointer_id-1]['url'];
					$prev_link = ", function() { window.location = '$prev_url'; }";
					$prev_title = $this->registered_pointers[$pointer_id-1]['title'];
				} else if ( isset($this->page_pointers[$pointer_id-1]) ) {
					$prev_pointer = $this->page_pointers[$pointer_id-1]['selector'];
					$prev_pointer_id = $pointer_id - 1;
					// Added scrolling to previous pointer.
					// This will also fix positioning/orientation bugs that happen in some cases
					// when the pointer is close to being off-screen.
					$prev_pointer = "$(window).scrollTop($('$prev_pointer').offset().top-300); $('$prev_pointer').pointer( options$prev_pointer_id ).pointer('open').focus();";
					$prev_title = $this->page_pointers[$pointer_id-1]['title'];
				}
				
				$close_name = __('Dismiss', $this->textdomain);
				$close_title = sprintf(__('Dismiss %s', $this->textdomain), $this->tutorial_name);
				?>
				//step <?php echo $pointer_id; ?> pointer<?php if ($pointer_id == $current_step) { ?> (Current)<?php } ?>				
				var options<?php echo $pointer_id; ?> = <?php echo json_encode( $args ); ?>;
	
				options<?php echo $pointer_id; ?> = $.extend( options<?php echo $pointer_id; ?>, {
					next: function() {
						$.post( ajaxurl, {
							pointer: '<?php echo $pointer_id; ?>',
							step: 'next',
							action: 'dismiss-<?php echo $this->tutorial_key; ?>-pointer'
						}<?php echo $next_link; ?>);
						<?php echo $next_pointer; ?>
					},
					prev: function() {
						$.post( ajaxurl, {
							pointer: '<?php echo $pointer_id; ?>',
							step: 'prev',
							action: 'dismiss-<?php echo $this->tutorial_key; ?>-pointer'
						}<?php echo $prev_link; ?>);
						<?php echo $prev_pointer; ?>
					},
					close: function() {
						$.post( ajaxurl, {
							pointer: '<?php echo $pointer_id; ?>',
							step: 'close',
							action: 'dismiss-<?php echo $this->tutorial_key; ?>-pointer'
						});
					},
					buttons: function( event, t ) {
						var $buttons = $(
							'<div>' +
							<?php if ($pointer_id > 0) { ?>
							'<a class="prev button" href="#" title="<?php echo esc_attr($prev_title); ?>"><?php echo $prev_name; ?></a> ' +
							<?php } ?>
							<?php if (!$last_step && !$this->hide_dismiss) { ?>
							'<a class="dismiss" href="#" title="<?php echo esc_attr($close_title); ?>"><?php echo $close_name; ?></a> ' +
							<?php } ?>
							<?php if (!$this->hide_step) { ?>
							'<span class="tut-step"><?php printf( __('%s: Step %d of %d', $this->textdomain), $this->tutorial_name, $pointer_id+1, count($this->registered_pointers) ); ?></span>' +
							<?php } ?>
							'<a class="next button" href="#" title="<?php echo esc_attr($next_title); ?>"><?php echo $next_name; ?></a>' +
							'</div>'
						);
						$buttons.find('.next').bind( 'click.pointer', function() {
							t.element.pointer('destroy');
							options<?php echo $pointer_id; ?>.next();
							return false;
						});
						<?php if (!$this->hide_dismiss) { ?>
						$buttons.find('.dismiss').bind( 'click.pointer', function() {
							t.element.pointer('destroy');
							options<?php echo $pointer_id; ?>.close();
							return false;
						});
						<?php } ?>
						<?php if ($pointer_id > 0) { ?>
						$buttons.find('.prev').bind( 'click.pointer', function() {
							t.element.pointer('destroy');
							options<?php echo $pointer_id; ?>.prev();
							return false;
						});
						<?php } ?>
						return $buttons;
					}
				});
				<?php if ($pointer_id == $current_step) { ?>
				$('<?php echo $selector; ?>').pointer( options<?php echo $pointer_id; ?> ).pointer('open');
				<?php
				}
			}
			
			?>
			});
			//]]>
			</script>
			<?php
		}
	
	}
}
?>