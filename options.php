<?php

if (is_admin()) {
    new Simple_Checkout_Settings_Page;
}

class Simple_Checkout_Settings_Page
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Simple Checkout Settings',
            'Simple Checkout Settings',
            'manage_options',
            'simple-checkout-options',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('simple_checkout_options');
        ?>
        <div class="wrap">
            <h2>My Settings</h2>

            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('simple_checkout_group');
                do_settings_sections('simple-checkout-options');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'simple_checkout_group', // Option group
            'simple_checkout_options', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'simple_checkout_section_key', // ID
            'Key options', // Title
            array($this, 'print_section_info'), // Callback
            'simple-checkout-options' // Page
        );

        add_settings_field(
            'private_key', // ID
            'Private Key', // Title
            array($this, 'private_key'), // Callback
            'simple-checkout-options', // Page
            'simple_checkout_section_key' // Section
        );

        add_settings_field(
            'publishable_key',
            'Public Key',
            array($this, 'publishable_key'),
            'simple-checkout-options',
            'simple_checkout_section_key'
        );

        add_settings_section(
            'simple_checkout_section_other', // ID
            'Other Options', // Title
            array($this, 'print_section_other_info'), // Callback
            'simple-checkout-options' // Page
        );

        add_settings_field(
            'url', // ID
            'Global Redirect URL', // Title
            array($this, 'url'), // Callback
            'simple-checkout-options', // Page
            'simple_checkout_section_other' // Section
        );

    }

    public static function getDefaultOptions()
    {
        return array(
            "private_key" => "sk_test_xEw5A11E8jLrvZY2ruaxsi6O",
            "publishable_key" => "pk_test_FohbV4ASrw0Fog0iPPgEQemB",
            "url" => home_url()
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = self::getDefaultOptions();

        if (isset($input['url'])) {
            if (filter_var($input['url'], FILTER_VALIDATE_URL)) {
                $new_input['url'] = filter_var($input['url'], FILTER_SANITIZE_URL);
            }
        }

        if (isset($input['private_key']) && str_replace(" ", "", $input['private_key']) != "") {
            $new_input['private_key'] = $input['private_key'];
        }

        if (isset($input['publishable_key']) && str_replace(" ", "", $input['private_key']) != "") {
            $new_input['publishable_key'] = $input['publishable_key'];
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Save your keys here:';
    }


    /**
     * Print the Section text
     */
    public function print_section_other_info()
    {
        print 'Enter other settings here:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function private_key()
    {
        printf(
            '<input type="text" id="private_key" name="simple_checkout_options[private_key]" value="%s" />',
            isset($this->options['private_key']) ? esc_attr($this->options['private_key']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function publishable_key()
    {
        printf(
            '<input type="text" id="publishable_key" name="simple_checkout_options[publishable_key]" value="%s" />',
            isset($this->options['publishable_key']) ? esc_attr($this->options['publishable_key']) : ''
        );
    }

    public function url()
    {
        printf(
            '<input type="url" id="url" name="simple_checkout_options[url]" value="%s" />',
            isset($this->options['url']) ? esc_attr($this->options['url']) : ''
        );
    }
}