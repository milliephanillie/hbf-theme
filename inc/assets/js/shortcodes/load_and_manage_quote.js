jQuery(document).ready(function($) {
    $('#load-quote-formout').submit(function(e) {
        e.preventDefault();
        var quoteId = $('#quoteIdInput').val();

        // AJAX request to server to load quote information...
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'load_quote_details',
                quote_id: quoteId
            },
            success: function(response) {
                // Handle the response
                // Populate #quote-details with the quote information
            },
            error: function() {
                alert('Failed to load quote. Please try again.');
            }
        });
    });
});