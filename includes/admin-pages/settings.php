<?php

defined( 'ABSPATH' ) || exit;

function rc_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// removes the max-width on the teeny mce body
	add_filter( 'teeny_mce_before_init', function ( $mceInit ) {
		$mceInit['content_style'] = empty( $mceInit['content_style'] )
			? 'html .mceContentBody {max-width: none;} '
			: $mceInit['content_style'] . ' html .mceContentBody {max-width: none;} ';

		return $mceInit;
	} );

	// add error/update messages
	if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'rc_messages', 'rc_message', 'Settings Saved', 'updated' );
        rc_set_selected_camp_attributes_and_variations();
	}
	settings_errors( 'rc_messages' );

	?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php" class="rc-settings-form">
			<?php
			settings_fields( "rc_settings_option_group" );
			do_settings_sections( "rc_settings" );
			submit_button();
			?>
        </form>
    </div>
	<?php
}

function rc_register_settings() {
	add_settings_section( "rc_settings_general", "General", null, "rc_settings" );
	add_settings_section( "rc_settings_camps", "Camps", null, "rc_settings" );
	add_settings_section( "rc_settings_discounts", "Discounts", null, "rc_settings" );
	add_settings_section( "rc_settings_events", "Events", null, "rc_settings" );

//     $user_ID = get_current_user_id(); 
//     $user_meta = get_userdata($user_ID);
//      $user_roles = $user_meta->roles;
//     if ( in_array( 'basic_contributor', (array) $user_roles[0] ) ) {
	
//     }
   // else{
        add_settings_field( "rc_product_id", "WooCommerce Product ID", "rc_product_id_display", "rc_settings", "rc_settings_general" );
        register_setting( "rc_settings_option_group", "rc_product_id" );
   // }
    
    
    add_settings_field( "rc_redirect_shop", "Redirect Shop Page?", "rc_redirect_shop_display", "rc_settings", "rc_settings_general" );

	add_settings_field( "rc_multicamp", "Multiple Camps", "rc_multicamp_display", "rc_settings", "rc_settings_camps" );
	add_settings_field( "rc_camps", "Camp Names", "rc_camp_names_display", "rc_settings", "rc_settings_camps" );
	add_settings_field( "rc_camp_dates", "Birthdate Validation", "rc_camp_dates_display", "rc_settings", "rc_settings_camps" );

	add_settings_field( "rc_multicamp", "Multi-Camp Discount", "rc_multicamp_discount_display", "rc_settings", "rc_settings_discounts" );
	add_settings_field( "rc_multicamper", "Multi-Camper Discount", "rc_multicamper_discount_display", "rc_settings", "rc_settings_discounts" );
	add_settings_field( "rc_earlybird", "Earlybird Discount", "rc_earlybird_discount_display", "rc_settings", "rc_settings_discounts" );
	add_settings_field( "rc_sleepybird", "Sleepybird Discount", "rc_sleepybird_discount_display", "rc_settings", "rc_settings_discounts" );

	add_settings_field( "rc_signup", "Signup Form", "rc_signup_display", "rc_settings", "rc_settings_events" );
	add_settings_field( "rc_meets", "Meets", "rc_meets_display", "rc_settings", "rc_settings_events" );

   
	register_setting( "rc_settings_option_group", "rc_redirect_shop" );
	register_setting( "rc_settings_option_group", "rc_camps" );
	register_setting( "rc_settings_option_group", "rc_discounts" );
	register_setting( "rc_settings_option_group", "rc_events", [ "sanitize_callback" => "rc_events_strip_commas_from_event_names" ] );
}

add_action( "admin_init", "rc_register_settings" );

