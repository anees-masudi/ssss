<?php

if (!defined('ABSPATH')) {
	exit;
}

function rc_add_spam_warning() {
	?>
    <p>Your registration confirmation will be emailed to you. Please check your spam or junk folder if you don't see it in your inbox.</p>
	<?php
}

add_action('woocommerce_review_order_before_submit', 'rc_add_spam_warning');


function rc_filter_gettext($translated) {
	if ($translated == "Continue shopping" && isset($_REQUEST['product_id']) && $_REQUEST['product_id'] == get_option("rc_product_id")) {
		$translated = "Add campers";
	}

	return $translated;
}

add_filter('gettext', 'rc_filter_gettext');


function rc_filter_ngettext($translation, $single, $plural, $number, $domain) {
	if ($single == "%s has been added to your cart.") {
		remove_filter('ngettext', 'rc_filter_ngettext', 10);
		$translations = get_translations_for_domain($domain);
		$translation = $translations->translate_plural("%s has been edited.", "%s have been edited.", $number);
	}

	return $translation;
}


// moves page titles farther down the page; be careful to only filter the main page title!
function rc_move_product_title($title, $id) {
	if (is_product() && $id == get_option('rc_product_id') && did_action("get_template_part_content") == 1) {
		remove_filter('the_title', 'rc_move_product_title', 10);
		add_action("woocommerce_single_product_summary", function () {
			wc_get_template('single-product/title.php');
		}, 5);

		return ''; // hide this one to prevent it showing up twice -- the cart and checkout pages seem to be ok though
	} elseif (is_cart()) {
		remove_filter('the_title', 'rc_move_product_title', 10);
		add_action("woocommerce_cart_is_empty", function () {
			wc_get_template('single-product/title.php');
		}, 5);
		add_action("woocommerce_before_cart", function () {
			wc_get_template('single-product/title.php');
		}, 5);
	} elseif (is_checkout()) {
		remove_filter('the_title', 'rc_move_product_title', 10);
		add_filter('the_title', 'wc_page_endpoint_title');
		add_action("woocommerce_before_checkout_form", function () {
			add_filter('the_title', 'wc_page_endpoint_title');
			wc_get_template('single-product/title.php');
		}, 5);
		add_action("woocommerce_before_thankyou", function () {
			add_filter('the_title', 'wc_page_endpoint_title');
			wc_get_template('single-product/title.php');
		}, 5);
	}

	return $title;
}

add_filter('the_title', 'rc_move_product_title', 10, 2);


// change product name in cart (SEE ALSO woocommerce_order_item_name)
function rc_woocommerce_cart_item_name($cart_item_name, $cart_item) {
	if ($cart_item['product_id'] != get_option("rc_product_id")) {
		return $cart_item_name;
	}

	$name = esc_html($cart_item['rc_firstname'] . ' ' . $cart_item['rc_lastname']);
	$gender = $cart_item['variation']['attribute_gender'];
	$age = $cart_item['variation']['attribute_age'];
	$camp = $cart_item['variation']['attribute_camp'];

	$cart_item_name = '<a href="' . get_the_permalink($cart_item['product_id']) . '">' . get_the_title($cart_item['product_id']) . '</a>';
	$cart_item_name .= rc_format_item_name($name, $gender, $age, $camp);

	return $cart_item_name;
}

add_filter('woocommerce_cart_item_name', 'rc_woocommerce_cart_item_name', 10, 2);

// change product name in order (SEE ALSO woocommerce_cart_item_name)
function rc_woocommerce_order_item_name($order_item_name, $order_item) {
	if ($order_item['product_id'] != get_option("rc_product_id")) {
		return $order_item_name;
	}

	$name = esc_html($order_item['rc_firstname'] . ' ' . $order_item['rc_lastname']);
	$gender = $order_item->get_meta("gender");
	$age = $order_item->get_meta("age");
	$camp = $order_item->get_meta("camp");

	$order_item_name = '<a href="' . get_the_permalink($order_item['product_id']) . '">' . get_the_title($order_item['product_id']) . '</a>';
	$order_item_name .= rc_format_item_name($name, $gender, $age, $camp);

	return $order_item_name;
}

add_filter('woocommerce_order_item_name', 'rc_woocommerce_order_item_name', 10, 2);

