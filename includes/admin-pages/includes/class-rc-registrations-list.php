<?php

defined( 'ABSPATH' ) || exit;

require_once( RC_PATH . '/includes/admin-pages/includes/class-rc-list-table.php' );

class RC_Registrations_List extends RC_List_Table {
	public $events, $camps;

	function __construct() {
		$this->events = get_option( 'rc_events', [] );
		$this->camps  = get_option( 'rc_camps', [] );
		parent::__construct( [
			'singular' => 'Registration',
			'plural'   => 'Registrations',
			'ajax'     => false,
		] );
	}

	/*
	 * Specify the columns to be rendered
	 */

	function get_columns() {
		$columns = array(
			'rc_firstname'    => 'First Name',
			'rc_lastname'     => 'Last Name',
			'camp'            => 'Camp',
			'gender'          => 'Gender',
			'age'             => 'Age',
			'group'           => 'Group',
			'rc_birthday'     => 'Birthday',
			'rc_shirt'        => 'Shirt Size',
			'rc_medical'      => 'Medical Info',
			'rc_attended'     => 'Attended Before',
			'rc_code'         => 'Code',
			'meet1'           => 'Events: ' . $this->events["meet1_title"],
			'meet2'           => 'Events: ' . $this->events["meet2_title"],
			'billing_name'    => 'Parent/Guardian',
			'work_phone'      => 'Work Phone',
			'cell_phone'      => 'Cell Phone',
			'email'           => 'Email',
			'emergency_name'  => 'Emergency Contact',
			'emergency_phone' => 'Emergency Phone',
			'order_id'        => 'Order ID',
			'order_status'    => 'Order Status',
		);
		if ( empty( $this->camps["camp2"] ) || $this->camps["camp2"] != "yes" ) {
			unset( $columns['camp'] );
		}
		if ( empty( $this->events["meet1_title"] ) ) {
			unset( $columns['meet1'] );
		}
		if ( empty( $this->events["meet2_title"] ) ) {
			unset( $columns['meet2'] );
		}

		return $columns;
	}


	/*
	 * Specify the column content
	 */

	function column_default( $item, $column_name, $exporting_as_csv = false ) {
		switch ( $column_name ) {
			case 'meet1':
				return $item['meet1_events'];
			case 'meet2':
				return $item['meet2_events'];
			case 'order_status':
				return $exporting_as_csv
					? wc_get_order_status_name( $item['order_status'] )
					: wc_get_order_status_name( $item['order_status'] ) . '<br><a href="' . admin_url( 'post.php?post=' . $item['order_id'] . '&action=edit' ) . '">View/Edit</a>';
			case 'group':
				return $exporting_as_csv
					? $item[ $column_name ]
					: '<div class="rc_group_wrapper"><input type="text" name="rc_group[' . $item['order_item_id'] . ']" data-order-item-id="' . $item['order_item_id'] . '" value="' . $item[ $column_name ] . '" /></div>';
			case 'emergency_name':
				return ( $name = get_post_meta( $item['order_id'], 'emergency_contact', true ) )
					? $name . '<br><em>' . get_post_meta( $item['order_id'], 'emergency_contact_relationship', true ) . '</em>'
					: '';
			case 'billing_name':
				$order = wc_get_order( $item['order_id'] );

				return $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			case 'emergency_phone':
				return $this->format_phone( get_post_meta( $item['order_id'], 'emergency_contact_phone', true ) );
			case 'work_phone':
				$order = wc_get_order( $item['order_id'] );

				return $this->format_phone( $order->get_billing_phone() );
			case 'cell_phone':
				return $this->format_phone( get_post_meta( $item['order_id'], 'billing_phone_cell', true ) );
			case 'email':
				$order = wc_get_order( $item['order_id'] );

				return $order->get_billing_email();
			default:
				return empty( $item[ $column_name ] )
					? ''
					: stripslashes( $item[ $column_name ] );
		}
	}


	/*
	 * the column output
	 */

