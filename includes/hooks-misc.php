<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// allow editing of all orders regardless of status (i.e. so admin can edit camper metadata)
add_filter( 'wc_order_is_editable', '__return_true' );

// do not automatically email waitlisted customers when products come back in stock
add_filter( 'wcwl_automatic_mailouts_are_disabled', '__return_true' );

// optionally redirect the woocommerce shop page to the single product page
function rc_redirect_shop_to_product() {
	
	if ( get_option( 'rc_redirect_shop' ) !== 'yes' ) {
		return;
	}
	
	$shop = (int) get_option( 'woocommerce_shop_page_id' );
	if ( get_the_ID() == $shop ) {
		$product = get_option( 'rc_product_id' );
		exit( wp_redirect( get_permalink( $product ) ) );
	}
}

add_action( 'template_redirect', 'rc_redirect_shop_to_product' );

// replace woocommerce's add-to-cart-variation.js with ours
function rc_woocommerce_get_asset_url( $url ) {
	if ( strpos( $url, "assets/js/frontend/add-to-cart-variation" ) !== false ) {
		$url = RC_URL . '/assets/add-to-cart-variation.js';
	}
	
	return $url;
}

add_filter( "woocommerce_get_asset_url", "rc_woocommerce_get_asset_url" );


// auto-completes virtual orders after payment is received
// see https://www.skyverge.com/blog/how-to-set-woocommerce-virtual-order-status-to-complete-after-payment/
function rc_virtual_order_payment_complete_order_status( $order_status, $order_id ) {
	
	$order = wc_get_order( $order_id );
	
	// only modify orders that are being changed to 'processing', which indicates they are not a downloadable-virtual order
	if ( $order && 'processing' === $order_status && in_array( $order->get_status(), [ 'on-hold', 'pending', 'failed' ], true ) ) {
		
		$virtual_order = false;
		$order_items   = $order->get_items();
		
		if ( count( $order_items ) > 0 ) {
			foreach ( $order_items as $item ) {
				
				if ( is_callable( [ $item, 'get_product' ] ) ) {
					$product = $item->get_product();
				} elseif ( is_callable( [ $order, 'get_product_from_item' ] ) ) {
					$product = $order->get_product_from_item( $item );
				} else {
					$product = null;
				}
				
				// this means a product was deleted and it doesn't exist; break to ensure the admin has to review this order
				if ( ! $product || ! is_callable( [ $product, 'is_virtual' ] ) ) {
					$order->add_order_note( 'Order auto-completion skipped: deleted or non-existent product found.' );
					$virtual_order = false;
					break;
				}
				
				// once we've found one non-virtual product we know we're done, break out of the loop
				if ( ! $product->is_virtual() ) {
					$virtual_order = false;
					break;
				}
				$virtual_order = true;
			}
		}
		
		// virtual order, mark as completed
		if ( $virtual_order ) {
			$order_status = 'completed';
		}
	}
	
	return $order_status;
}

add_filter( 'woocommerce_payment_complete_order_status', 'rc_virtual_order_payment_complete_order_status', 10, 2 );


// Adds required fields for first/last name and phone number to the new user registration form

// register fields
function rc_woocommerce_register_form_start() { ?>
	<p class="form-row form-row-first">
		<label for="reg_billing_first_name">First name <span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) {
			esc_attr_e( $_POST['billing_first_name'] );
		} ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="reg_billing_last_name">Last name <span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) {
			esc_attr_e( $_POST['billing_last_name'] );
		} ?>" />
	</p>
	<p class="form-row form-row-wide">
		<label for="reg_billing_phone">Phone <span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) {
			esc_attr_e( $_POST['billing_phone'] );
		} ?>" />
	</p>
	<div class="clear"></div>
	<?php
}

add_action( 'woocommerce_register_form_start', 'rc_woocommerce_register_form_start' );


// validate fields
function rc_woocommerce_register_post( $username, $email, $validation_errors ) {
	if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
		$validation_errors->add( 'billing_first_name_error', 'First name is required!' );
	}
	if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
		$validation_errors->add( 'billing_last_name_error', 'Last name is required!' );
	}
	if ( isset( $_POST['billing_phone'] ) && empty( $_POST['billing_phone'] ) ) {
		$validation_errors->add( 'billing_phone_error', 'Phone number is required!' );
	}
	
	return $validation_errors;
}

add_action( 'woocommerce_register_post', 'rc_woocommerce_register_post', 10, 3 );


// save fields

function rc_woocommerce_created_customer( $customer_id ) {
	
	if ( isset( $_POST['billing_first_name'] ) ) {
		update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
		update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
	}
	
	if ( isset( $_POST['billing_last_name'] ) ) {
		update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
		update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
	}
	
	if ( isset( $_POST['billing_phone'] ) ) {
		update_user_meta( $customer_id, 'billing_phone', sanitize_text_field( $_POST['billing_phone'] ) );
	}
	
}

add_action( 'woocommerce_created_customer', 'rc_woocommerce_created_customer' );