// show an Edit Camper Information link for each cart item
function rc_woocommerce_after_cart_item_name($cart_item, $cart_item_key) {
	$product_permalink = $cart_item['data']->get_permalink($cart_item);

	foreach (array('rc_firstname', 'rc_lastname', 'rc_birthday', 'rc_shirt', 'rc_medical', 'rc_attended') as $metakey) {
		$value = $cart_item[$metakey];
		$fieldname = str_replace('_', '-', $metakey) . '-field';
		$product_permalink = add_query_arg($fieldname, $value, $product_permalink);
	}

	$product_permalink = add_query_arg('edit_cart_item', $cart_item_key, $product_permalink);

	echo '<a href="' . esc_url($product_permalink) . '" class="rc-edit-cart-item hidden">Edit camper information</a>';
}

add_action("woocommerce_after_cart_item_name", "rc_woocommerce_after_cart_item_name", 10, 2);


// change waitlist message
function rc_wcwl_join_waitlist_message_text($text) {
	return "Join the waitlist and we'll email you if a space in this camp opens up.";
}

add_filter('wcwl_join_waitlist_message_text', 'rc_wcwl_join_waitlist_message_text');


// set max quantity to 1 so that woocommerce hides the quantity input
function rc_woocommerce_quantity_input_args($args, $product) {
	if ($product->get_id() == get_option('rc_product_id')) {
		$args['max_value'] = 1;
	}

	return $args;
}

add_filter('woocommerce_quantity_input_args', 'rc_woocommerce_quantity_input_args', 10, 2);


// change the low/out of stock verbiage to talk about spaces
function rc_woocommerce_get_availability_text($availability, $variation) {
	$product_id = $variation->get_parent_id();
	if ($product_id != get_option('rc_product_id')) {
		return $availability;
	}

	// specify camp if product has camp attribute
	if ($camp = $variation->get_attribute("camp")) {
		$camp = ' in ' . $camp;
	} else {
		$camp = '';
	}

	if ($availability == "1 in stock") {
		$availability = "1 space left" . $camp;
	} elseif ($availability == "Out of stock") {
		$availability = "No spaces left" . $camp;
	} else {
		$availability = str_replace("in stock", "spaces left" . $camp, $availability);
	}

	return $availability;
}

add_filter('woocommerce_get_availability_text', 'rc_woocommerce_get_availability_text', 10, 2);


// customize the Age label to explain how it works; also add labels to our custom metadata
function rc_woocommerce_attribute_label($attribute_label, $attribute_name, $product) {

	if (!is_admin() && $attribute_name == 'Age') {
		return $attribute_label . ' (<small>on the first day of camp</small>)';
	}
	if (!is_admin() && $attribute_name == 'Gender') {
		return $attribute_label . ' (<small>as assigned at birth</small>)';
	}
	if (!is_admin() && $attribute_name == 'Camp') {
		return $attribute_label . ' (<small>please select all desired options</small>)';
	}

	switch ($attribute_name) {
		case 'rc_firstname':
			return 'First name';
		case 'rc_lastname':
			return 'Last name';
		case 'rc_birthday':
			return 'Birthday';
		case 'rc_shirt':
			return 'Shirt';
		case 'rc_medical':
			return 'Medical info';
		case 'rc_attended':
			return 'Attended before?';
		case 'rc_code':
			return 'Unique code';
		case 'rc_group':
			return 'Group';
		case 'meet1_events':
			return 'Meet 1 events';
		case 'meet2_events':
			return 'Meet 2 events';
	}

	return $attribute_label;
}

add_filter('woocommerce_attribute_label', 'rc_woocommerce_attribute_label', 10, 3);


// hide some of the item meta from being displayed
function rc_woocommerce_order_item_get_formatted_meta_data($formatted_meta) {
	if (empty($formatted_meta)) {
		return $formatted_meta;
	}

	foreach ($formatted_meta as $key => $meta) {
		if (in_array($meta->key, array('rc_firstname', 'rc_lastname', 'gender', 'age', 'camp', 'rc_code', 'rc_group'))) {
			unset($formatted_meta[$key]);
		}
	}

	// disable this filter as soon as we're done with it so it doesn't alter anything else
	remove_filter('woocommerce_order_item_get_formatted_meta_data', 'rc_woocommerce_order_item_get_formatted_meta_data', 1);

	return $formatted_meta;
}