	function prepare_items() {

		// prevent the nonce referrer field from growing exponentially
		$_SERVER['REQUEST_URI'] = remove_query_arg( '_wp_http_referer', $_SERVER['REQUEST_URI'] );

		// how many records per page to show
		$per_page = 30;

		// define our column headers: columns to be displayed (slugs & titles), to keep hidden, and that are sortable
		$columns               = $this->get_columns();
		$screen                = get_current_screen();
		$hidden                = get_hidden_columns( $screen );
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// fetches the dataset from the database. This function applies any filters passed in $_REQUEST.
		$data = $this->get_rc_registrations( false );

		// how many items are in our data array
		$total_items = count( $data );

		// WP_List_Table class does not handle pagination, so trim the data to only the current page
		$current_page = $this->get_pagenum();
		$this->items  = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		// register our pagination options & calculations
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	function get_rc_registrations( $args = array() ) {
		global $wpdb;

		/*
		 * list of meta keys we want to pull out of the order item meta table
		 */
		$meta = array(
			'rc_firstname',
			'rc_lastname',
			'gender',
			'age',
			'rc_birthday',
			'rc_shirt',
			'rc_medical',
			'rc_attended',
			'rc_code'
		);
		if ( ! empty( $this->camps["camp2"] ) && $this->camps["camp2"] == "yes" ) {
			$meta[] = 'camp';
		}
		if ( ! empty( $this->events["meet1_title"] ) ) {
			$meta[] = 'meet1_events';
		}
		if ( ! empty( $this->events["meet2_title"] ) ) {
			$meta[] = 'meet2_events';
		}

		/*
		 * set up $args, either prefilled in the $args array or pulled from $_REQUEST
		 */
		foreach ( [ 'camp', 'gender', 'age', 'event', 'order_status' ] as $arg ) {
			if ( ! isset( $args[ $arg ] ) ) {
				$args[ $arg ] = empty( $_REQUEST[ $arg ] ) ? false : $_REQUEST[ $arg ];
			}
		}

		/*
		 * customize the WHERE clause
		 */
		$parameters   = array();
		$where        = "WHERE posts.post_type LIKE 'shop_order' AND item_product.meta_value = '%d'";
		$parameters[] = get_option( 'rc_product_id' );

		if ( $args['camp'] ) {
			$where        .= ' AND camp.meta_value LIKE %s';
			$parameters[] = $args['camp'];
		}
		if ( $args['gender'] ) {
			$where        .= ' AND gender.meta_value LIKE %s';
			$parameters[] = $args['gender'];
		}
		if ( $args['age'] ) {
			$where        .= ' AND age.meta_value LIKE %s';
			$parameters[] = $args['age'];
		}

		if ( $args['event'] && $args['event'] == 'unregistered' ) {
			if ( ! empty( $this->events["meet1_title"] ) ) {
				$where = ' AND meet1_events.meta_value IS NULL';
			}
			if ( ! empty( $this->events["meet2_title"] ) ) {
				$where = ' AND meet2_events.meta_value IS NULL';
			}
		} elseif ( $args['event'] ) {
			$where        .= ' AND (FIND_IN_SET(%s, meet1_events.meta_value) OR FIND_IN_SET(%s, meet1_events.meta_value) OR FIND_IN_SET(%s, meet2_events.meta_value) OR FIND_IN_SET(%s, meet2_events.meta_value))';
			$parameters[] = $args['event'];
			$parameters[] = ' ' . $args['event'];
			$parameters[] = $args['event'];
			$parameters[] = ' ' . $args['event'];
		}

		if ( $args['order_status'] ) {
			$where        .= ' AND posts.post_status LIKE %s';
			$parameters[] = $args['order_status'];
		} else {
			//$where .= " AND posts.post_status NOT IN ('auto-draft', 'trash')";
			$where .= " AND posts.post_status IN ('wc-processing', 'wc-completed')";
		}

		/*
		 * customize the ORDER BY clause
		 */
		if ( isset( $args['order_by'] ) && $args['order_by'] == 'carpool' ) {
			// age (young to old), gender (boy, girl), last name, first name
			$orderby = 'cast(age.meta_value as unsigned), gender.meta_value, LOWER(rc_lastname), LOWER(rc_firstname)';
		} else {
			// last name, first name
			$orderby = 'LOWER(rc_lastname), LOWER(rc_firstname)';
		}


		/*
		 * generate the sql
		 */
		$sql = "SELECT DISTINCT item.order_item_id, ";

		foreach ( $meta as $metakey ) {
			$sql .= "{$metakey}.meta_value AS '{$metakey}', ";
		}

		$sql .= "rc_group.meta_value AS 'group', orderitems.order_id AS 'order_id', posts.post_status AS 'order_status' FROM {$wpdb->prefix}woocommerce_order_itemmeta AS item ";

		foreach ( $meta as $metakey ) {
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS {$metakey} ON item.order_item_id = {$metakey}.order_item_id AND {$metakey}.meta_key = '{$metakey}' ";
		}

		$sql .= "
LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS orderitems
ON item.order_item_id = orderitems.order_item_id
LEFT JOIN {$wpdb->prefix}posts AS posts
ON orderitems.order_id = posts.ID
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS rc_group
ON item.order_item_id = rc_group.order_item_id
AND rc_group.meta_key = 'rc_group'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_product
ON item.order_item_id = item_product.order_item_id
AND item_product.meta_key = '_product_id'
{$where}
ORDER BY {$orderby}";

		// TODO add limit and pagination to sql

		return $wpdb->get_results( $wpdb->prepare( $sql, $parameters ), ARRAY_A );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which
	 *
	 * @since 3.1.0
	 */
	function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		} ?>
        <div class="alignleft actions">
			<?php
			global $wpdb;
			$statuses = $wpdb->get_results( "
SELECT DISTINCT post_status
FROM {$wpdb->prefix}posts
WHERE post_type LIKE 'shop_order'
AND post_status NOT IN ('auto-draft','trash')
", ARRAY_A );
			if ( $statuses ) {
				?>
                <select name="order_status">
                    <option value="">All paid orders</option>
					<?php foreach ( $statuses as $status ) { ?>
                        <option value="<?php echo $status['post_status']; ?>" <?php selected( $_GET['order_status'], $status['post_status'] ); ?>><?php echo wc_get_order_status_name( $status['post_status'] ); ?>
                            orders
                        </option>
					<?php } ?>
                </select>
			<?php }

			// if not in single-camp mode, show the camp filter
			$rc_camps = get_option( 'rc_camps', [] );
			if ( isset( $rc_camps["camp2"] ) && $rc_camps["camp2"] == "yes" ) {
				?>
                <select name="camp">
                    <option value="">Any camp</option>
                    <option value="Camp 1" <?php selected( $_GET['camp'], 'Camp 1' ); ?>>Camp 1</option>
                    <option value="Camp 2" <?php selected( $_GET['camp'], 'Camp 2' ); ?>>Camp 2</option>
                    <option value="Camp 3" <?php selected( $_GET['camp'], 'Camp 3' ); ?>>Camp 3</option>
                </select>
			<?php } ?>

            <select name="gender">
                <option value="">Boys and girls</option>
                <option value="Boy" <?php selected( $_GET['gender'], 'Boy' ); ?>>Boys</option>
                <option value="Girl" <?php selected( $_GET['gender'], 'Girl' ); ?>>Girls</option>
            </select>
            <select name="age">
                <option value="">All ages</option>
				<?php for ( $i = 6; $i < 13; $i ++ ) { ?>
                    <option value="<?php echo $i; ?>" <?php selected( $_GET['age'], $i ); ?>><?php echo $i; ?> year
                        olds
                    </option>
				<?php } ?>
            </select>

			<?php
			$events = get_option( 'rc_events', [] );
			if ( $events["meet1_title"] || $events["meet2_title"] ) {
				?>
                <select name="event">
                    <option value="">All events</option>
                    <option value="unregistered">Not yet registered</option>
					<?php
					$events = get_option( 'rc_events', [] );
					foreach ( [ 'meet1', 'meet2' ] as $meet ) {
						if ( ! $events[ $meet . '_title' ] ) {
							continue;
						}
						$meet_events = explode( PHP_EOL, trim( $events[ $meet . '_events' ] ) );
						$meet_events = array_map( 'trim', $meet_events );
						foreach ( $meet_events as $event ) {
							?>
                            <option value="<?php echo $event; ?>" <?php selected( $_GET['event'], $event ); ?>><?php echo $event; ?></option>
							<?php
						}
					}
					?>
                </select>
				<?php
			}
			?>
			<?php submit_button( 'Apply Filters', '', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
        </div>
		<?php
	}

	function export_as_csv() {

		$data = $this->get_rc_registrations();
		if ( count( $data ) == 0 ) {
			return;
		}

		// disable caching
		$now = gmdate( "D, d M Y H:i:s" );
		header( "Expires: Tue, 03 Jul 2001 06:00:00 GMT" );
		header( "Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate" );
		header( "Last-Modified: {$now} GMT" );

		// force download
		header( "Content-Type: application/force-download" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );

		// disposition/encoding on response body
		header( "Content-Disposition: attachment;filename=runnerscamp-export.csv" );
		header( "Content-Transfer-Encoding: binary" );

		$out = fopen( "php://output", 'w' );

		// get the list of visible columns
		$columns         = $this->get_columns();
		$hidden          = get_hidden_columns( get_current_screen() );
		$visible_columns = array_diff_key( $columns, array_flip( $hidden ) );

		$column_labels = array_values( $visible_columns );
		fputcsv( $out, $column_labels );

		// output the rows of table contents
		$column_keys = array_keys( $visible_columns );
		foreach ( $data as $item ) {
			$output = array();
			foreach ( $column_keys as $column_key ) {
				$output[ $column_key ] = $this->column_default( $item, $column_key, true );
			}
			fputcsv( $out, $output );
		}

		fclose( $out );
		die();
	}

	function format_phone( $original_phone ) {
		if ( ! $original_phone ) {
			return '';
		}
		$phone = preg_replace( "/[^0-9]/", "", $original_phone );
		if ( strlen( $phone ) == 11 && substr( $phone, 0, 1 ) == '1' ) {
			$phone = substr( $phone, 1 );
		}
		if ( strlen( $phone ) == 10 ) {
			return substr( $phone, 0, 3 ) . '-' . substr( $phone, 3, 3 ) . '-' . substr( $phone, 6 );
		}

		return $original_phone;
	}
}
