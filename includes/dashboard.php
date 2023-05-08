<?php

defined( 'ABSPATH' ) || exit;

include_once( RC_PATH . '/includes/admin-pages/registrations.php' );
include_once( RC_PATH . '/includes/admin-pages/waitlist.php' );
include_once( RC_PATH . '/includes/admin-pages/settings.php' );
include_once( RC_PATH . '/includes/admin-pages/reports.php' );
// gl start
include_once( RC_PATH . '/includes/admin-pages/dynamic_shortcodes.php' );
// gl end
/*
 * Add the menu page and its child submenu pages
 */
function rc_registrations_admin_menu() {
	add_menu_page(
		'Runner\'s Camp',
		'Runner\'s Camp',
		'manage_options',
		'rc_registrations',
		'',
		'dashicons-admin-generic',
		58
	);
	add_submenu_page(
		'rc_registrations',
		'Runner\'s Camp Registrations',
		'Registrations',
		'manage_options',
		'rc_registrations',
		'rc_registrations_page'
	);
	add_submenu_page(
		'rc_registrations',
		'Runner\'s Camp Waitlist',
		'Waitlist',
		'manage_options',
		'rc_waitlist',
		'rc_waitlist_page'
	);
	add_submenu_page(
		'rc_registrations',
		'Runner\'s Camp Reports',
		'Reports',
		'manage_options',
		'rc_reports',
		'rc_reports_page'
	);
	add_submenu_page(
		'rc_registrations',
		'Runner\'s Camp Settings',
		'Settings',
		'manage_options',
		'rc_settings',
		'rc_settings_page'
	);
	if ( $product_id = (int) get_option( 'rc_product_id' ) ) {
		add_submenu_page(
			'rc_registrations',
			'',
			'Camp Setup',
			'manage_options',
			'post.php?post=' . $product_id . '&action=edit'
		);
	}
	// gl start
add_submenu_page(
	'rc_registrations',
	'Runner\'s Camp Shortcodes',
	'Shortcodes',
	'manage_options',
	'rc_shortcodes',
	'rc_shortcodes_page'
);

// gl end

}

add_action( 'admin_menu', 'rc_registrations_admin_menu' );
