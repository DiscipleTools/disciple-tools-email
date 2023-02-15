<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Email_Menu
 */
class Disciple_Tools_Email_Menu {

    public $token = 'disciple_tools_email';
    public $page_title = 'D.T Email';

    private static $_instance = null;

    /**
     * Disciple_Tools_Email_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Email_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_Email_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        $this->page_title = __( 'D.T Email', 'disciple-tools-email' );
    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        $this->page_title = __( 'D.T Email', 'disciple-tools-email' );

        add_submenu_page( 'dt_extensions', $this->page_title, $this->page_title, 'manage_dt', $this->token, [ $this, 'content' ] );
        add_action( 'admin_init', array( $this, 'page_init' ) );

    }
    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'my_option_group', // Option group
            'dt_email_settings', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Email Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'my-setting-admin' // Page
        );

        add_settings_field(
            'email', // ID
            'Email', // Title
            array( $this, 'email_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_section_id' // Section
        );
        add_settings_field(
            'domain', // ID
            'Mailgun Domain', // Title
            array( $this, 'domain_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'key',
            'key',
            array( $this, 'key_callback' ),
            'my-setting-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ){
        return dt_recursive_sanitize_array( $input );
    }

    /**
     * Print the Section text
     */
    public function print_section_info(){
        print 'Enter your settings below:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function email_callback()
    {
        printf(
            '<input type="text" id="email" name="dt_email_settings[email]" value="%s" />',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : ''
        );
    }
    public function domain_callback()
    {
        printf(
            '<input type="text" id="domain" name="dt_email_settings[domain]" value="%s" />',
            isset( $this->options['email'] ) ? esc_attr( $this->options['domain']) : ''
        );
    }
    public function key_callback()
    {
        printf(
            '<input type="text" id="key" name="dt_email_settings[key]" value="%s" />',
            isset( $this->options['key'] ) ? esc_attr( $this->options['key']) : ''
        );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        // Set class property
        $this->options = get_option( 'dt_email_settings' );
        ?>
        <div class="wrap">
            <h1>My Settings</h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );
                do_settings_sections( 'my-setting-admin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
Disciple_Tools_Email_Menu::instance();

/**
 * Class Disciple_Tools_Email_Tab_General
 */
class Disciple_Tools_Email_Tab_General {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Header</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Content
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}


/**
 * Class Disciple_Tools_Email_Tab_Second
 */
class Disciple_Tools_Email_Tab_Second {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Header</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Content
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}

