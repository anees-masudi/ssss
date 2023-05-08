<?php

defined( 'ABSPATH' ) || exit;

require_once( RC_PATH . '/includes/admin-pages/includes/class-rc-registrations-list.php' );

function rc_registrations_page() {
	global $registrationListTable;
	$export_url_args = array(
		'page'         => 'rc_registrations',
		'rc-export'    => 'registrations',
		'order_status' => empty( $_GET['order_status'] ) ? null : $_GET['order_status'],
		'camp'         => empty( $_GET['camp'] ) ? null : $_GET['camp'],
		'gender'       => empty( $_GET['gender'] ) ? null : $_GET['gender'],
		'age'          => empty( $_GET['age'] ) ? null : $_GET['age'],
	);
	$export_url      = add_query_arg( $export_url_args, admin_url( 'admin.php' ) );
	?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Click "Screen Options" in the top right corner of the page to toggle display of each column.</p>
        <p><a href="<?php echo $export_url ?>">Download results as CSV</a> (includes applied filters and visible
            columns)</p>
			<!-- gl start -->
		<?php $site_url = get_site_url(); 
			echo '<p><a href="'.$site_url.'/user-dashboard/" target="_blank">User End Dashboard</a></p>'; ?>

			<!-- gl end -->
        <form method="get" action="<?php echo admin_url( "admin.php?page=rc_registrations" ); ?>">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
			<?php
			wp_nonce_field( 'rc_save_camper_group', '_nonce_update_order_meta', false );
			$registrationListTable->prepare_items();
			$registrationListTable->display();
			?>
        </form>
    </div>
	<?php
}


function rc_download_registrations_csv() {
	if ( ! isset( $_GET['rc-export'] ) || $_GET['rc-export'] != 'registrations' ) {
		return;
	}
	set_current_screen( "toplevel_page_rc_registrations" );
	require_once( RC_PATH . '/includes/admin-pages/includes/class-rc-registrations-list.php' );
	$list = new RC_Registrations_List();
	$list->export_as_csv();
}

add_action( 'admin_init', 'rc_download_registrations_csv' );


/*
 * Add customizable columns to the Registrations page
 */

function rc_registrations_list_add_option() {
	global $registrationListTable;
	$registrationListTable = new RC_Registrations_List();

	/* TODO: allow custom per page
	$option = 'per_page';
	$args   = array(
		'label'   => 'Number of items per page:',
		'default' => 20,
		'option'  => 'per_page'
	);
	add_screen_option( $option, $args );
    */
}

add_action( "load-toplevel_page_rc_registrations", 'rc_registrations_list_add_option' );


function cmi_set_screen_options( $status, $option, $value ) {
	if ( 'cmi_show_columns' == $option ) {
		$value = $_POST['cmi_columns'];
	}

	return $value;
}

//add_filter('set-screen-option', 'cmi_set_screen_options', 11, 3);
