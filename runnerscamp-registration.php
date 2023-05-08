<?php
/*
Plugin Name: Runner's Camp Registration
Version:     1.0.12
Plugin URI:  https://rosieleung.com/
Description: Customizes WooCommerce to sell a "2020 Camp Registration" product. (Do not activate this plugin if other products are being sold.)
Author:      Rosie Leung
Author URI:  mailto:rosie@rosieleung.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'RC_PATH', dirname( __FILE__ ) );
define( 'RC_VERSION', '1.0.12' );

function rc_init_plugin() {

	if ( ! class_exists( 'woocommerce' ) ) {
		add_action( 'admin_notices', 'rc_warn_no_wc' );

		return;
	}
	include_once( RC_PATH . '/includes/enqueue.php' );
	include_once( RC_PATH . '/includes/dashboard.php' );
	include_once( RC_PATH . '/includes/discounts.php' );
	include_once( RC_PATH . '/includes/wc-my-account.php' );
	include_once( RC_PATH . '/includes/hooks-cart.php' );
	include_once( RC_PATH . '/includes/hooks-ajax.php' );
	include_once( RC_PATH . '/includes/hooks-checkout.php' );
	include_once( RC_PATH . '/includes/hooks-misc.php' );
	include_once( RC_PATH . '/includes/hooks-template.php' );
}

add_action( 'plugins_loaded', 'rc_init_plugin' );

// Display WC required warning on admin if WC is not activated
function rc_warn_no_wc() {
	?>
	<div class="error">
		<p>
			<strong>Runner's Camp Registration:</strong> This plugin requires WooCommerce in order to operate. Please install and activate WooCommerce, or disable this plugin.
		</p>
	</div>
	<?php
}
