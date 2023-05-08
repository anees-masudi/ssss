<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function rc_update_stock_ajax() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		wp_die( "Invalid security token." );
	}
	
	check_admin_referer( 'rc_update_product_stock', '_ajax_nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( "Invalid permissions." );
	}
	
	$product_id = (int) $_POST['product_id'];
	if ( ! $product_id ) {
		wp_die( "Invalid product ID." );
	}
	
	$stock_quantity = (int) $_POST['stock_quantity'];
	
	wc_update_product_stock( $product_id, $stock_quantity );
	
	wp_die();
}

add_action( 'wp_ajax_rc_update_product_stock', 'rc_update_stock_ajax' );


function rc_save_camper_group() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		wp_die( "Invalid security token." );
	}
	
	check_admin_referer( 'rc_save_camper_group', '_ajax_nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( "Invalid permissions." );
	}
	
	$order_item_id = (int) $_POST['order_item_id'];
	if ( ! $order_item_id ) {
		wp_die( "Invalid order item ID." );
	}
	
	global $wpdb;
	$order_exists = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
			$order_item_id
		)
	);
	
	if ( ! $order_exists ) {
		wp_die( "Invalid order item ID." );
	}
	
	$group = trim(sanitize_text_field( $_POST['group'] ));
	
	if ( update_metadata( 'order_item', $order_item_id, 'rc_group', wp_slash( $group ), '' ) ) {
		WC_Cache_Helper::incr_cache_prefix( 'object_' . $order_item_id ); // Invalidate cache.
	}
	
	wp_die();
}

add_action( 'wp_ajax_rc_save_camper_group', 'rc_save_camper_group' );