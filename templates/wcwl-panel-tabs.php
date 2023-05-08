<ul class="wcwl_tabs">
	<li class="current" data-tab="waitlist"><?php _e( 'Waitlist', 'woocommerce-waitlist' ); ?></li>
	<li data-tab="inventory"><?php _e( 'Available', 'woocommerce-waitlist' ); ?></li>
	<li data-tab="archive"><?php _e( 'Archive', 'woocommerce-waitlist' ); ?></li>
	<li data-tab="options"><?php _e( 'Options', 'woocommerce-waitlist' ); ?></li>
</ul>

<div class="inventory wcwl_tab_content" data-panel="inventory">
	<p>Enter the number of remaining camp spaces for this age/gender class:</p>
	<div class="rc-update-stock-wrapper">
		<?php
		$product = wc_get_product( $product_id );
		wp_nonce_field( 'rc_update_product_stock', '_nonce_update_product_stock_' . $product_id, false );
		?>
		<input type="number" class="short wc_input_stock" name="variable_stock[<?php echo $product_id; ?>]" value="<?php echo (int) $product->get_stock_quantity() ?>" />
		<button type="submit" class="button primary update-stock-submit">Update</button>
	</div>
</div>