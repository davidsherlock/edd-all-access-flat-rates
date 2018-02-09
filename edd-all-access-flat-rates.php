<?php
/**
 * Plugin Name:     Easy Digital Downloads - All Access Flat Rates
 * Plugin URI:      https://wordpress.org/plugins/edd-all-access-flat-rates
 * Description:     Override the All Access weighted commission calculations and reward vendors with more straight forward flat rate amounts.
 * Version:         1.0.0
 * Author:          Sell Comet
 * Author URI:      https://sellcomet.com
 * Text Domain:     edd-all-access-flat-rates
 *
 * @package         EDD\All_Access_Flat_Rates
 * @author          Sell Comet
 * @copyright       Copyright (c) Sell Comet
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'EDD_All_Access_Flat_Rates' ) ) {

    /**
     * Main EDD_All_Access_Flat_Rates class
     *
     * @since       1.0.0
     */
    class EDD_All_Access_Flat_Rates {

        /**
         * @var         EDD_All_Access_Flat_Rates $instance The one true EDD_All_Access_Flat_Rates
         * @since       1.0.0
         */
        private static $instance;

        /**
        * @var EDD_All_Access_Commissions
        */
        public static $edd_commission_flat_rates;

        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_All_Access_Flat_Rates
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_All_Access_Flat_Rates();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();


                self::$edd_commission_flat_rates = new EDD_All_Access_Commission_Flat_Rates();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_ALL_ACCESS_FLAT_RATES_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_ALL_ACCESS_FLAT_RATES_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_ALL_ACCESS_FLAT_RATES_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {

            // Include EDD All Access Commission Flat Rates Class
            require_once EDD_ALL_ACCESS_FLAT_RATES_DIR . 'includes/classes/class-edd-all-access-commission-flat-rates.php';

            // Include helper functions
            require_once EDD_ALL_ACCESS_FLAT_RATES_DIR . 'includes/functions/functions.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {

            // Register settings
            add_filter( 'eddc_settings', array( $this, 'settings' ), 1 );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_ALL_ACCESS_FLAT_RATES_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_all_access_flat_rates_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-all-access-flat-rates' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-all-access-flat-rates', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-plugin-name/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-plugin-name/ folder
                load_textdomain( 'edd-all-access-flat-rates', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-plugin-name/languages/ folder
                load_textdomain( 'edd-all-access-flat-rates', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-all-access-flat-rates', false, $lang_dir );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'      => 'edd_all_access_flat_rates_header',
                    'name'    => '<strong>' . __( 'Flat Rate Settings', 'edd-all-access-flat-rates' ) . '</strong>',
                    'desc'    => '',
                    'type'    => 'header',
                    'size'    => 'regular',
                ),
                array(
                    'id'      => 'edd_all_access_flat_rates_default_rate',
                    'name'    => __( 'Default rate', 'edd-all-access-flat-rates' ),
                    'desc'    => sprintf( __( 'Enter the default flat rate All Access recipients should recieve. 0.25 = %s', 'edd-all-access-flat-rates' ), edd_currency_filter( edd_format_amount( 0.25 ) ) ),
                    'type'    => 'text',
                    'size'    => 'small',
                )
            );

            return array_merge( $settings, $new_settings );
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_All_Access_Flat_Rates
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_All_Access_Flat_Rates The one true EDD_All_Access_Flat_Rates
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function EDD_All_Access_Flat_Rates_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/classes/class-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_All_Access_Flat_Rates::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_All_Access_Flat_Rates_load' );
