<?php

if (!defined('ABSPATH')) {
	exit;
}


// validate cart contents: don't allow more than one of each item, make sure all camp registrations have all required metadata
function rc_woocommerce_check_cart_items() {
	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

		if (!$cart_item['data']->is_type("variation") || $cart_item['data']->get_parent_id() != get_option('rc_product_id')) {
			continue;
		}

		if ($cart_item['quantity'] > 1) {
			WC()->cart->set_quantity($cart_item_key, 1, true);
		}

		foreach (array('rc_firstname', 'rc_lastname', 'rc_birthday', 'rc_shirt', 'rc_medical', 'rc_attended') as $metakey) {
			if (!isset($cart_item[$metakey])) {
				wc_add_notice(sprintf('Error: registration was missing camper information and has been automatically removed. Please try again or contact the site admin if the problem persists.'), 'error');
				WC()->cart->remove_cart_item($cart_item_key);
				break;
			}
		}
	}

	$cart_count = rc_count_camps_campers_in_cart();
	if ($cart_count["campers"] > 5) {
		wc_add_notice(sprintf('Error: You can only register five campers at a time!'), 'error');
	}
}

add_action('woocommerce_check_cart_items', 'rc_woocommerce_check_cart_items');

// Validate camp registrations
function rc_validate_camp_registrations($passed, $product_id) {

	// this function is only to validate camp registrations
	if ($product_id != get_option("rc_product_id")) {
		return $passed;
	}

	if (empty($_POST['rc-firstname-field']) || empty($_POST['rc-lastname-field'])) {
		$passed = false;
		wc_add_notice('Please enter your camper\'s full name', 'error');
	}
	if (empty($_POST['rc-attended-field'])) {
		$passed = false;
		wc_add_notice('Has your camper attended Runner\'s Camp before?', 'error');
	}
	if (empty($_POST['rc-shirt-field'])) {
		$passed = false;
		wc_add_notice('Please enter your camper\'s shirt size', 'error');
	}
	if (empty($_POST['rc-medical-field'])) {
		$passed = false;
		wc_add_notice('Please enter your camper\'s medical information', 'error');
	}

	if (empty($_POST['rc-birthday-field'])) {
		$passed = false;
		wc_add_notice('Please enter your camper\'s birthday', 'error');
	} else {
		// birthday field is special! validate against the age group selected
		$birthday = $_POST['rc-birthday-field'];
		$age_group = (int)$_POST['attribute_age'];
		if ($failed = rc_validate_birthday_against_age_group($birthday, $age_group)) {
			$passed = false;
			wc_add_notice($failed, 'error');
		}
	}

	// make sure this camper isn't already registered
	if ($failed = rc_validate_registration_not_already_in_cart()) {
		$passed = false;
		wc_add_notice($failed, 'error');
	}

	// make sure there aren't already five campers in the cart (unless we're editing an existing cart item)
	if ($failed = rc_validate_prevent_more_than_five_campers_in_cart()) {
		$passed = false;
		wc_add_notice($failed, 'error');
	}


	return $passed;
}

add_filter('woocommerce_add_to_cart_validation', 'rc_validate_camp_registrations', 10, 2);

