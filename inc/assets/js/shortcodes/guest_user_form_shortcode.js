jQuery(document).ready(function($) {
    // Populate country dropdown for guest form
    var guestCountries = window.guest_user_creation_form_shortcode_object.countries;
    var $guestBillingCountryDropdown = $('#guest_billing_country');
    var $guestShippingCountryDropdown = $('#guest_shipping_country');

    $.each(guestCountries, function(key, value) {
        $guestBillingCountryDropdown.append($('<option>', { value: key, text: value }));
        $guestShippingCountryDropdown.append($('<option>', { value: key, text: value }));
    });

    // Set default country to United States for guest form
    $guestBillingCountryDropdown.val('US');
    $guestShippingCountryDropdown.val('US');

    // Function to populate states dropdown for guest form
    function populateGuestStates($stateDropdown, country) {
        var guestStates = window.guest_user_creation_form_shortcode_object.states;
        $stateDropdown.empty();

        if (guestStates[country]) {
            $.each(guestStates[country], function(key, value) {
                $stateDropdown.append($('<option>', { value: key, text: value }));
            });
        } else {
            $stateDropdown.append($('<option>', { value: '', text: 'N/A' }));
        }
    }

    // Update states dropdown when country changes for guest form
    $guestBillingCountryDropdown.change(function() {
        populateGuestStates($('#guest_billing_state'), $(this).val());
    });
    $guestShippingCountryDropdown.change(function() {
        populateGuestStates($('#guest_shipping_state'), $(this).val());
    });

    // Populate states for the default country (United States) for guest form
    populateGuestStates($('#guest_billing_state'), 'US');
    populateGuestStates($('#guest_shipping_state'), 'US');

    // Handle form submission for Guest User Account creation
    $('#createGuestUserForm').submit(function(e) {
        e.preventDefault();

        var formData = {
            action: 'create_guest_woocommerce_customer',
            billing_first_name: $('#guest_billing_first_name').val(),
            billing_last_name: $('#guest_billing_last_name').val(),
            billing_company: $('#guest_billing_company').val(),
            billing_phone: $('#guest_billing_phone').val(),
            billing_address_1: $('#guest_billing_address_1').val(),
            billing_address_2: $('#guest_billing_address_2').val(),
            billing_city: $('#guest_billing_city').val(),
            billing_postcode: $('#guest_billing_postcode').val(),
            billing_country: $('#guest_billing_country').val(),
            billing_state: $('#guest_billing_state').val(),
            shipping_first_name: $('#guest_shipping_first_name').val(),
            shipping_last_name: $('#guest_shipping_last_name').val(),
            shipping_company: $('#guest_shipping_company').val(),
            shipping_address_1: $('#guest_shipping_address_1').val(),
            shipping_address_2: $('#guest_shipping_address_2').val(),
            shipping_city: $('#guest_shipping_city').val(),
            shipping_postcode: $('#guest_shipping_postcode').val(),
            shipping_country: $('#guest_shipping_country').val(),
            shipping_state: $('#guest_shipping_state').val()
        };

        // AJAX call for form submission
        $.ajax({
            url: window.php_vars.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                try {
                    var parsedResponse = JSON.parse(response);
                    if (parsedResponse.success) {
                        alert('Success: ' + parsedResponse.message);
                        location.reload();
                    } else {
                        alert('Error: ' + parsedResponse.message);
                    }
                } catch (error) {
                    console.error('Error parsing server response:', error);
                    alert('An error occurred while processing your request. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while submitting the form. Please try again.');
            }
        });
    });
// Event listener for the Copy Billing to Shipping button
    $('#copyBillingToShipping').click(function() {
        $('#guest_shipping_first_name').val($('#guest_billing_first_name').val());
        $('#guest_shipping_last_name').val($('#guest_billing_last_name').val());
        $('#guest_shipping_company').val($('#guest_billing_company').val());
        $('#guest_shipping_address_1').val($('#guest_billing_address_1').val());
        $('#guest_shipping_address_2').val($('#guest_billing_address_2').val());
        $('#guest_shipping_city').val($('#guest_billing_city').val());
        $('#guest_shipping_postcode').val($('#guest_billing_postcode').val());
        $('#guest_shipping_country').val($('#guest_billing_country').val()).change();
        $('#guest_shipping_state').val($('#guest_billing_state').val()).change();
    });
});