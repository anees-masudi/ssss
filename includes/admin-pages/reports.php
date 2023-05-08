<?php

defined( 'ABSPATH' ) || exit;

// page to list reports
function rc_reports_page() {
	?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Note: You can generate CSV exports from the <a
                    href="<?php echo admin_url( 'admin.php?page=rc_registrations' ); ?>">Registrations</a> page.</p>
        <h2>Carpool PDF</h2>
		<?php rc_generate_download_links( 'carpool', 'Carpool' ); ?>
        <h2>Age Group Report PDF</h2>
		<?php rc_generate_download_links( 'age_group_report', 'Age group report' ); ?>
        <h2>Roster PDF</h2>
		<?php rc_generate_download_links( 'roster', 'Roster' ); ?>
        <h2>Parent/Guardian PDF</h2>
		<?php rc_generate_download_links( 'parent', 'Parent/guardian report' ); ?>
    </div>
	<?php
}

add_action( "rc_reports_page_action", "rc_reports_page" );

function rc_generate_download_links( $report, $title ) {
	$rc_camps   = get_option( 'rc_camps', [] );
	$both_camps = ( isset( $rc_camps["camp2"] ) && $rc_camps["camp2"] == "yes" );

	$args = array(
		'rc-export' => 'reports',
		'report'    => $report
	);
	if ( $both_camps ) {
		$args['camp'] = 'Camp 1';
	}

	$camp1_export_url = admin_url( 'admin.php' . add_query_arg( $args, null ) );

	ob_start();
	?>
    <p><a href="<?php echo $camp1_export_url; ?>"><?php echo $title; ?><?php if ( $both_camps ) {
				echo ': Camp 1';
			} ?></a></p>
	<?php

	// if second camp is enabled, show the second link
	if ( $both_camps ) {
		$args             = array(
			'rc-export' => 'reports',
			'report'    => $report,
			'camp'      => 'Camp 2'
		);
		$camp2_export_url = admin_url( 'admin.php' . add_query_arg( $args, null ) );
		?>
        <p><a href="<?php echo $camp2_export_url; ?>"><?php echo $title; ?>: Camp 2</a></p>
		<?php
	}
	echo ob_get_clean();
}

function rc_download_report() {
	if ( ! isset( $_GET['rc-export'] ) || $_GET['rc-export'] != 'reports' || ! isset( $_GET["report"] ) ) {
		return;
	}

	require_once( RC_PATH . '/includes/admin-pages/includes/class-rc-registrations-list.php' );
	require_once( RC_PATH . '/includes/admin-pages/includes/class-rc-pdf-exports.php' );

	$rc_camps = get_option( 'rc_camps', [] );
	$camp     = null;
	if ( isset( $rc_camps["camp2"] ) && $rc_camps["camp2"] == "yes" ) {
		$camp = ( $_GET['camp'] == 'Camp 1' ) ? 'Camp 1' : 'Camp 2';
	}

	$list = new RC_PDF_Exports();
	if ( $_GET["report"] == 'carpool' ) {
		$list->generate_carpool( $camp );
	} elseif ( $_GET["report"] == 'age_group_report' ) {
		$list->generate_age_group_report( $camp );
	} elseif ( $_GET["report"] == 'roster' ) {
		$list->generate_roster( $camp );
	} elseif ( $_GET["report"] == 'parent' ) {
		$list->generate_parent_report( $camp );
	}
}

add_action( 'admin_init', 'rc_download_report' );