function rc_set_selected_camp_attributes_and_variations()
{
    $rc_camps = get_option('rc_camps');
    $product_id = get_option('rc_product_id');
    if ( !empty($product_id) )
    {
        $product = wc_get_product( $product_id );        

        $gender = new WC_Product_Attribute();
        $gender->set_name('Gender');
        $gender->set_options([ 'Boy', 'Girl' ]);
        $gender->set_position(0);
        $gender->set_visible(0);
        $gender->set_variation(1);

        $age = new WC_Product_Attribute();
        $age->set_name('Age');
        $age->set_options([ '6', '7', '8', '9', '10', '11', '12' ]);
        $age->set_position(1);
        $age->set_visible(0);
        $age->set_variation(1);

        $camp = new WC_Product_Attribute();
        $camp->set_name('Camp');
        $camp->set_options([ 'Camp 1', 'Camp 2', 'Camp 3' ]);
        $camp->set_position(2);
        $camp->set_visible(0);
        $camp->set_variation(1);

        $product->set_attributes([ $gender, $age, $camp ]);

        $product->save();

        // Setup an array for age groups to create the product variations
        // Assuming ages 6-12 for both geneders and each available camp.
        // We'll check that the variation does not already exist, first.
        $available_variations = $product->get_available_variations();
        $available_variations = array_map(function($value) {
            return $value['attributes'];
        }, $available_variations);

        foreach (['Boy', 'Girl'] as $gender) {
            for ($age = 6; $age <= 12; $age++) {
                if (!rc_check_for_existing_camp_group_variation($available_variations, $age, $gender, 'Camp 1'))
                {
                    rc_create_camp_group_variation($product_id, $age, $gender, 'Camp 1');
                }

                if (isset($rc_camps['camp2']) && $rc_camps['camp2'] == 'yes'
                        && !rc_check_for_existing_camp_group_variation($available_variations, $age, $gender, 'Camp 2')) {
                    rc_create_camp_group_variation($product_id, $age, $gender, 'Camp 2');
                }

                if (isset($rc_camps['camp3']) && $rc_camps['camp3'] == 'yes'
                        && !rc_check_for_existing_camp_group_variation($available_variations, $age, $gender, 'Camp 3')) {
                    rc_create_camp_group_variation($product_id, $age, $gender, 'Camp 3');
                }
            }
        }
    }
}

function rc_create_camp_group_variation($product_id, $age, $gender, $camp)
{
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_attributes(
            [
                'age' => $age,
                'camp' => $camp,
                'gender' => $gender
            ]
        );
        $variation->set_price(90);
        $variation->set_regular_price(90);
        $variation->set_manage_stock(true);
        $variation->set_virtual(true);
        $variation->set_stock_status('outofstock');
        $variation->set_stock_quantity(20);
        $variation->save();

        // Make sure the waitlist is enabled and configured with some defaults.
        // This is a hacky way to do things, but it should work.
        update_post_meta($product_id, 'woocommerce_waitlist_has_dates', true);
        update_post_meta($product_id, 'wcwl_options', serialize([
            'enable_waitlist' => true,
            'enable_stock_trigger' => false,
            'minimum_stock' => '99'
        ]));
}

function rc_check_for_existing_camp_group_variation($available_variations, $age, $gender, $camp)
{
    foreach ($available_variations as $variation) {
        if ($variation['attribute_gender'] == $gender 
            && $variation['attribute_age'] == $age 
            && $variation['attribute_camp'] == $camp) {
            return true;
        }
    }
    return false;
}

function rc_product_id_display() {
	$product_id = get_option( 'rc_product_id' ) ?: '';
   
?>

<p>This is the product ID of the current year's camp registration product. This must be different for each year; do
        not rename and reuse the previous year's product.</p>

 <!-- camps -->
 <label for="products">Choose a Camp:</label> 
           <select name = "rc_product_id" id="e_template" required>
           <option value="<?php echo $product_id ?>" selected  hidden> <?php echo get_the_title( $product_id ) ?></option>
<?php 
$type = 'product'; // your post type
$args=array(
  'post_type' => $type,
  'post_status' => 'publish',
  'product_cat' => 'camp',
);

$my_query = null;
$my_query = new WP_Query($args);
if( $my_query->have_posts() ) {
  while ($my_query->have_posts()) : $my_query->the_post(); 
 ?>
    <option value = "<?php the_ID() ?>"  name = "rc_product_id" ><?php echo the_title() ?></option>
<?php
  

     endwhile;
 }
 
wp_reset_query();
?>
    </select>

<!-- camps end-->



<?php

}

function rc_redirect_shop_display() {
	$redirect_shop = get_option( 'rc_redirect_shop' ) ?: '';
	?>
    <p>This prevents users from being shown a storefront with only one product for sale. Do not enable this setting if
        you sell other WooCommerce products.</p>
    <p>
        <label><input type="checkbox" name="rc_redirect_shop" value="yes" <?php checked( "yes", $redirect_shop ); ?> />
            Redirect the WooCommerce shop page to the registration product page</label>
    </p>
	<?php
}

