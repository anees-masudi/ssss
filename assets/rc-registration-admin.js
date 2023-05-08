jQuery(function () {
	init_rc_save_camper_group();
	init_rc_update_product_stock();
});

function init_rc_update_product_stock() {
	let $submit_buttons = jQuery("button.update-stock-submit");
	if ( !$submit_buttons.length ) return;

	$submit_buttons.click(function ( e ) {
		e.preventDefault();

		let $this_wrapper = jQuery(this).parent();
		if ( $this_wrapper.hasClass("sending") ) {
			return;
		}

		let product_id = parseInt(jQuery(this).closest('.wcwl_body_wrap').data("product-id"));
		let stock_quantity = parseInt(jQuery(this).prev('input[name^="variable_stock"]').val());
		let nonce = jQuery("#_nonce_update_product_stock_" + product_id).val();

		if ( !nonce.length ) {
			return;
		}

		jQuery.ajax({
			url: rc.ajax_url,
			type: 'post',
			data: {
				action: 'rc_update_product_stock',
				_ajax_nonce: nonce,
				product_id: product_id,
				stock_quantity: stock_quantity
			},
			beforeSend: function () {
				$this_wrapper.removeClass("success failure");
				$this_wrapper.addClass("sending");
			},
			success: function ( response ) {
				$this_wrapper.removeClass("sending");
				$this_wrapper.addClass("success");
			},
			error: function ( xhr, status, error ) {
				$this_wrapper.removeClass("sending");
				$this_wrapper.addClass("failure");
				console.log(xhr.responseText);
			}
		});
	});
}


function init_rc_save_camper_group() {
	let $inputs = jQuery('input[name^="rc_group"]');
	if ( !$inputs.length ) return;

	$inputs.change(function () {

		let $this_wrapper = jQuery(this).parent();

		if ( $this_wrapper.hasClass("sending") ) {
			return;
		}

		let nonce = jQuery("#_nonce_update_order_meta").val();
		let group = jQuery(this).val();
		let order_item_id = jQuery(this).data("order-item-id");

		if ( !nonce.length ) {
			return;
		}

		jQuery.ajax({
			url: rc.ajax_url,
			type: 'post',
			data: {
				action: 'rc_save_camper_group',
				_ajax_nonce: nonce,
				order_item_id: order_item_id,
				group: group
			},
			beforeSend: function () {
				$this_wrapper.removeClass("success failure");
				$this_wrapper.addClass("sending");
			},
			success: function ( response ) {
				$this_wrapper.removeClass("sending");
				$this_wrapper.addClass("success");
                $this_wrapper.find("input").val(jQuery.trim($this_wrapper.find("input").val()));
			},
			error: function ( xhr, status, error ) {
				$this_wrapper.removeClass("sending");
				$this_wrapper.addClass("failure");
				console.log(xhr.responseText);
			}
		});
	});
}