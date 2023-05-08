<?php

defined( 'ABSPATH' ) || exit;

// replace WooCommerce Waitlist's tab template
function rc_wcwl_include_path_admin_panel_tabs( $what ) {
	return RC_PATH . '/templates/wcwl-panel-tabs.php';
}

add_filter( "wcwl_include_path_admin_panel_tabs", "rc_wcwl_include_path_admin_panel_tabs" );


function rc_wcwl_variation_tab_title( $variation_title, $variation_id ) {
	$variation       = wc_get_product( $variation_id );
	$variation_title = preg_replace( "(#[0-9]+ )", "", $variation_title );
	$variation_title = str_replace( "</span>", " on waitlist, " . $variation->get_stock_quantity() . " available</span>", $variation_title );

	return $variation_title;
}

add_filter( 'wcwl_variation_tab_title', 'rc_wcwl_variation_tab_title', 10, 2 );

function rc_waitlist_page() {
	?>
	<div class="wrap rc-waitlists-page">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p>After cancelling an order, you must manually increase the available camp spaces and notify the next waitlisted person.</p>
		<?php

		$waitlist_admin = new Pie_WCWL_Admin_Init();
		$waitlist_admin->load_waitlist_from_product_id( get_option( "rc_product_id" ) );

		do_action( "woocommerce_product_data_panels" );
		do_action( 'admin_enqueue_scripts' );
		?>
	</div>
	<?php
}


function rc_format_stock_info( $info ) {
	ob_start();
	?>
	<table class="rc-stock-table">
		<tr>
			<th></th>
			<th>Age 6</th>
			<th>Age 7</th>
			<th>Age 8</th>
			<th>Age 9</th>
			<th>Age 10</th>
			<th>Age 11</th>
			<th>Age 12</th>
		</tr>
		<tr>
			<th>Boys</th>
			<?php
			$gender = 'Boy';
			foreach ( $info as $row ) {
				if ( $gender == 'Boy' && $row['gender'] == 'Girl' ) {
					echo '</tr><tr><th>Girls</th>';
				}
				$gender = $row['gender'];

				$stock    = isset( $row['stock'] ) ? $row['stock'] : 0;
				$waitlist = isset( $row['waitlist'] ) ? $row['waitlist'] : 0;
				echo '<td>' . $stock . ' <span>available</span><br>' . $waitlist . ' <span>on waitlist</span></td>';
			}

			?>
		</tr>
	</table>
	<?php
	return ob_get_clean();
}

function rc_get_stock_info( $camp ) {
	global $wpdb;

	$product_id = get_option( 'rc_product_id' );
	$metakey1   = '_stock';
	$metakey2   = ( null === WCWL_SLUG ) ? '_' . WCWL_SLUG . '_count' : '_woocommerce_waitlist_count';

	$stock_quantity = $wpdb->get_results( $wpdb->prepare( "
SELECT pm2_gender.meta_value AS 'gender', pm3_age.meta_value AS 'age', ROUND(pm_stock.meta_value,0) AS 'stock', ROUND(pm_waitlist.meta_value,0) AS 'waitlist'
FROM {$wpdb->prefix}posts AS p
STRAIGHT_JOIN {$wpdb->prefix}postmeta AS pm_stock    ON p.ID = pm_stock.post_id    AND pm_stock.meta_key = '{$metakey1}'
LEFT JOIN {$wpdb->prefix}postmeta     AS pm_waitlist ON p.ID = pm_waitlist.post_id AND pm_waitlist.meta_key = '{$metakey2}'
STRAIGHT_JOIN {$wpdb->prefix}postmeta AS pm_camp     ON p.ID = pm_camp.post_id     AND pm_camp.meta_key = 'attribute_camp'
STRAIGHT_JOIN {$wpdb->prefix}postmeta AS pm2_gender     ON p.ID = pm2_gender.post_id     AND pm2_gender.meta_key = 'attribute_gender'
STRAIGHT_JOIN {$wpdb->prefix}postmeta AS pm3_age     ON p.ID = pm3_age.post_id     AND pm3_age.meta_key = 'attribute_age'
WHERE p.post_type = 'product_variation'
AND p.post_status = 'publish'
AND p.post_parent = '{$product_id}'
AND pm_camp.meta_value = '{$camp}'
ORDER BY pm2_gender.meta_value, ABS(pm3_age.meta_value)
        ", $metakey1, $metakey2, $product_id ), ARRAY_A );

	return $stock_quantity;
}

