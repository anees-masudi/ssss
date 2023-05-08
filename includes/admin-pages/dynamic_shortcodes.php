<?php

defined( 'ABSPATH' ) || exit;

function rc_shortcodes_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

// add error/update messages
if ( isset( $_GET['settings-updated'] ) ) {
    add_settings_error( 'rc_messages', 'rc_message', 'Settings Saved', 'updated' );
}
settings_errors( 'rc_messages' );

?>
    <div class="wrap">
        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
        <form method="post" action="options.php" class="rc-dy_shortcodes-form">
			<?php
			settings_fields( "rc_dy_shortcodes_option_group" );
			do_settings_sections( "rc_dy_shortcodes" );
			submit_button();
			?>
        </form>
    </div>
	<?php



}


function rc_dy_shortcodes_settings() {
    add_settings_section( "rc_dy_shortcodes_list", "Shortcodes", null, "rc_dy_shortcodes" ); // contact info
	add_settings_section( "rc_dy_shortcodes_general", "Contact", null, "rc_dy_shortcodes" ); // contact info
    add_settings_section( "rc_dy_shortcodes_map", "Location/Map", null, "rc_dy_shortcodes" ); // maps
    add_settings_section( "rc_dy_shortcodes_camp_address", "Camp Address", null, "rc_dy_shortcodes" ); // Camp Address

    
    add_settings_field( "rc_shortcodes_list", "List of shortcodes", "rc_shortcodes_list_display", "rc_dy_shortcodes", "rc_dy_shortcodes_list" ); // shortcodes list

    add_settings_field( "rc_website_content", "Website Contact Information", "rc_website_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_general" ); // contact info


    add_settings_field( "rc_camp_1", "Camp1", "rc_camp1_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_map" ); // camp1
    add_settings_field( "rc_camp_2", "Camp2", "rc_camp2_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_map" ); // camp2
    add_settings_field( "rc_camp_3", "Camp3", "rc_camp3_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_map" ); // camp3

    add_settings_field( "rc_camp_name_1", "Name", "rc_campname1_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp1 Name
    add_settings_field( "rc_camp_address_1", "Address", "rc_address1_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp1 Address

    add_settings_field( "rc_camp_name_2", "Name", "rc_campname2_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp2 Name
    add_settings_field( "rc_camp_address_2", "Address", "rc_address2_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp2 Address

    add_settings_field( "rc_camp_name_3", "Name", "rc_campname3_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp3 Name
    add_settings_field( "rc_camp_address_3", "Address", "rc_address3_content_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp3 Address

    add_settings_field( "rc_camp_available_dates", "Camp Dates", "rc_camp_available_dates_display", "rc_dy_shortcodes", "rc_dy_shortcodes_camp_address" ); // camp dates


    register_setting( "rc_dy_shortcodes_option_group", "rc_shortcodes_list" );// contact info

    register_setting( "rc_dy_shortcodes_option_group", "rc_website_content" );// contact info


    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_1" );// camp1 map
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_2" );// camp2 map
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_3" );// camp3 map

    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_name_1" );// camp1 Name
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_name_2" );// camp2 Name
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_name_3" );// camp2 Name
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_address_1" );// camp1 Address
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_address_2" );// camp2 Address
    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_address_3" );// camp2 Address

    register_setting( "rc_dy_shortcodes_option_group", "rc_camp_available_dates" );// camp dates



}
add_action( "admin_init", "rc_dy_shortcodes_settings" );


//shortcodes list
function rc_shortcodes_list_display(){
    ?>
   
<p><b>Site name: </b> [rc_sitename]</p>

<p><b>Contact information: </b> [rc_contact_information]</p>

<p><b>Location/Map Camp1: </b> [rc_location_map_camp1]</p>

<p><b>Location/Map Camp2: </b> [rc_location_map_camp2]</p>

<p><b>Location/Map Camp3: </b> [rc_location_map_camp3]</p>

<p><b>Price and discounted rates: </b> [rc_rates]</p>

<p><b>Camp1 Name: </b> [rc_camp1_name]</p>

<p><b>Camp1 Address: </b> [rc_camp1_address]</p>

<p><b>Camp2 Name: </b> [rc_camp2_name]</p>

<p><b>Camp2 Address: </b> [rc_camp2_address]</p>

<p><b>Camp3 Name: </b> [rc_camp3_name]</p>

<p><b>Camp3 Address: </b> [rc_camp3_address]</p>

<p><b>Training Dates: </b> [rc_camp_dates]</p>

<p><b>Training Dates: </b> [rc_traning_camp_dates]</p>

<p><b>For Camp Track Meet 1: </b> [rc_camp_title_meet1] , [rc_camp_events_meet1] , [rc_camp_maxeventspercamper_meet1]</p>

<p><b>For Camp Track Meet 2: </b> [rc_camp_title_meet2] , [rc_camp_events_meet2] , [rc_camp_maxeventspercamper_meet2]</p>
<br>
<small>Copy and paste these shortcode wherever you want it to appear.</small>

<?php
}
//contact info
function rc_website_content_display() {
	$contact_information = get_option( 'rc_website_content' );

   // wp_editor(esc_attr( $contact_information ), 'rc_website_content'); 
   wp_editor( $contact_information, 'rc_website_content', array(
    'textarea_name' => 'rc_website_content',
    'media_buttons' => false,
    'textarea_rows' => 10,
    'teeny' => true,
    'quicktags' => true,
    'tinymce' => true,
    'wpautop' => false,
  ) );
	?>
    
    <p>
       
        <small>Enter a contact information of your site.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_contact_information]</small>
    </p>
   
	<?php
}

//camp dates
function rc_camp_available_dates_display() {
   $camp_dates = get_option( 'rc_camp_available_dates' );
    wp_editor( $camp_dates, 'rc_camp_available_dates', array(
        'textarea_name' => 'rc_camp_available_dates',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => true,
        'wpautop' => false,
      ) );
        ?>
        
        <p>
           
            <small>Enter a camp dates.</small>
            <p>Copy this shortcode and paste it where you would like to show this.</p>
            <small>[rc_traning_camp_dates]</small>
        </p>
       
        <?php
}

//camp1 map
function rc_camp1_content_display() {
	$camp1 = get_option( 'rc_camp_1' );
    //wp_editor(esc_attr( $camp1 ), 'rc_camp_1'); 
    wp_editor($camp1, 'rc_camp_1', array(
        'textarea_name' => 'rc_camp_1',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => false,
        //'wpautop' => false,
      )  );
	?>
    
    <p>
   
        <small>Enter a camp address location.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_location_map_camp1]</small>
       
    </p>     
   
   
	<?php
}

//camp2 map
function rc_camp2_content_display() {

    $camp2 = get_option( 'rc_camp_2' );
   // wp_editor(esc_attr( $camp2 ), 'rc_camp_2'); 
    wp_editor( $camp2, 'rc_camp_2', array(
        'textarea_name' => 'rc_camp_2',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => false,
       // 'wpautop' => false,
      ) );
	?>

    <p>
 
        <small>Enter a camp address location.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_location_map_camp2]</small>
    </p>
   
	<?php
}

//camp3 map
function rc_camp3_content_display() {

    $camp3 = get_option( 'rc_camp_3' );
   // wp_editor(esc_attr( $camp3 ), 'rc_camp_3'); 
    wp_editor( $camp3, 'rc_camp_3', array(
        'textarea_name' => 'rc_camp_3',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => false,
       // 'wpautop' => false,
      ) );
    ?>


        <small>Enter a camp address location.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_location_map_camp3]</small>
    </p>
   
    <?php
}

//camp1 Name
function rc_campname1_content_display() {
	$rc_camp_name_1 = get_option( 'rc_camp_name_1' );
	?>
    
    <p>
        <label>Camp 1 Name:<br><input type="text" name="rc_camp_name_1" size="50"
                                          value="<?php echo $rc_camp_name_1 ?>"/></label><br>
        <small>Enter a camp name.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_camp1_name]</small>
       
    </p>     
   
   
	<?php
}

//camp1 Address
function rc_address1_content_display() {
	$campaddress_1 = get_option( 'rc_camp_address_1' );
?>
   


<?php
   // wp_editor(esc_attr( $campaddress_1 ), 'rc_camp_address_1'); 
    wp_editor( $campaddress_1, 'rc_camp_address_1', array(
        'textarea_name' => 'rc_camp_address_1',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => true,
        'wpautop' => false,
      ) );
	?>
    
    <p>
       
        <small>Enter a camp address.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_camp1_address]</small>
     
    </p>     
   
   
	<?php
}

