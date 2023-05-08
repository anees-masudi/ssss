<?php

defined('ABSPATH') || exit;

// disable all these front-end customizations if the form isn't enabled
$events = get_option('rc_events', []);
if (!isset($events['show_form'])) {
	return;
}

// add links to new endpoints in the dashboard menu
function rc_woocommerce_account_menu_items($items) {

	$new_endpoints = array(
		"event-signup" => "Event Signup",
	);

	// inserts new endpoints in the second position, under Dashboard
	$items = array_merge(array_slice($items, 0, 1), $new_endpoints, array_slice($items, 1));

	return $items;
}

add_filter('woocommerce_account_menu_items', 'rc_woocommerce_account_menu_items');


// add new WC endpoints to the my account section
function rc_add_new_endpoints() {
	add_rewrite_endpoint('event-signup', EP_PAGES);
}

add_action('init', 'rc_add_new_endpoints', 1);


// get content for event signup page
function rc_woocommerce_account_event_signup_endpoint() {
	include(RC_PATH . '/templates/event-signup.php');
}

add_action('woocommerce_account_event-signup_endpoint', 'rc_woocommerce_account_event_signup_endpoint');


// get content for main dashboard
function rc_woocommerce_account_dashboard() {
	include(RC_PATH . '/templates/my-account.php');
}

add_action('woocommerce_account_dashboard', 'rc_woocommerce_account_dashboard');


// changes page titles for custom endpoints
function rc_add_wc_endpoints_to_title($post_title) {

	if (!is_account_page()) {
		return $post_title;
	}

	global $wp;

	if (isset($wp->query_vars['event-signup'])) {
		$post_title = 'Event Signup';
	}

	return $post_title;
}

add_filter('single_post_title', 'rc_add_wc_endpoints_to_title', 99);