add_action('woocommerce_order_item_meta_start', function () {
	// add this filter right before we need it so it doesn't alter anything else
	add_filter('woocommerce_order_item_get_formatted_meta_data', 'rc_woocommerce_order_item_get_formatted_meta_data', 1);
}, 9999);

add_action('woocommerce_order_item_meta_end', function () {
	//remove_filter( 'woocommerce_order_item_get_formatted_meta_data', 'rc_woocommerce_order_item_get_formatted_meta_data', 1 );
}, 9999);


function rc_woocommerce_order_item_display_meta_value($display_value) {
	return stripslashes($display_value);
}

add_filter('woocommerce_order_item_display_meta_value', 'rc_woocommerce_order_item_display_meta_value');

remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');

// helper function to format cart/order item names
function rc_format_item_name($name, $gender, $age, $camp) {
	if ($camp) {
		$rc_camps = get_option('rc_camps', []);
		if ($camp == 'Camp 1' && isset($rc_camps['camp1_name']) && $rc_camps['camp1_name']) {
			$camp = $rc_camps['camp1_name'];
		} elseif ($camp == 'Camp 2' && isset($rc_camps['camp2_name']) && $rc_camps['camp2_name']) {
			$camp = $rc_camps['camp2_name'];
		} elseif ($camp == 'Camp 3' && isset($rc_camps['camp3_name']) && $rc_camps['camp3_name']) {
			$camp = $rc_camps['camp3_name'];
		}
		$camp = '<br><em>' . $camp . '</em>';
	}

	return $camp . '<br><br><strong>' . $name . '</strong> (' . $gender . ', ' . $age . ')';
}

// Switch to checkboxes for camp attribute
function rc_woocommerce_dropdown_variation_attribute_options_html($html, $args) {
	$product_id = $args['product']->get_id();
	if ($product_id != get_option("rc_product_id")) {
		return $html;
	}

	if ($args['attribute'] != 'Camp') {
		return $html;
	}

	// customize labels in the dropdowns
	$rc_camps = get_option('rc_camps', []);
	$old_html = $html;	
	$checked_camps = get_checked_camps();
	// var_dump($checked_camps); die();
	$html = "<div id='camp' class='rc-camp-checkbox-group'>";
	if (isset($rc_camps['camp1_name']) && $rc_camps['camp1_name'] && stristr($old_html, 'Camp 1') !== false) {
		$html .= "<label class='camp_checkbox'><input type='checkbox' name='possible_camps' value='Camp 1' " . (isset($checked_camps['Camp 1']) ? 'checked' : '') . "/>" . $rc_camps['camp1_name'] . "</label>"; 
	}

	if (isset($rc_camps['camp2_name']) && $rc_camps['camp2_name'] && strstr($old_html, 'Camp 2') !== false) {
		$html .= "<label class='camp_checkbox'><input type='checkbox' name='possible_camps' value='Camp 2' " . (isset($checked_camps['Camp 2']) ? 'checked' : '') . "/>" . $rc_camps['camp2_name'] . "</label>"; 
	}

	if (isset($rc_camps['camp3_name']) && $rc_camps['camp3_name'] && strstr($old_html, 'Camp 3') !== false) {
		$html .= "<label class='camp_checkbox'><input type='checkbox' name='possible_camps' value='Camp 3' " . (isset($checked_camps['Camp 3']) ? 'checked' : '') . "/>" . $rc_camps['camp3_name'] . "</label>"; 
	}
	$html .= "<input type='hidden' id='selected_camp' name='attribute_camp' data-attribute_name='attribute_camp' value=''/>";
	$html .= "<input type='hidden' id='additional_selected_camps' name='additional_selected_camps' value=''/>";
	$html .= "<input type='hidden' id='camp_variations' value='' />";
	$html .= "<input type='hidden' id='additional_variation_ids' name='additional_variation_ids' value='' /></div>";

	// if (isset($rc_camps['camp1_name']) && $rc_camps['camp1_name']) {
	// 	$html = str_replace("Camp 1</option>", $rc_camps['camp1_name'] . '</option>', $html);
	// }
	// if (isset($rc_camps['camp2_name']) && $rc_camps['camp2_name']) {
	// 	$html = str_replace("Camp 2</option>", $rc_camps['camp2_name'] . '</option>', $html);
	// }
	// if (isset($rc_camps['camp3_name']) && $rc_camps['camp3_name']) {
	// 	$html = str_replace("Camp 3</option>", $rc_camps['camp3_name'] . '</option>', $html);
	// }

	// // add "Both"
	// $selected = (isset($_REQUEST["register-both-camps"]) && $_REQUEST["register-both-camps"]);
	// $html = str_replace("</select>", '<option ' . selected($selected, true, false) . 'value="Camp 1" data-camp="both" class="attached enabled">All Camps</option></select>', $html);

	return $html;
}

