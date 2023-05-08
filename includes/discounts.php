<?php


// Multiple camp/camper discount, if more than one camp/camper is in the cart
function rc_multicamp_multicamper_discount( $cart ) {
	
	if ( WC()->cart->get_cart_contents_total() == '0.00' ) {
		// total is already zero, don't bother adding a discount
		return true;
	}
	
	$discounts = get_option( 'rc_discounts' ,[]);
	
	// get the discount amounts and exit early if neither discount is in use
	$multicamp_value   = isset( $discounts['multicamp'] ) ? (int) $discounts['multicamp'] : 0;
	$multicamper_value = isset( $discounts['multicamper'] ) ? (int) $discounts['multicamper'] : 0;
	if ( ! $multicamp_value && ! $multicamper_value ) {
		return true;
	}
	
	// get how many unique camps and campers there are
	$cart_count = rc_count_camps_campers_in_cart();
	
	// apply the multicamp discount if there are both camps in cart
	if ( $multicamp_value && $cart_count["camps"] > 1 ) {
		$cart->add_fee( 'Multi-Camp Discount', - $multicamp_value );
	}
	
	// if at least two campers exist, apply the multicamper discount once per camper (maybe including the first camper)
	if ( $multicamper_value && $cart_count["campers"] > 1 ) {
		
		if ( $discounts['multicamper_include_first'] == 'yes' ) {
			// include the first camper
			$discount = $multicamper_value * $cart_count["campers"];
		} else {
			// don't include the first camper
			$discount = $multicamper_value * ( $cart_count["campers"] - 1 );
		}
		
		$cart->add_fee( 'Multi-Camper Discount', - $discount );
	}
}

add_action( 'woocommerce_cart_calculate_fees', 'rc_multicamp_multicamper_discount' );

// helper function to count how many unique campers and camps are in the cart
function rc_count_camps_campers_in_cart( $return_count = true ) {
	
	$cart = WC()->cart->get_cart();
	
	$camps_in_cart   = array();
	$campers_in_cart = array();
	
	foreach ( $cart as $cart_item ) {
		if ( $cart_item['product_id'] == get_option( 'rc_product_id' ) ) {
			if ( isset( $cart_item['variation'] ) && isset( $cart_item['variation']['attribute_camp'] ) ) {
				$camps_in_cart[] = $cart_item['variation']['attribute_camp'];
			}
			if ( isset( $cart_item['rc_firstname'] ) || isset( $cart_item['rc_lastname'] ) ) {
				$campers_in_cart[] = strtolower( $cart_item['rc_firstname'] . $cart_item['rc_lastname'] );
			}
		}
	}
	
	if ( $return_count ) {
		// count how many unique camps and campers there are
		return array(
			"camps"   => count( array_unique( $camps_in_cart ) ),
			"campers" => count( array_unique( $campers_in_cart ) ),
		);
	} else {
		// return the lists of camps and campers
		return array(
			"camps"   => $camps_in_cart,
			"campers" => $campers_in_cart,
		);
	}
}


function rc_get_earlybird_discount() {
	
	$discounts = get_option( 'rc_discounts' ,[]);
	// get earlybird settings
	
	$earlybird_value = isset( $discounts['earlybird_value'] ) ? (int) $discounts['earlybird_value'] : 0;
	if ( ! $earlybird_value ) {
		return 0;
	}
	
	$earlybird_ends = isset( $discounts['earlybird_ends'] ) ? $discounts['earlybird_ends'] : 0;
	if ( $earlybird_ends ) {
		$earlybird_ends = preg_replace( "([^0-9\-])", "", $earlybird_ends );
	} else {
		return 0;
	}
	
	// get sleepybird settings
	
	$sleepybird_value = isset( $discounts['sleepybird_value'] ) ? (int) $discounts['sleepybird_value'] : 0;
	$sleepybird_ends  = isset( $discounts['sleepybird_ends'] ) ? $discounts['sleepybird_ends'] : 0;
	if ( $sleepybird_ends ) {
		$sleepybird_ends = preg_replace( "([^0-9\-])", "", $sleepybird_ends );
	}
	
	// get unix timestamps for right now and for the threshold times
	
	$now = time();
	date_default_timezone_set( get_option( 'timezone_string' ) );
	$earlybird_ends_time  = strtotime( $earlybird_ends . ' 23:59:59' );
	$sleepybird_ends_time = strtotime( $sleepybird_ends . ' 23:59:59' );
	
	// apply any discounts
	
	if ( $now < $earlybird_ends_time ) {
		return (int) $earlybird_value;
	} elseif ( $sleepybird_value && $now < $sleepybird_ends_time ) {
		return (int) $sleepybird_value;
	}
	
	return 0;
}


/*
 * Applies the earlybird discount as a sale price
 * Modified from https://stackoverflow.com/questions/48763989/set-product-sale-price-programmatically-in-woocommerce-3
 */


// Generate the product "regular price"
function rc_dynamic_regular_price( $regular_price, $product ) {
	if ( empty( $regular_price ) || $regular_price == 0 ) {
		return $product->get_price();
	} else {
		return $regular_price;
	}
}

add_filter( 'woocommerce_product_get_regular_price', 'rc_dynamic_regular_price', 10, 2 );
add_filter( 'woocommerce_product_variation_get_regular_price', 'rc_dynamic_regular_price', 10, 2 );


// Generate the product "sale price"
function rc_dynamic_sale_price( $sale_price, $product ) {
	$product_id = $product->is_type( "variation" ) ? $product->get_parent_id() : $product->get_id();
	if ( $product_id == get_option( "rc_product_id" ) ) {
		$discount = rc_get_earlybird_discount();
	} else {
		$discount = 0;
	}
	
	if ( empty( $sale_price ) || $sale_price == 0 ) {
		return $product->get_regular_price() - $discount;
	} else {
		return $sale_price;
	}
}

add_filter( 'woocommerce_product_get_sale_price', 'rc_dynamic_sale_price', 10, 2 );
add_filter( 'woocommerce_product_variation_get_sale_price', 'rc_dynamic_sale_price', 10, 2 );


// Display formatted regular price + sale price
function rc_woocommerce_get_price_html( $price_html, $product ) {
	if ( $product->get_id() == get_option( 'rc_product_id' ) ) {
		$reg  = $product->get_regular_price();
		$sale = $product->get_sale_price();
		if ( $reg != $sale ) {
			$price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $reg ) ), wc_get_price_to_display( $product, array( 'price' => $sale ) ) ) . $product->get_price_suffix();
		}
	}
	
	return $price_html;
}

add_filter( 'woocommerce_get_price_html', 'rc_woocommerce_get_price_html', 20, 2 );


// show the original and sale price in the cart
function rc_woocommerce_cart_item_price( $price, $values ) {
	$price = $values['data']->get_price_html();
	
	return $price;
}

add_filter( 'woocommerce_cart_item_price', 'rc_woocommerce_cart_item_price', 10, 2 );


// set the price in the cart
function rc_set_cart_item_sale_price( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}
	
	foreach ( $cart->get_cart() as $cart_item ) {
		$price = $cart_item['data']->get_sale_price();
		$cart_item['data']->set_price( $price );
	}
}

add_action( 'woocommerce_before_calculate_totals', 'rc_set_cart_item_sale_price', 20, 1 );