function rc_multicamp_display() {
	$rc_camps = get_option( 'rc_camps', [] );
	?>
    <p>If multiple camps are enabled, the camp product must have a "Camp" attribute included in its variations with values
        for each camp, e.g. "Camp 1", "Camp 2", and "Camp 3".</p>
    <p><label><input type="checkbox" name="rc_camps[camp2]"
                     value="yes" <?php checked( $rc_camps['camp2'], 'yes' ); ?> /> Enable Camp 2</label></p>
    <p><label><input type="checkbox" name="rc_camps[camp3]"
                     value="yes" <?php checked( $rc_camps['camp3'], 'yes' ); ?> /> Enable Camp 3</label></p>
	<?php
}

function rc_camp_names_display() {
	$rc_camps = get_option( 'rc_camps', [] );
	?>
    <p>This will be shown instead of the generic Camp 1/Camp 2/Camp 3 labels during registration and can be used to remind
        users of the camp dates.</p>
    <p><label>Camp 1 name:<br><input type="text" name="rc_camps[camp1_name]"
                                     value="<?php echo $rc_camps['camp1_name']; ?>"/></label></p>
    <p><label>Camp 2 name:<br><input type="text" name="rc_camps[camp2_name]"
                                     value="<?php echo $rc_camps['camp2_name']; ?>"/></label></p>
    <p><label>Camp 3 name:<br><input type="text" name="rc_camps[camp3_name]"
                                     value="<?php echo $rc_camps['camp3_name']; ?>"/></label></p>
	<?php
}

function rc_camp_dates_display() {
	$rc_camps = get_option( 'rc_camps', [] );
	?>
    <p>If these fields are filled, campers will only be able to register for an age group if they will be the correct
        age for at least one day between the first and last dates given below.</p>

    <p>
        <label>First day of camp:<br><input type="text" name="rc_camps[first_day]"
                                            value="<?php echo $rc_camps['first_day']; ?>"/></label><br>
        <small>Enter a date in YYYY-MM-DD format.</small>
    </p>
    <p>
        <label>Last day of camp:<br><input type="text" name="rc_camps[last_day]"
                                           value="<?php echo $rc_camps['last_day']; ?>"/></label><br>
        <small>Enter a date in YYYY-MM-DD format.</small>
    </p>
    <p>
        <label>Add a grace period to extend these dates and allow some flexibility:<br>
            <input type="text" name="rc_camps[grace_period]" value="<?php echo $rc_camps['grace_period']; ?>"/>
            days</label>
    </p>
    <p>
        <label>Allow campers under six if they will turn six by:<br>
            <input type="text" name="rc_camps[allow_under_six]"
                   value="<?php echo $rc_camps['allow_under_six']; ?>"/></label><br>
        <small>Enter a date in YYYY-MM-DD format.</small>
    </p>
	<?php
}

function rc_multicamp_discount_display() {
	$discounts = get_option( 'rc_discounts', [] );
	?>
    <p>This is a flat discount applied if both camps are represented in the cart during checkout.</p>
    <p>
        <label>Discount amount:<br><input type="number" name="rc_discounts[multicamp]"
                                          value="<?php echo $discounts['multicamp']; ?>"/></label><br>
        <small>Enter zero to disable.</small>
    </p>
	<?php
}