add_filter('woocommerce_dropdown_variation_attribute_options_html', 'rc_woocommerce_dropdown_variation_attribute_options_html', 10, 2);

function get_checked_camps()
{
	$camps = !empty($_REQUEST['additional_selected_camps']) ? explode(',', $_REQUEST['additional_selected_camps']) : [];
	array_push($camps, $_REQUEST['attribute_camp']);
	$camps = array_combine(array_values($camps), array_fill(0, count($camps), 'checked'));
	return $camps;
}


function rc_woocommerce_cart_item_permalink($permalink, $cart_item) {
	if ($cart_item['product_id'] != get_option("rc_product_id")) {
		return $permalink;
	}

	return get_permalink($cart_item['product_id']);
}

add_filter('woocommerce_cart_item_permalink', 'rc_woocommerce_cart_item_permalink', 10, 2);


// Display the custom field value in the cart
function rc_woocommerce_get_item_data($item_data, $cart_item) {
	if ($cart_item['product_id'] != get_option('rc_product_id')) {
		return $item_data;
	}

	// already displaying these elsewhere
	foreach ($item_data as $key => $val) {
		if (in_array($val['key'], array("Camp", "Age", "Gender"))) {
			unset($item_data[$key]);
		}
	}

	// add the custom metadata
	$item_data[] = array(
		'key' => 'Attended before?',
		'value' => esc_html($cart_item['rc_attended']),
	);
	$item_data[] = array(
		'key' => 'Birthday',
		'value' => esc_html($cart_item['rc_birthday']),
	);
	$item_data[] = array(
		'key' => 'Shirt',
		'value' => esc_html($cart_item['rc_shirt']),
	);
	$item_data[] = array(
		'key' => 'Medical information',
		'value' => esc_html(stripslashes($cart_item['rc_medical'])),
	);

	return $item_data;
}

add_filter('woocommerce_get_item_data', 'rc_woocommerce_get_item_data', 10, 2);


// add a bunch of customizations to the registration product page
function rc_add_single_product_hooks() {
	if (get_the_ID() != get_option("rc_product_id")) {
		return;
	}

	// hide "on sale" indicator and product images
	remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
	remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

	// hide meta for SKU and category
	remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

	// move price to above the add to cart buttons
	remove_filter('woocommerce_single_product_summary', 'woocommerce_template_single_price');
	add_filter('woocommerce_before_add_to_cart_button', 'woocommerce_template_single_price', 12);

	// remove related products
	remove_filter("woocommerce_after_single_product_summary", "woocommerce_output_related_products", 20);

	// add custom fields/html to the template
	add_action('woocommerce_single_product_summary', 'rc_display_view_cart_link', 7);
	add_action('woocommerce_before_add_to_cart_button', 'rc_display_custom_fields', 11);
	add_action('woocommerce_before_add_to_cart_button', 'rc_woocommerce_before_add_to_cart_button', 20);
	add_filter('woocommerce_product_single_add_to_cart_text', 'rc_woocommerce_product_single_add_to_cart_text');
	add_action('woocommerce_after_add_to_cart_button', 'rc_woocommerce_after_add_to_cart_button');

}

add_action('woocommerce_before_single_product', 'rc_add_single_product_hooks', 20);

function rc_display_view_cart_link() {
	$cart_count = WC()->cart->cart_contents_count;
	if ($cart_count) {
		echo '<p class="rc-view-cart-link"><a href="' . wc_get_cart_url() . '"><i class="fa fa-shopping-cart" aria-hidden="true"></i> ' . $cart_count . ' registration' . _n('', 's', $cart_count) . ' in cart &rarr;</a></p>';
	}
}

