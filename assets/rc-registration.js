jQuery(function () {
    // customizations for registration product pages
    init_rc_single_product_pages();

    // moves the Edit link underneath the variations
    init_rc_edit_cart();

    // customizations for the meet event signup form
    init_rc_meet_event_signup();
});

function init_rc_meet_event_signup() {
    let $verify = jQuery("#meet-events-verify-submit");
    let $submit = jQuery("#meet-events-submit");
    if (!$verify.length || !$submit.length) {
        return;
    }

    // enables the submit button when the verify checkbox is checked
    $verify.change(function () {
        if (jQuery(this).attr("checked")) {
            $submit.removeAttr("disabled");
        } else {
            $submit.attr("disabled", "disabled");
        }
    });

    // enforces the event max limits
    for (let i = 1; i < 3; i++) {
        let meet = 'meet' + i;
        let $wrapper = jQuery("#" + meet + "_events");
        if ($wrapper.length) {
            let limit = $wrapper.data("limit");
            if (limit) {
                $wrapper.find("input").on('change', function () {
                    if (jQuery(this).closest("div").find('input:checked').length > limit) {
                        this.checked = false;
                    }
                });
            }
        }
    }
}

function init_rc_edit_cart() {
    let $cart = jQuery(".woocommerce-cart-form__contents");
    if (!$cart.length) {
        return;
    }

    let move_edit_link = function () {
        $cart = jQuery(".woocommerce-cart-form__contents");
        $cart.find(".rc-edit-cart-item.hidden").each(function () {
            let variation = jQuery(this).next("dl");
            jQuery(this).appendTo(variation).removeClass("hidden");
        });
    };

    // do this on init and on cart edit
    move_edit_link();
    jQuery(document).on("updated_wc_div wc_update_cart", move_edit_link);
}

function init_rc_single_product_pages() {

    const $singlevariation = jQuery(".single_variation");
    if (!$singlevariation.length) {
        return;
    }

    // do stuff when a variation is selected
    $singlevariation.on("show_variation", function (e, variation, purchasable) {
        if (purchasable) {
            jQuery("#rc-registration-fields").show();
        } else {
            jQuery("#rc-registration-fields").hide();
        }
    });

    // do stuff when a variation is hidden
    $singlevariation.on("hide_variation", function (e, variation, purchasable) {
        jQuery("#rc-registration-fields").hide();
    });

    // get min/max possible birth years
    let min_year, max_year;
    if (typeof rc === "object") {
        // these were passed via php
        min_year = Number(rc.first_day);
        max_year = Number(rc.last_day);
    } else {
        // ballpark based on the current year
        let d = new Date();
        min_year = d.getFullYear() - 12;
        max_year = d.getFullYear() - 4;
    }

    // initialize datepicker
    jQuery("#rc-birthday-field").datepicker({
        dateFormat: "mm/dd/yy",
        changeMonth: true,
        changeYear: true,
        minDate: '01/01/' + min_year,
        maxDate: '12/31/' + max_year,
        yearRange: min_year + ":" + max_year,
        defaultDate: '01/01/' + min_year,
    });

}