//camp2 Name
function rc_campname2_content_display() {
	$rc_camp_name_2 = get_option( 'rc_camp_name_2' );

	?>
    
    <p>
        <label>Camp 2 Name:<br><input type="text" name="rc_camp_name_2" size="50"
                                          value="<?php echo $rc_camp_name_2 ?>"/></label><br>
        <small>Enter a camp name.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_camp2_name]</small>
       
    </p>     
   
   
	<?php
}
//camp2 Address
function rc_address2_content_display() {
	$campaddress_2 = get_option( 'rc_camp_address_2' );
    //wp_editor(esc_attr( $campaddress_2 ), 'rc_camp_address_2'); 
    wp_editor( $campaddress_2, 'rc_camp_address_2', array(
        'textarea_name' => 'rc_camp_address_2',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => true,
        'wpautop' => false,
      ) );
	?>
    
    <p>
       
        <small>Enter a camp address.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_camp2_address]</small>
    </p>     
   
   
	<?php
}

//camp3 Name
function rc_campname3_content_display() {
    $rc_camp_name_3 = get_option( 'rc_camp_name_3' );
    ?>
    
    <p>
        <label>Camp 3 Name:<br><input type="text" name="rc_camp_name_3" size="50"
                                          value="<?php echo $rc_camp_name_3 ?>"/></label><br>
        <small>Enter a camp name.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_camp3_name]</small>
       
    </p>     
   
   
    <?php
}
//camp3 Address
function rc_address3_content_display() {
    $campaddress_3 = get_option( 'rc_camp_address_3' );
   // wp_editor(esc_attr( $campaddress_3 ), 'rc_camp_address_3'); 

    wp_editor( $campaddress_3, 'rc_camp_address_3', array(
        'textarea_name' => 'rc_camp_address_3',
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => true,
        'tinymce' => true,
        'wpautop' => false,
      ) );
    ?>
    
    <p>
     
        <small>Enter a camp address.</small>
        <p>Copy this shortcode and paste it where you would like to show this.</p>
        <small>[rc_camp3_address]</small>
    </p>     
   
   
    <?php
}

