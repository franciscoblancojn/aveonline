<?php

class ExternalApiLoginPlugin
{
    private static $initiated = false;

    public const SETTINGS_KEY   = "external_api_login_settings";
    public const SETTINGS_GROUP = "externalApiLogin";

    public function __construct($file)
    {
        global $wpdb;

        if (!$this->validate_requirements()) {
            return;
        }

        $this->plugin_name = plugin_basename($file);
    }

    public function init()
    {
        if (self::$initiated) {
            return;
        } else {
            self::$initiated = true;
        }

        add_filter('plugin_action_links_' . $this->plugin_name, array('ExternalApiLoginPlugin', 'set_action_links'));
        // create custom plugin settings menu
        add_action('admin_menu', array('ExternalApiLoginPlugin', 'add_custom_menu'));
        //call register settings function
        add_action('admin_init', array('ExternalApiLoginPlugin', 'register_custom_settings') );
	    // overwrite process lost passowrd
        add_action( 'wp_loaded', array('ExternalApiLoginPlugin', 'process_lost_password' ), 19 );
	    // disable unique email condition
        add_filter('pre_user_email', array('ExternalApiLoginPlugin', 'skip_email_exist'));
    }

    /**
     * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
     * @static
     */
    public static function activate()
    {
    }

    /**
     * Attached to deactivate_{ plugin_basename( __FILES__ ) } by register_deactivation_hook()
     * @static
     */
    public static function deactivate()
    {
        // flush rewrite rules
        flush_rewrite_rules();

    }

    /**
     * Attached to uninstall plugin
     * @static
     */
    public static function uninstall()
    {
    }

    /**
     * Disable unique email condition
     * @static
     */
    public static function skip_email_exist($user_email){
        define( 'WP_IMPORTING', 'SKIP_EMAIL_EXIST' );
        return $user_email;
    }

    /**
     * setup plugin action links
     */
    public static function set_action_links($links)
    {
        $pluginLinks = array();
        // settings link
        $pluginLinks[] = '<a href="' . admin_url('admin.php?page=external-api-login') . '">' . 'Settings' . '</a>';

        return array_merge($pluginLinks, $links);
    }

    /**
     * Add custom menu option to Wordpress settings menu
     *
     * @return void
     */
    public static function add_custom_menu() {

        //create new top-level menu
        add_options_page( 'External API Login', 'External API Login', 'administrator', 'external-api-login', array('ExternalApiLoginPlugin', 'custom_settings_page' ));
    }

    /**
     * Register the settings group and key for this plugin
     *
     * @return void
     */
    public static function register_custom_settings() {
        //register our settings
        register_setting(self::SETTINGS_GROUP, self::SETTINGS_KEY);
        //register_setting(self::SETTINGS_GROUP, "auth_login_api");
    }

    /**
     * Load the settings HTML
     *
     * @return void
     */
    public static function custom_settings_page() {
        include(EXTERNAL_API_LOGIN_DIR . 'src/templates/admin/settings.php');
    }


    /**
     * Display an admin notice
     */
    public static function admin_notice($notice, $type)
    {
        $class = "notice-info";
        if ($type == "error") {
            $class = "notice-error";
        } elseif ($type == "warning") {
            $class = "notice-warning";
        } elseif ($type == "success") {
            $class = "notice-success";
        }

        echo "<div class='notice $class'>" .
            '<p>' . esc_html($notice) . '</p>' .
        '</div>';
    }
    
    public static function validate_requirements()
    {
        if (version_compare(PHP_VERSION, '7.1.0', '<')) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action(
                    'admin_notices',
                    function () {
                        self::admin_notice('External API Login Plugin: plugin fue desarrollado usando PHP 7, algunas funcionalidades podrían fallar en esta versión', 'warning');
                    }
                );
            }
        }

        return true;
    }

    public static function process_lost_password()
    {
        if ( isset( $_POST['wc_reset_password'], $_POST['user_login'] ) ) {
			$nonce_value = wc_get_var( $_REQUEST['woocommerce-lost-password-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'lost_password' ) ) {
				return;
			}

			$success = ExternalApiLoginPlugin::retrieve_password();

			// If successful, redirect to my account with query arg set.
			if ( $success ) {
				wp_safe_redirect( add_query_arg( 'reset-link-sent', 'true', wc_get_account_endpoint_url( 'lost-password' ) ) );
				exit;
			}
		}
    }

    public static function retrieve_password()
    {
        require_once EXTERNAL_API_LOGIN_DIR . 'src/api/smart-fit-api.php';

        $errors = new WP_Error();
        $user_data = false;

        if ( empty( $_POST['user_login'] ) || ! is_string( $_POST['user_login'] ) ) {
            $errors->add( 'empty_username', __( '<strong>Error</strong>: Enter a username or email address.' ) );
        }

        if ( $errors->has_errors() ) {
            return $errors;
        }

        // get settings
        $settings = get_option(ExternalApiLoginPlugin::SETTINGS_KEY);
        $api = new SmartFitApi($settings["token"],$settings["auth"]);
        $resetPassowrd = $api->resetPassword($_POST['user_login'] );

        if ($resetPassowrd["result"] == "ok") {
            return true;
        } else {
            $errors->add('invalidcombo', __('<strong>Error</strong>: There is no account with that username or email address.'));
        }

        return $errors;
    }
}