function rc_validate_prevent_more_than_five_campers_in_cart() {

	// validation for editing a cart item is more complicated than for adding a new cart item...
	if (isset($_POST['edit_cart_item']) && $_POST['edit_cart_item']) {
		$cart = WC()->cart->get_cart();
		if ($item_in_cart = $cart[$_POST['edit_cart_item']]) {
			// we're editing a camper in the cart, check to make sure the name won't cause issues
			if ($item_in_cart['rc_firstname'] != $_POST['rc-firstname-field'] || $item_in_cart['rc_lastname'] != $_POST['rc-lastname-field']) {
				// we're editing the camper name, check to make sure there won't be 6 campers in the cart afterwards

				// grab all the current camper names in the cart
				$campers_in_cart = rc_count_camps_campers_in_cart(false)["campers"];

				// remove the camper name we're trying to edit (note: this name might appear multiple times in the list, just remove it once)
				$old_camper_name = strtolower($item_in_cart['rc_firstname'] . $item_in_cart['rc_lastname']);
				if (($key = array_search($old_camper_name, $campers_in_cart)) !== false) {
					unset($campers_in_cart[$key]);
				}

				// include the new camper name we're trying to change the old one to
				$campers_in_cart[] = strtolower($_POST['rc-firstname-field'] . $_POST['rc-lastname-field']);

				if (count(array_unique($campers_in_cart)) > 5) {
					// editing this name would result in more than 5 campers in the cart!
					return 'You can only register five campers at a time!';
				}
			}

			// if nothing above was problematic, then this camper can be edited
			return false;
		}
	}

	// validation for adding a new camper

	// grab all the current camper names in the cart
	$campers_in_cart = rc_count_camps_campers_in_cart(false)["campers"];

	// include the new camper name we're trying to register
	$campers_in_cart[] = strtolower($_POST['rc-firstname-field'] . $_POST['rc-lastname-field']);

	if (count(array_unique($campers_in_cart)) > 5) {
		// adding this camper would result in more than 5 campers in the cart!
		return 'You can only register five campers at a time!';
	}

	return false;
}

function rc_validate_registration_not_already_in_cart() {

	if (isset($_POST['edit_cart_item']) && $_POST['edit_cart_item']) {
		$cart = WC()->cart->get_cart();
		if ($cart[$_POST['edit_cart_item']]) {
			// editing an existing registration -- don't worry if nothing is changed
			return false;
		}
	}

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

		if ($cart_item['product_id'] != $_POST['product_id']) {
			continue;
		}
		if ($cart_item['variation_id'] != $_POST['variation_id']) {
			continue;
		}
		foreach (array('rc_firstname', 'rc_lastname', 'rc_birthday', 'rc_shirt', 'rc_medical', 'rc_attended') as $metakey) {
			$fieldname = str_replace('_', '-', $metakey) . '-field';
			if ($cart_item[$metakey] != $_POST[$fieldname]) {
				continue 2;
			}
		}

		// all of these values match, so adding this product would make the quantity 2, which is not allowed
		return 'You have already registered this camper!';
	}

	return false;
}

function rc_validate_birthday_against_age_group($birthday, $age_group) {

	$failed = false;

	if ($birthday !== preg_replace("([^0-9/])", "", $birthday)) {
		return 'Invalid birthday';
	}

	$camps = get_option("rc_camps",[]);
	if (!isset($camps['first_day']) || !$camps['first_day'] || !isset($camps['last_day']) || !$camps['last_day']) {
		// skip validation
		return $failed;
	}

	date_default_timezone_set(get_option('timezone_string'));
	$birthday_dateobj = DateTime::createFromFormat("m/d/Y", $birthday);

	$first_day_of_camp = date_create($camps['first_day']);
	$last_day_of_camp = date_create($camps['last_day']);

	// maybe add grace period
	if (isset($camps['grace_period']) && $camps['grace_period']) {
		$grace_period = (int)$camps['grace_period'];

		// on the first day of camp, you must not already be too old
		// so the grace period should make you seem younger by subtracting days from the first day
		date_sub($first_day_of_camp, date_interval_create_from_date_string($grace_period . ' days'));

		// on the last day of camp, you must now be old enough
		// so the grace period should make you seem older by adding days to the last day of camp
		date_add($last_day_of_camp, date_interval_create_from_date_string($grace_period . ' days'));
	}

	$age_on_last_day = date_diff($birthday_dateobj, $last_day_of_camp);
	$age_on_first_day = date_diff($birthday_dateobj, $first_day_of_camp);

	if ($age_on_first_day->y > $age_group) {
		// camper already too old on the first day of camp
		return 'This camper is too old for the selected age group';
	}

	if ($age_on_last_day->y < $age_group) {
		// camper still too young on the last day of camp

		// check for the option to allow younger kids register for the 6-year-old age group anyway
		if ($age_group != 6 || !isset($camps['allow_under_six']) || !$camps['allow_under_six']) {
			return 'This camper is too young for the selected age group';
		}

		// if we got here, campers under six have more flexibility; maybe this camper qualifies
		$last_day_allowed_to_be_six = date_create($camps['allow_under_six']);
		date_add($last_day_allowed_to_be_six, date_interval_create_from_date_string('1 day')); // doesn't include the last day otherwise

		$age_on_last_day_allowed_to_be_six = date_diff($birthday_dateobj, $last_day_allowed_to_be_six);

		if ($age_on_last_day_allowed_to_be_six->y < 6) {
			// no dice, camper won't turn six in time to qualify
			return 'This camper is too young for the selected age group';
		}
	}

	return $failed;
}

