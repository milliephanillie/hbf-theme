jQuery(document).ready(function($) {
    // Close modal buttons
    $('#closeSelectCustomerModal, #closeCreateCustomerModal').click(function() {
        $.unblockUI();
    });

    // Open Create Customer Modal
    $('#createCustomerBtn').click(function() {
        $.blockUI({
            message: $('#createCustomerModal'),
            css: {
                top:  '40%',
                left: '50%',
                marginLeft: '-300px',
                marginTop: '-150px',
                width: '600px',
                height: '600px',
                borderRadius: '10px'
            }
        });
    });

    // Toggle Credit Card Fields
    $('#toggleCreditCard').change(function() {
        if($(this).is(":checked")) {
            $('#creditCardFields').show();
        } else {
            $('#creditCardFields').hide();
        }
    });

    // Create Customer Form Submission
    $('#createCustomerForm').submit(function(e) {
        e.preventDefault();

        // Client-side Validation
        if ($('#billing_email').val() === '' || !validateEmail($('#billing_email').val())) {
            alert('Invalid email.');
            return;
        }
        if (!$('#sendLoginLink').is(":checked")) {
            if ($('#account_password').val().length < 6) {
                alert('Password must be at least 6 characters.');
                return;
            }

            if ($('#account_password').val() !== $('#account_password_confirm').val()) {
                alert('Passwords do not match.');
                return;
            }
        }

        var sendLoginLink = $('#sendLoginLink').is(":checked");

        $.ajax({
            url: window.php_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_woocommerce_customer',
                billing_email: $('#billing_email').val(),
                account_password: $('#account_password').val(),
                billing_first_name: $('#billing_first_name').val(),
                billing_last_name: $('#billing_last_name').val(),
                billing_company: $('#billing_company').val(),
                billing_address_1: $('#billing_address_1').val(),
                billing_address_2: $('#billing_address_2').val(),
                billing_city: $('#billing_city').val(),
                billing_postcode: $('#billing_postcode').val(),
                billing_country: $('#billing_country').val(),
                billing_state: $('#billing_state').val(),
                billing_phone: $('#billing_phone').val(),
                account_password_confirm: $('#account_password_confirm').val(),
                sendLoginLink: sendLoginLink,
                isDistributor: $('#isDistributor').is(":checked"),
                isExport: $('#isExport').is(":checked"),
                isInternational: $('#isInternational').is(":checked"),
            },
            success: function(response) {
                try {
                    var parsedResponse = JSON.parse(response);
                    if (parsedResponse.success) {
                        if (confirm('Success: ' + parsedResponse.message)) {
                            if (parsedResponse.redirect) {
                                window.location.href = parsedResponse.redirect;
                            } else {
                                $.unblockUI();
                                location.reload();
                            }
                        }
                    } else {
                        alert('Could not create customer: ' + parsedResponse.message);
                    }
                } catch (e) {
                    console.error('Error parsing server response:', e);
                    alert('An error occurred while processing your request. Please try again.');
                }
            }

        });
    });

    function validateEmail(email) {
        var re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(email);
    }
});

var countries = window.wc_locations.countries;
var $countryDropdown = $('#billing_country');
$.each(countries, function(key, value) {
    $countryDropdown.append($('<option>', {
        value: key,
        text: value
    }));
});

$('#billing_country').val('US');

function populateStates(country) {
    var states = window.wc_locations.states;
    var $stateDropdown = $('#billing_state');
    $stateDropdown.empty();

    if (states[country]) {
        $.each(states[country], function(key, value) {
            $stateDropdown.append($('<option>', {
                value: key,
                text: value
            }));
        });
    } else {
        $stateDropdown.append($('<option>', {
            value: '',
            text: 'N/A'
        }));
    }
}

$('#billing_country').change(function() {
    populateStates($(this).val());
});

populateStates('US');