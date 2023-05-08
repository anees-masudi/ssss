<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Add custom field to order object
function rc_add_custom_data_to_order( $item ) {
	foreach ( $item as $cart_item_key => $values ) {
		if ( $item->get_product_id() != get_option( 'rc_product_id' ) ) {
			return;
		}
		foreach ( array( 'rc_firstname', 'rc_lastname', 'rc_birthday', 'rc_shirt', 'rc_medical', 'rc_attended' ) as $metakey ) {
			if ( isset( $values[ $metakey ] ) ) {
				$item->add_meta_data( $metakey, $values[ $metakey ], true );
			}
		}
	}
}

add_action( 'woocommerce_checkout_create_order_line_item', 'rc_add_custom_data_to_order' );


function rc_woocommerce_new_order( $order_id ) {
	
	$order = wc_get_order( $order_id );
	$items = $order->get_items();
	foreach ( $items as $item_id => $item ) {
		if ( $item->get_product_id() != get_option( 'rc_product_id' ) ) {
			continue;
		}
		
		rc_assign_camper_code( $item );
		
		// add an empty group assignment field (note: use a space because WC won't save an empty string)
		wc_add_order_item_meta( $item_id, 'rc_group', ' ', true );
	}
}

add_action( 'woocommerce_checkout_update_order_meta', 'rc_woocommerce_new_order' );


// helper function to save a code for this camper, either the same one he/she was already assigned or a unique one nobody else is using
function rc_assign_camper_code( $camper ) {
	$camper_id = $camper->get_id();
	try {
		
		$camp         = wc_get_order_item_meta( $camper_id, 'camp', true );
		$gender       = wc_get_order_item_meta( $camper_id, 'gender', true );
		$age          = wc_get_order_item_meta( $camper_id, 'age', true );
		$rc_firstname = wc_get_order_item_meta( $camper_id, 'rc_firstname', true );
		$rc_lastname  = wc_get_order_item_meta( $camper_id, 'rc_lastname', true );
		$rc_birthday  = wc_get_order_item_meta( $camper_id, 'rc_birthday', true );
		$rc_attended  = wc_get_order_item_meta( $camper_id, 'rc_attended', true );
		
		$existing_codes = array();
		
		$orders = wc_get_orders( array(
			'limit' => - 1,
			'type'  => wc_get_order_types(),
		) );
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( $item->get_product_id() != get_option( 'rc_product_id' ) ) {
					// this wasn't the camp registration product
					continue;
				}
				
				// if the same camper is already registered for a different camp, we'll try to reuse his/her code
				$camper_maybe_already_exists = true;
				if ( $camp == wc_get_order_item_meta( $item_id, 'camp', true ) ) {
					// if this registration is for the same camp we need a code for, then definitely generate a new code
					$camper_maybe_already_exists = false;
				} else {
					// all these meta values need to match in order for the camper to be considered the same
					foreach ( array( 'gender', 'age', 'rc_firstname', 'rc_lastname', 'rc_birthday', 'rc_attended' ) as $metakey ) {
						if ( $$metakey != wc_get_order_item_meta( $item_id, $metakey, true ) ) {
							$camper_maybe_already_exists = false;
							break;
						}
					}
				}
				
				if ( $camper_maybe_already_exists ) {
					// we have now confirmed camper already exists
					if ( $code = wc_get_order_item_meta( $item_id, 'rc_code', true ) ) {
						// if a code was already assigned, use it; otherwise keep going to generate a new one
						return wc_update_order_item_meta( $camper_id, 'rc_code', $code );
					}
				}
				
				// we may still need to generate a new code -- save this one so we can be sure not to duplicate it
				if ( $code = wc_get_order_item_meta( $item_id, 'rc_code', true ) ) {
					$existing_codes[] = $code;
				}
				
			}
		}
		
		do {
			$code = 'AB' . str_pad( (string) rand( 0, 999999 ), '6', '0', STR_PAD_LEFT );
		} while( in_array( $code, $existing_codes ) );
		
		return wc_update_order_item_meta( $camper_id, 'rc_code', $code );
	} catch( Exception $e ) {
	}
}