// Add the custom fields as item data to the cart object
function rc_add_custom_field_item_data($cart_item_data) {
	foreach (array('rc_firstname', 'rc_lastname', 'rc_birthday', 'rc_shirt', 'rc_medical', 'rc_attended') as $metakey) {
		$fieldname = str_replace('_', '-', $metakey) . '-field';
		if (!empty($_POST[$fieldname])) {
			$cart_item_data[$metakey] = trim($_POST[$fieldname]);
		}
	}

	return $cart_item_data;
}

add_filter('woocommerce_add_cart_item_data', 'rc_add_custom_field_item_data');

// customizations for the add to cart action
function rc_woocommerce_add_to_cart() {
	remove_action('woocommerce_add_to_cart', 'rc_woocommerce_add_to_cart', 99);

	// if editing a cart item, remove the old one from the cart
	if (isset($_POST['edit_cart_item']) && ($cart_item_key = $_POST['edit_cart_item'])) {
		$cart = WC()->cart->get_cart();
		if (isset($cart[$cart_item_key])) {
			if ($cart[$cart_item_key]['quantity'] > 1) {
				WC()->cart->set_quantity($cart_item_key, 1, true);
			} else {
				WC()->cart->remove_cart_item($cart_item_key);
			}
			add_filter('ngettext', 'rc_filter_ngettext', 10, 5); // change the success message
		}
	}

	// add the other camps to cart when multiple are selected
	if (isset($_POST['additional_variation_ids']) && $_POST['additional_variation_ids'] != '') {
		$additional_variation_ids = explode(',', $_POST['additional_variation_ids']);
		foreach($additional_variation_ids as $variation_id) {
			if (is_int((int) $variation_id)) {
				$product_id = (int)$_POST['product_id'];
				$attributes = wc_get_product_variation_attributes($variation_id);
				WC()->cart->add_to_cart($product_id, 1, $variation_id, $attributes);
			}
		}
	}

	// clear all fields so they don't autofill with already-submitted data
	$fields_to_delete = array(
		'rc-firstname-field',
		'rc-lastname-field',
		'rc-birthday-field',
		'rc-shirt-field',
		'rc-medical-field',
		'rc-attended-field',
		'edit_cart_item',
		'register-both-camps',
		'attribute_age',
		'attribute_gender',
		'attribute_camp',
	);
	foreach ($fields_to_delete as $fieldname) {
		//unset( $_POST[ $fieldname ] );
		//unset( $_GET[ $fieldname ] );
		unset($_REQUEST[$fieldname]);
	}
}

add_action('woocommerce_add_to_cart', 'rc_woocommerce_add_to_cart', 99);


function rc_option_woocommerce_cart_redirect_after_add($value) {
	if (isset($_REQUEST['product_id']) && $_REQUEST['product_id'] != get_option("rc_product_id")) {
		// preserve default if not adding the camp product to cart
		return $value;
	}

	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save-and-add') {
		return "no";
	}

	return "yes";
}

add_filter("option_woocommerce_cart_redirect_after_add", "rc_option_woocommerce_cart_redirect_after_add");