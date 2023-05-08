<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rc_enqueue_scripts() {

	wp_enqueue_style( 'runnerscamp-registration', RC_URL . '/assets/rc-registration.css', array(), RC_VERSION );
	wp_enqueue_script( 'runnerscamp-registration', RC_URL . '/assets/rc-registration.js', array( 'jquery', 'jquery-ui-datepicker' ), RC_VERSION );
	
	if ( get_the_ID() == get_option( "rc_product_id" ) ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_register_style( 'jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), RC_VERSION );
		wp_enqueue_style( 'jquery-ui-style' );
		
		$camps = get_option( "rc_camps" ,[]);
		if ( isset( $camps['first_day'] ) ) {
			$first_day = $camps['first_day'];
		}
		if ( isset( $camps['allow_under_six'] ) ) {
			$last_day = $camps['allow_under_six'];
		} elseif ( isset( $camps['last_day'] ) ) {
			$last_day = $camps['last_day'];
		}
		if ( isset( $first_day ) && isset( $last_day ) ) {
			$first_day = date_create( $first_day );
			$last_day  = date_create( $last_day );
			
			date_sub( $first_day, date_interval_create_from_date_string( '13 years' ) );
			date_sub( $last_day, date_interval_create_from_date_string( '5 years' ) );
			
			wp_localize_script( 'runnerscamp-registration', 'rc', array(
				'first_day' => $first_day->format( 'Y' ),
				'last_day'  => $last_day->format( 'Y' ),
			) );
		}
	}
}

add_action( 'wp_enqueue_scripts', 'rc_enqueue_scripts', 10000 );


function rc_admin_enqueue_scripts() {
	wp_enqueue_style( 'runnerscamp-registration', RC_URL . '/assets/rc-registration-admin.css', array(), RC_VERSION );
	
	global $pagenow;
	if ( ( $pagenow == 'admin.php' ) && in_array( $_GET['page'], array( 'rc_registrations', 'rc_waitlist' ) ) ) {
		wp_enqueue_script( 'runnerscamp-registration', RC_URL . '/assets/rc-registration-admin.js', array( 'jquery' ), RC_VERSION );
		wp_localize_script( 'runnerscamp-registration', 'rc', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}
}

add_action( 'admin_enqueue_scripts', 'rc_admin_enqueue_scripts' );