function rc_display_custom_fields() { ?>
    <div id="rc-registration-fields" class="rc-registration-fields">

    <h2>Step 2: Camper Details</h2>
    <p>Please fill out all fields.</p>
    <div class="rc-field">
        <label for="rc-field-firstname">Name <abbr class="required" title="required">*</abbr></label><br>
        <div class="rc-field-name">
            <label>
                <input type="text" name="rc-firstname-field" id="rc-field-firstname" value="<?php echo esc_attr($_REQUEST['rc-firstname-field']); ?>"/>
                <span>First</span>
            </label>
            <label>
                <input type="text" name="rc-lastname-field" value="<?php echo esc_attr($_REQUEST['rc-lastname-field']); ?>"/>
                <span>Last</span>
            </label>
        </div>
    </div>
    <div class="rc-field-columns">
        <div class="rc-field">
            <label>Birthday <abbr class="required" title="required">*</abbr><br>
                <input type="text" name="rc-birthday-field" id="rc-birthday-field" value="<?php echo esc_attr($_REQUEST['rc-birthday-field']); ?>"/>
            </label>
        </div>
        <div class="rc-field">
            <label>Shirt size <abbr class="required" title="required">*</abbr><br>
                <select name="rc-shirt-field">
                    <option value="">Choose an option</option>
                    <option value="Youth S" <?php selected($_REQUEST['rc-shirt-field'], "Youth S"); ?>>Youth S</option>
                    <option value="Youth M" <?php selected($_REQUEST['rc-shirt-field'], "Youth M"); ?>>Youth M</option>
                    <option value="Youth L" <?php selected($_REQUEST['rc-shirt-field'], "Youth L"); ?>>Youth L</option>
                    <option value="Youth XL" <?php selected($_REQUEST['rc-shirt-field'], "Youth XL"); ?>>Youth XL</option>
                    <option value="Adult S" <?php selected($_REQUEST['rc-shirt-field'], "Adult S"); ?>>Adult S</option>
                    <option value="Adult M" <?php selected($_REQUEST['rc-shirt-field'], "Adult M"); ?>>Adult M</option>
                    <option value="Adult L" <?php selected($_REQUEST['rc-shirt-field'], "Adult L"); ?>>Adult L</option>
                    <option value="Adult XL" <?php selected($_REQUEST['rc-shirt-field'], "Adult XL"); ?>>Adult XL</option>
                </select>
            </label>
        </div>
    </div>
    <div class="rc-field">
        <label>Please list any medical problems, medications, or special information we should know about your camper <abbr class="required" title="required">*</abbr><br>
            <textarea name="rc-medical-field"><?php echo esc_html($_REQUEST['rc-medical-field']); ?></textarea>
        </label>
    </div>
    <div class="rc-field">
        <strong>Have you attended Runner's Camp before? <abbr class="required" title="required">*</abbr></strong>
        <div class="rc-field-attended">
            <label><input type="radio" name="rc-attended-field" value="Yes" <?php checked($_REQUEST['rc-attended-field'], "Yes"); ?> /> Yes</label>
            <label><input type="radio" name="rc-attended-field" value="No" <?php checked($_REQUEST['rc-attended-field'], "No"); ?> /> No</label>
        </div>
    </div>
	<?php
}


function rc_woocommerce_before_add_to_cart_button() {
	if (isset($_REQUEST['edit_cart_item']) && $_REQUEST['edit_cart_item']) {
		?>
        <a href="<?php echo wc_get_cart_url(); ?>">
            <button type="button" class="button cancel-edit">Cancel Edit</button>
        </a>
		<?php
	} else {
		// only show the Save and Add Camper option if there are less than 4 campers already in the cart
		$cart_count = rc_count_camps_campers_in_cart();
		if ($cart_count["campers"] < 4) {
			?>
            <button type="submit" class="single_add_to_cart_button button alt" name="action" value="save-and-add">Add Additional Campers</button>
			<?php
		}
	}
}

function rc_woocommerce_after_add_to_cart_button() {
	?>
    <input type="hidden" name="edit_cart_item" value="<?php echo esc_attr($_REQUEST['edit_cart_item']); ?>"/>
    </div>
	<?php
}

function rc_woocommerce_product_single_add_to_cart_text() {
	if (isset($_REQUEST['edit_cart_item']) && $_REQUEST['edit_cart_item']) {
		return "Save Camper Info";
	}

	return "Continue to Checkout";
}
