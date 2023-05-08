<h2>Event Signup</h2>
<?php

if (isset($_GET['code']) && isset($_GET['camp']) && isset($_GET['order'])) {
	display_camper_signup_single();
} else {
	display_camper_signup_archive();
}

function update_camper_events($code, $camp, $order_id, $item_id) {
	?>
	<?php
	$events = get_option('rc_events',[]);

	$signup_for_events = array();
	foreach (['meet1', 'meet2'] as $meet) {

		// make sure meet exists
		if (!$events[$meet . '_title']) {
			continue;
		}

		$num_events = count($_POST[$meet . '_events']);
		$max_num_events = (int)$events[$meet . '_max'];
		if ($max_num_events && $num_events > $max_num_events) {
			rc_message_and_show_single("Error: you selected too many events. Please try again.");
			return;
		}

		$meet_events = explode(PHP_EOL, trim($events[$meet . '_events']));
		$meet_events = array_map('trim', $meet_events);

		foreach ($_POST[$meet . '_events'] as $event) {
			if (!in_array($event, $meet_events)) {
				rc_message_and_show_archive("Error: invalid event. Please try again.");
				return;
			}
		}

		// make sure order exists and was ordered by current user
		if (!$order_id || !($order = wc_get_order($order_id)) || $order->get_user_id() != get_current_user_id()) {
			rc_message_and_show_archive("Invalid order. Please try again or contact the site administrator if the problem persists.");
			return;
		}

		$order_item = new WC_Order_Item_Product($item_id);
		$product_id = get_option("rc_product_id");
		// make sure product is camp registration and the camp and camper code match
		if ($product_id != $order_item->get_product_id() || $camp != $order_item->get_meta('camp', true) || $code != $order_item->get_meta('rc_code', true)) {
			rc_message_and_show_archive("Invalid order. Please try again or contact the site administrator if the problem persists.");
			return;
		}

		// either save the event list or save a hyphen (woocommerce won't save empty meta)
		$signup_for_events[$meet] = $num_events ? implode(", ", $_POST[$meet . '_events']) : '-';

	}

	// wait to add the metadata at the end, to make sure all validation checks passed for both camps
	if (isset($signup_for_events['meet1'])) {
		wc_update_order_item_meta($item_id, 'meet1_events', $signup_for_events['meet1']);
	}
	if (isset($signup_for_events['meet2'])) {
		wc_update_order_item_meta($item_id, 'meet2_events', $signup_for_events['meet2']);
	}

	rc_message_and_show_archive("Event signup completed!", "message");
}

function rc_meet_event_signup_form($code, $camp, $order_id) {

	$args = array(
		"code" => $code,
		"camp" => $camp,
		"order" => $order_id
	);
	$url = add_query_arg($args, wc_get_endpoint_url('event-signup'));

	$events = get_option('rc_events',[]);

	if (isset($events['instructions'])) {
		echo wpautop($events['instructions'], true);
	}
	?>
    <form action="<?php echo $url; ?>" method="post" class="rc-event-signup-form">
        <div class="rc-meet-cols">
			<?php
			wp_nonce_field('signup_code_' . $code . '_' . str_replace(" ", "_", $camp) . '_order_' . $order_id);

			foreach (['meet1', 'meet2'] as $meet) {
				if (!$events[$meet . '_title']) {
					continue;
				}
				?>
                <div class="rc-meet-col">
                    <h3><?php echo $events[$meet . '_title']; ?></h3>
					<?php if ($max = (int)$events[$meet . '_max']) { ?>
                        <p>Select up to <strong><?php echo $max ?></strong> events.</p>
					<?php } ?>
                    <div id="<?php echo $meet; ?>_events" data-limit="<?php echo $max ?>">
						<?php
						$meet_events = explode(PHP_EOL, trim($events[$meet . '_events']));
						$meet_events = array_map('trim', $meet_events);
						foreach ($meet_events as $event) {
							?>
                            <label><input type="checkbox" name="<?php echo esc_attr($meet . '_events[' . $event . ']'); ?>" value="<?php echo esc_attr($event); ?>"/> <?php echo $event; ?></label><br>
							<?php
						}
						?>
                    </div>
                </div>
				<?php
			}
			?>
        </div>
        <p>This form cannot be resubmitted. Please double-check your selections before submitting.</p>
        <p><label><input type="checkbox" id="meet-events-verify-submit"/> I have verified my event selections.</label></p>
        <input id="meet-events-submit" type="submit" value="Submit" class="woocommerce-button button view" disabled="disabled"/>

    </form>
	<?php
}

