<?php
/*
Plugin Name: NR Cron Disable
Description: The NR Cron Disable by Reliable Penguin is useful if you have a long running cron that is skewing performance data in New Relic.. 
Version: 1.0
Author: Reliable Penguin
Author URI: http://reliablepenguin.com/
*/

class rpNRDisable {
     /* Plugin settings */
    public $menuName = 'rpnrdisable';
    public $pluginName = 'NR Cron Disable';
    public $version = '1.0';
    public $menuSettingsName = 'rpnrdisable-settings';

    /**
     * Initialize
		 *
		 * @since 1.0
     */
    public function __construct() {
        $this->pluginName = __('NR Cron Disable', 'rpnrdisable');
        $this->pluginPath = WP_CONTENT_DIR . '/plugins/' . plugin_basename(dirname(__FILE__));
        $this->pluginURL = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));


        /* Wordpress Hooks */
        add_action('admin_menu', array(&$this, 'addAdminPages'));
 
				// Enable saving function
				add_action( 'admin_init', array( &$this, 'save' ) );

				// Display Admin notices when saving
				add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
				
				// Display Admin header when saving
				add_action( 'admin_head', array( &$this, 'admin_header' ) );

				add_action( 'wp_ajax_test_crontab', array( &$this, "test_cronjob" ) );

				//Cron related functions.
				add_filter( 'cron_schedules', array( &$this, 'add_custom_intervals')); 

				register_activation_hook( __FILE__, array( &$this, 'cron_activation') );
				add_action( 'cron_hourly_event_hook', array( &$this, 'cron_do_this_hourly') );
				register_deactivation_hook( __FILE__, array( &$this, 'cron_deactivation') );
    }

    /**
     * Add necessary code to the head section for the administration pages.
		 *
		 * @since 1.0
     */
    public function admin_header() {
        if (preg_match('/rpnrdisable/', $_SERVER['REQUEST_URI'])) {
            print "<link rel='stylesheet' href='" . $this->pluginURL . "/style.css' type='text/css' media='all' />";
						wp_enqueue_script( 'rpnrdisable', plugins_url( '/script.js', __FILE__ ), array( 'jquery' ), '5', true );
        }
    }

    /**
     * Custom Cron time settings.
		 *
		 * @since 1.0
     */
		public function add_custom_intervals( $schedules ) {
				// Adds once weekly to the existing schedules.
				$schedules['weekly'] = array(
					'interval' => 604800,
					'display' => __( 'Once Weekly' )
				);
				$schedules['monthly'] = array(
						'interval' => 2635200,
						'display' => __('Once a Month')
				);
				return $schedules;
		}


    /**
     * cron_activation
		 *
		 * @since 1.0
     */
    public function cron_activation() {
				$settings  = get_option( 'rpnrdisable-settings');

				$time = (isset($settings['run_cron_job'])) ? $settings['run_cron_job'] : 'hourly';
				wp_schedule_event( time(), $time, array( &$this, 'cron_do_this') );
    }

    /**
     * cron_deactivation
		 *
		 * @since 1.0
     */
    public function cron_deactivation() {
				$time = (isset($settings['run_cron_job'])) ? $settings['run_cron_job'] : 'hourly';
				$timestamp = wp_next_scheduled( array( &$this, 'cron_do_this') );
				wp_clear_scheduled_hook( $timestamp, $time, array( &$this, 'cron_do_this') );
    }

    /**
     * cron_do_this_hourly
		 *
		 * @since 1.0
     */
    public function cron_do_this() {
			$settings  = get_option( 'rpnrdisable-settings');

			if ((isset($settings['enable_cron_job']) && $settings['enable_cron_job'] == 1) || ( 'test_cronjob' == $_REQUEST['action'] )) {
				/* Newrelic. transactions. */
				if (extension_loaded('newrelic')) {
					newrelic_ignore_transaction (TRUE);
					newrelic_ignore_apdex (TRUE);
				}
			}
			return true;
    }


    /**
     * Add the admin page for the settings panel.
     *
     * @global string $wp_version
		 *
		 * @since 1.0
     */
    public function addAdminPages() {
				// Add a new submenu under Settings:
        add_options_page($this->pluginName, $this->pluginName, 'manage_options', $this->menuSettingsName, array(&$this, 'settingsPanel'));
    }
 

    /**
     * To show the form for set the NR Disable script crontab.
		 *
		 * @since 1.0
     */
    public function settingsPanel() {
			$settings  = get_option( 'rpnrdisable-settings');
			$defaults = array(
				'enable_cron_job'	=> '',
				'run_cron_job'	=> 'hourly'
			);

			foreach ( $defaults as $key => $value ) {
				$settings[ $key ] = isset( $settings[ $key ] ) ? $settings[ $key ] : $value;
			}
?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"><br></div>
				<h2 class="option-rpnrdisable-header"><?php echo $this->pluginName;?> by Reliable Penguin<span><a href="http://reliablepenguin.com" target="_new"></a></span></h2>
				<div class="tool-box">
					<h3 class="title">Settings</h3>
					<p>The NR Disable Script by Reliable Penguin used to run the script in CRONTAB.</p>
	        <form id="rpnrdisable-form" method="post">
						<input name="action" type="hidden" value="save-rpnrdisable-settings" />
						<table cellspacing="0" class="wp-list-table widefat fixed posts importers tblAnalyzer">
							<tbody>
								<tr align="top" class="">
									<td>
										<label for="settings-rpnrdisable-bytes_threshold" class="">
											<?php _e( 'Enable NR Disable Script CRONTAB' , 'rpnrdisable'); ?>
										</label>
									</td>
									<td>
 										<input type="checkbox" class="regular-text" id="settings-rpnrdisable-enable_cron_job" name="settings[enable_cron_job]" value="1" <?php checked( $settings['enable_cron_job'], 1 ); ?> />
									</td>
								</tr>		
								<tr align="top" class="alternate">
									<td>
										<label for="settings-rpnrdisable-bytes_threshold" class="">
											<?php _e( 'Duration of Script Run in CRONTAB' , 'rpnrdisable'); ?>
										</label>
									</td>
									<td>
										<select class="regular-text" id="settings-rpnrdisable-run_cron_job" name="settings[run_cron_job]">
											<option <?php selected( $settings['run_cron_job'], 'monthly' ); ?> value="monthly">Monthly</option>
											<option <?php selected( $settings['run_cron_job'], 'weekly' ); ?> value="weekly">Weekly</option>
											<option <?php selected( $settings['run_cron_job'], 'daily' ); ?> value="daily">Daily</option>
											<option <?php selected( $settings['run_cron_job'], 'hourly' ); ?> value="hourly">Hourly</option>
										</select>
 									</td>
								</tr>		 
							</tbody> 
						</table> 
						<div class="buttonSec">
							<div class="buttonSecLeft"><?php submit_button( __( 'Save', 'rpnrdisable' ), 'primary', 'rpnrdisable-submit' ); ?></div>
							<div class="buttonSecRight"><p class="submit"><a href="javascript:return void;" class="button" id="btn-test-crontab">
								<?php _e( 'Test NR Disable Script', 'rpnrdisable' ); ?><img alt="" src="<?php echo $this->pluginURL. '/wpspin_light.gif' ; ?>" class="waiting spinner" />
							</a></p></div>
						</div>
					</form>
				</div>
				<p>Reliable Penguin provides consulting, development optimization and hosting for WordPress. Visit our website for more information: <a href="http://reliablepenguin.com" target="_new">http://reliablepenguin.com</a></p>
			</div>
<?php
    }

		/**
		 * Save settings into database
		 *
		 * @since 1.0
		 */
		public function save() {
			global $wpdb;

			if ( !isset( $_REQUEST['rpnrdisable-submit'] ) )
				return;
 
			$data = array();
			foreach ( $_POST['settings'] as $key => $val ) {
				$data[ $key ] = esc_html( $val );
			}

			update_option( 'rpnrdisable-settings', $data );
		}

		/**
		 * Display saving messages in admin
		 *
		 * @since 1.0
		 */
		public function admin_notices() {
			if ( !isset( $_REQUEST['action'] ) )
				return;

			switch( $_REQUEST['action'] ) :
				case 'save-rpnrdisable-settings' :
					echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'NR Disable script settings are saved.' , 'rpnrdisable' ) );
				break;
			endswitch;
		}

	/**
	 * To test the CRONTAB script functionality.
	 *
	 * @access public
	 * @return void
	 */
	public function test_cronjob() {
		$data = array();
		$data[ 'enable_cron_job' ] = esc_html( $_REQUEST['enable_job'] );
		$data[ 'run_cron_job' ] = esc_html( $_REQUEST['run_job'] );
		update_option( 'rpnrdisable-settings', $data );

		$response = $this->cron_do_this();

		if ( $response ) {
			echo sprintf( '<div class="test-crontab-response updated" id="message" ><p>%s</p></div>',
				__( 'NR Disable script is successfully runned.', 'rpnrdisable' )	);
			die(0);
		}
		else {
			echo sprintf( '<div id="message" class="test-crontab-response error"><p>%s</p></div>',
				__( 'There is some problem to run the NR Disable script.', 'rpnrdisable' )	);
			die(0);
		}
 	}
}

/* Instantiate the Plugin */
$RPNRDISABLE = new rpNRDisable();