function rc_multicamper_discount_display() {
	$discounts = get_option( 'rc_discounts', [] );
	?>
    <p>This is a per-camper discount applied after at least two campers have been registered. Campers are distinguished
        based on their first and last names.</p>
    <p>
        <label>Discount amount:<br><input type="number" name="rc_discounts[multicamper]"
                                          value="<?php echo $discounts['multicamper']; ?>"/> per additional
            camper</label><br>
        <small>Enter zero to disable.</small>
    </p>
    <p><label><input type="checkbox" name="rc_discounts[multicamper_include_first]"
                     value="yes" <?php checked( $discounts['multicamper_include_first'], 'yes' ); ?> /> Include the
            first camper in
            discount calculations <small>(The discount will be [discount amount] &times [# campers] instead of [discount
                amount] &times [# campers &ndash; 1])</small>.</label></p>
	<?php
}


function rc_earlybird_discount_display() {
	$discounts = get_option( 'rc_discounts', [] );
	?>
    <p>This discount is applied to every registration sold before the given date.</p>
    <p>
        <label>Discount amount:<br><input type="number" name="rc_discounts[earlybird_value]"
                                          value="<?php echo $discounts['earlybird_value']; ?>"/></label><br>
        <small>Enter zero to disable.</small>
    </p>
    <p>
        <label>Last day of discount:<br><input type="text" name="rc_discounts[earlybird_ends]"
                                               value="<?php echo $discounts['earlybird_ends']; ?>"/></label><br>
        <small>Enter a date in YYYY-MM-DD format.</small>
    </p>
	<?php
}

function rc_sleepybird_discount_display() {
	$discounts = get_option( 'rc_discounts', [] );
	?>
    <p>This discount is applied to every registration sold before the given date. If enabled, the sleepybird discount
        begins the day after the earlybird discount ends.</p>
    <p>
        <label>Discount amount:<br><input type="number" name="rc_discounts[sleepybird_value]"
                                          value="<?php echo $discounts['sleepybird_value']; ?>"/></label><br>
        <small>Enter zero to disable.</small>
    </p>
    <p>
        <label>Last day of discount:<br><input type="text" name="rc_discounts[sleepybird_ends]"
                                               value="<?php echo $discounts['sleepybird_ends']; ?>"/></label><br>
        <small>Enter a date in YYYY-MM-DD format.</small>
    </p>
	<?php
}


function rc_signup_display() {
	$events = get_option( 'rc_events', [] );
	?>
    <p>
        <label><input type="checkbox" name="rc_events[show_form]"
                      value="yes" <?php checked( "yes", $events['show_form'] ); ?> /> Enable event signup by displaying
            the form in users' My Account
            dashboards (must enter at least one meet title below)<br>
            <strong>Important:</strong> Be sure to flush permalinks after enabling/disabling! (Go to the <a
                    href="<?php echo admin_url("options-permalink.php"); ?>">Permalinks settings page</a>, scroll down,
            and click Save Changes.)</label>
    </p>
    <p style="margin: 2em 0 0">Instructions, event descriptions, etc to be displayed above the signup form:</p>
	<?php
	$meta_content = wpautop( $events['instructions'], true );
	wp_editor( $meta_content, 'rc_signup_instructions', array(
		'media_buttons' => false,
		'textarea_name' => 'rc_events[instructions]',
		'teeny'         => true
	) );
}


function rc_meets_display() {
	$events = get_option( 'rc_events', [] );
	?>
    <p>You can add events for up to two meets.</p>
    <div class="rc-settings-meets">
        <div>
            <h4>Meet 1</h4>
            <p>
                <label>Title (leave blank to disable this meet):<br><input type="text" name="rc_events[meet1_title]"
                                                                           value="<?php echo $events['meet1_title']; ?>"/></label>
            </p>
            <p>
                <label>Events (enter one per line):<br><textarea
                            name="rc_events[meet1_events]"><?php echo $events['meet1_events']; ?></textarea></label>
            </p>
            <p>
                <label>Max events per camper:<br><input type="number" name="rc_events[meet1_max]"
                                                        value="<?php echo $events['meet1_max']; ?>"/></label><br>
                <small>Enter zero to allow unlimited events.</small>
            </p>
        </div>
        <div>
            <h4>Meet 2</h4>
            <p>
                <label>Title (leave blank to disable this meet):<br><input type="text" name="rc_events[meet2_title]"
                                                                           value="<?php echo $events['meet2_title']; ?>"/></label>
            </p>
            <p>
                <label>Events (enter one per line):<br><textarea
                            name="rc_events[meet2_events]"><?php echo $events['meet2_events']; ?></textarea></label>
            </p>
            <p>
                <label>Max events per camper:<br><input type="number" name="rc_events[meet2_max]"
                                                        value="<?php echo $events['meet2_max']; ?>"/></label><br>
                <small>Enter zero to allow unlimited events.</small>
            </p>
        </div>
    </div>
	<?php
}

function rc_events_strip_commas_from_event_names( $input ) {
	// strips commas from meet event names because I'm storing campers' event selections as comma-separated lists in the db, against everyone's advice
	foreach ( [ 'meet1', 'meet2' ] as $meet ) {
		if ( isset( $input[ $meet . '_events' ] ) ) {
			$input[ $meet . '_events' ] = str_replace( ",", "", $input[ $meet . '_events' ] );
		}
	}

	// if neither meet has a title, don't allow the form to show
	if ( ! $input['meet1_title'] && ! $input['meet2_title'] ) {
		unset( $input['show_form'] );
	}

	return $input;
}