function display_camper_signup_single() {

	$order_id = (int)$_GET['order'];
	$code = preg_replace("/[^0-9a-zA-Z]/", "", $_GET['code']);
	$camp = preg_replace("/[^0-9a-zA-Z ]/", "", $_GET['camp']);

	$events = get_option('rc_events', []);
	if (!$events['show_form']) {
		rc_message_and_show_archive("This form is not available.");
		return;
	}

	if (!$order_id || !($order = wc_get_order($order_id)) || $order->get_user_id() != get_current_user_id()) {
		rc_message_and_show_archive("Invalid order. Please try again or contact the site administrator if the problem persists.");
		return;
	}

	$product_id = get_option("rc_product_id");
	$camper = null;

	foreach ($order->get_items() as $item_id => $item) {
		if ($product_id != $item->get_product_id()) {
			continue;
		}
		$item_camp = $item->get_meta('camp', true);
		$item_code = $item->get_meta('rc_code', true);

		if ($camp == $item_camp && $code == $item_code) {
			$camper_id = $item_id;
			break;
		}
	}

	if (!$camper_id) {
		rc_message_and_show_archive("Camper not found. Please try again or contact the site administrator if the problem persists.");
		return;
	}

	if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'signup_code_' . $code . '_' . str_replace(" ", "_", $camp) . '_order_' . $order_id)) {
		update_camper_events($code, $camp, $order_id, $camper_id);
		return;
	}

	if ($item->get_meta('meet1_events', true) || $item->get_meta('meet2_events', true)) {
		rc_message_and_show_archive("Camper already registered! Contact the site administrator if you need to change your selections.");
		return;
	}

	echo '<p><strong>Camper:</strong> ' . $item->get_meta('rc_firstname', true) . ' ' . $item->get_meta('rc_lastname', true);

	// if in single-camp mode, hide the camp display
	$rc_camps = get_option('rc_camps',[]);
	if (isset($rc_camps["camp2"]) && $rc_camps["camp2"] == "yes") {
		echo '<br><strong>Camp:</strong> ' . $camp;
	}
	echo '</p>';
	rc_meet_event_signup_form($code, $camp, $order_id);
}

function display_camper_signup_archive() {

	$args = array(
		'customer_id' => get_current_user_id()
	);
	$orders = wc_get_orders($args);

	$product_id = get_option("rc_product_id");

	ob_start();

	foreach ($orders as $order) {
		$order_status = $order->get_status();
		if ($order_status != 'completed') {
			continue;
		}
		foreach ($order->get_items() as $item) {
			if ($product_id != $item->get_product_id()) {
				continue;
			}
			?>
            <tr>
				<?php
				$first = $item->get_meta('rc_firstname', true);
				$last = $item->get_meta('rc_lastname', true);
				$camp = $item->get_meta('camp', true);
				$code = $item->get_meta('rc_code', true);
				$args = array(
					"code" => $code,
					"camp" => $camp,
					"order" => $order->get_id()
				);
				?>
                <td>
					<?php echo $first . ' ' . $last; ?>
                </td>
                <td>
					<?php echo $camp; ?>
                </td>
                <td>
					<?php
					$events = get_option('rc_events',[]);
					$meet1_title = $events['meet1_title'];
					$meet1_events = $item->get_meta('meet1_events', true);
					$meet2_title = $events['meet2_title'];
					$meet2_events = $item->get_meta('meet2_events', true);
					if (($meet1_title && $meet1_events) || ($meet2_title && $meet2_events)) {
						if ($meet1_title && $meet1_events) {
							echo '<strong>' . $meet1_title . ':</strong> ' . $meet1_events . '<br>';
						}
						if ($meet2_title && $meet2_events) {
							echo '<strong>' . $meet2_title . ':</strong> ' . $meet2_events . '<br>';
						}
					} else {
						?>
                        <a class="woocommerce-button button view" href="<?php echo add_query_arg($args, wc_get_endpoint_url('event-signup')); ?>">Submit form</a>
						<?php
					}
					?>
                </td>
            </tr>
			<?php
		}
	}

	$table_content = ob_get_clean();
	if (!$table_content) {
		echo '<p>No campers registered for this year. (Only campers from <a href="' . wc_get_endpoint_url('orders') . '">completed orders</a> will appear below.)</p>';
		return;
	}
	?>

    <table>
        <tr>
            <th>Name</th>
            <th>Camp</th>
            <th>Event Signup</th>
        </tr>
		<?php echo $table_content; ?>
    </table>
	<?php
}

function rc_message_and_show_archive($message, $type = "error") {
	echo '<div class="woocommerce-' . $type . '" role="alert"><p>' . $message . '</p></div>';
	display_camper_signup_archive();
}

function rc_message_and_show_single($message, $type = "error") {
	echo '<div class="woocommerce-' . $type . '" role="alert"><p>' . $message . '</p></div>';
	unset($_POST['_wpnonce']);
	display_camper_signup_single();
}