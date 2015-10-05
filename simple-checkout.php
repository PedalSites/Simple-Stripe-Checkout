<?php
/**
 * Plugin Name: Simple Stripe Checkout
 * Description: Simple Stripe Checkout integration into WordPress
 * Version: 0.2-alpha
 * Author: Trenton Maki
 * Author URI: my-website.site
 * Text Domain: simple-checkout
 * Domain Path: /languages
 *
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <http://unlicense.org/>
 */


defined('ABSPATH') or die('No script kiddies please!');

require_once(__DIR__ . "/options.php");


//
//function simple_checkout_load_plugin_textdomain() {
//    load_plugin_textdomain( 'simple-checkout', FALSE, basename( __DIR__) ) . '/languages/' );
//}
//add_action( 'plugins_loaded', 'simple_checkout_load_plugin_textdomain' );


register_activation_hook(__FILE__, "simple_checkout_set_default_options");
function simple_checkout_set_default_options()
{
        add_option("simple_checkout_options", Simple_Checkout_Settings_Page::getDefaultOptions());
}


/**
 * Courtesy of http://www.stumiller.me/sending-output-to-the-wordpress-debug-log/
 */
function simple_checkout_write_log($log)
{
    if (true === WP_DEBUG) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}


add_action('wp_ajax_add_customer', 'add_customer');
function add_customer()
{
    if (empty($_POST) || !wp_verify_nonce($_POST['add_customer_nonce'], 'add_customer')) {
        echo __('You targeted the right function, but sorry, your nonce did not verify.', "simple-checkout");
        die();
    } else {
        require_once(__DIR__ . "/stripe-php-3.4.0/init.php");
        $options = get_option('simple_checkout_options');

        try {
            Stripe\Stripe::setApiKey($options["private_key"]);

            $token = $_POST['stripeToken'];

            Stripe\Customer::create(array(
                "source" => $token,
                "description" => __("Support Customer", "simple-checkout")
            ));
        } catch (Exception $e) {
            simple_checkout_write_log($e);
        }

        wp_redirect($options["url"]);
    }
}

add_shortcode('stripe_checkout', 'get_stripe_checkout');
function get_stripe_checkout($atts, $content = null)
{
    $options = get_option('simple_checkout_options');
    $processedAtts = shortcode_atts(array(
        "data_key" => $options["publishable_key"],
        "data_image" => "",
        "data_name" => "",
        "data_description" => "",
        "data_amount" => "",
        "data_currency" => "",
        "data_panel-label" => "",
        "data_zip-code" => "",
        "data_billing-address" => "",
        "data_shipping-address" => "",
        "data_email" => "",
        "data_label" => "",
        "data_allow-remember-me" => "",
        "data_bitcoin" => "",
        "data_alipay" => "",
        "data_alipay-reusable" => "",
        "data_locale" => "",
        "class" => "stripe-button",
        "form_class" => ""
    ), $atts, "stripe_checkout");
    ob_start();
    ?>
    <form role="form" action="<?php echo admin_url("admin-ajax.php") ?>" method="POST"
          class="<?php echo sanitize_html_class(esc_attr($processedAtts['form_class'])) ?>">
        <?php if (!is_null($content)) {
            echo do_shortcode($content);
        } ?>
        <?php wp_nonce_field('add_customer', 'add_customer_nonce'); ?>
        <input name="action" value="add_customer" type="hidden">
        <script
            src="https://checkout.stripe.com/checkout.js"
            <?php
            foreach ($processedAtts as $key => $val) {
                if ("" === $val) {
                    continue;
                }
                echo esc_attr(str_replace("_", "-", $key)) . '="' . esc_attr($val) . '" ';
            }
            ?>
            >
        </script>
    </form>
    <?php
    $result = ob_get_clean();
    return $result;
}
