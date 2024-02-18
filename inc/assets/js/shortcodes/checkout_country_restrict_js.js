jQuery(function ($) {
    function clearCustomerSession() {
        $.ajax({
            url: wc_checkout_params.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_customer_session',
                security: window.wp_nonces.clear_customer_session
            },
            success: function (response) {
                if (response === 'success') {
                    console.log('Session cleared.');
                }
            }
        });
    }

    function checkCountryAndStateRestrictions(country, state) {
        var restrictedStates = ['HI', 'PR', 'AK']; // Hawaii, Puerto Rico, Alaska

        if (country && (country !== 'US' || (country === 'US' && restrictedStates.includes(state)))) {
            var location = country === 'US' ? 'State: ' + state : 'Country: ' + country;
            var message = "Online ordering is currently not available for your location. If you would like to place an order with us, please call 1-800-346-0269 to speak with a customer service representative. We apologize for the inconvenience and appreciate your business.\n\nDetected Location: " + location;
            alert(message);
            clearCustomerSession();
            window.location.href = "<?php echo home_url(); ?>"; // Redirect to home page
        }
    }

    $('#billing_country, #shipping_country, #billing_state, #shipping_state').change(function () {
        var selectedCountry = $('#billing_country').val() || $('#shipping_country').val();
        var selectedState = $('#billing_state').val() || $('#shipping_state').val();
        checkCountryAndStateRestrictions(selectedCountry, selectedState);
    });

    var currentCountry = $('#billing_country').val() || $('#shipping_country').val();
    var currentState = $('#billing_state').val() || $('#shipping_state').val();
    checkCountryAndStateRestrictions(currentCountry, currentState);
});