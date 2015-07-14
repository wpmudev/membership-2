<?php
/**
 * Abstract class for all Views.
 *
 * All views will extend or inherit from the MS_View class.
 * Methods of this class will prepare and output views.
 *
 * @since 1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_View extends MS_Hooker {

	/**
	 * The storage of all data associated with this render.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Flag is set to true while in Simulation mode.
	 *
	 * @since 1.1.0
	 *
	 * @var bool
	 */
	static protected $is_simulating = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The data what has to be associated with this render.
	 */
	public function __construct( $data = array() ) {
		static $Simulate = null;

		$this->data = $data;

		/**
		 * Actions to execute when constructing the parent View.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_View object.
		 */
		do_action( 'ms_view_construct', $this );

		if ( null === $Simulate && MS_Model_Simulate::can_simulate() ) {
			$Simulate = MS_Factory::load( 'MS_Model_Simulate' );
			self::$is_simulating = $Simulate->is_simulating();
		}
	}

	/**
	 * Displays a note while simulation mode is enabled.
	 *
	 * @since  1.1.0
	 */
	protected function check_simulation() {
		if ( self::$is_simulating ) :
		?>
		<div class="error below-h2">
			<p>
				<strong><?php _e( 'You are in Simulation mode!', MS_TEXT_DOMAIN ); ?></strong>
			</p>
			<p>
				<?php _e( 'Content displayed here might be altered because of simulated restrictions.', MS_TEXT_DOMAIN ); ?><br />
				<?php
				printf(
					__( 'We recommend to %sExit Simulation%s before making any changes!', MS_TEXT_DOMAIN ),
					'<a href="' . MS_Controller_Adminbar::get_simulation_exit_url() . '">',
					'</a>'
				);
				?>
			</p>
			<p>
				<em><?php _e( 'This page is only available to Administrators - you can always see it, even during Simulation.', MS_TEXT_DOMAIN ); ?></em>
			</p>
		</div>
		<?php
		endif;
	}

	/**
	 * Displays a warning if network-wide protection is enabled for a large
	 * network.
	 *
	 * @since  2.0.0
	 */
	protected function check_network() {
		if ( MS_Plugin::is_network_wide() && wp_is_large_network() ) :
		?>
			<div class="error below-h2">
			<p>
				<strong><?php _e( 'Warning!', MS_TEXT_DOMAIN ); ?></strong>
			</p>
			<p>
				<?php _e( 'This network has a large number of sites. Some features of network protection might be slow or unavailable.', MS_TEXT_DOMAIN ); ?>
			</p>
			</div>
		<?php
		endif;
	}

	/**
	 * Builds template and return it as string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function to_html() {
		// This function is implemented different in each child class.
		return apply_filters( 'ms_view_to_html', '' );
	}

	/**
	 * Renders the template.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$html = $this->to_html();

		echo apply_filters( 'ms_view_render', $html );
	}
}