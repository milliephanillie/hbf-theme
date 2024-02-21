/*

jQuery(document).ready(function($) {

    $('#switurl: window.php_vars'click', function() {

        $.ajax({

            url: php_vars.adminAjaxUrl,

            type: 'POST',

            data: {

                action: 'switch_back_to_original_user'

            },

            success: function(response) {

                if (response.success) {

                    location.reload();  // Reload the page to reflect the user switch

                } else {

                    alert('Error switching back to original user.');

                }

            },

            error: function() {

                alert('An error occurred while switching back to the original user.');

            }

        });

    });

});

*/