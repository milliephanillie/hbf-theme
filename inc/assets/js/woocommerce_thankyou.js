jQuery(function($) {
    $('#printInvoice').on('click', function() {
        // Find the 'View Invoice' button URL
        var invoiceUrl = $('.wc_pip_view_invoice').attr('href');
        if (invoiceUrl) {
            // Open the invoice URL in a new tab
            window.open(invoiceUrl, '_blank');
        }
    });

    $('#completeProcess').on('click', function() {
        // Disable the button after the first click
        $(this).prop('disabled', true).text('Processing...');

        var href = $('#user_switching_switch_on a').attr('href');
        var two_href = href.split('redirect_to=');
        if (two_href[0]) {
            window.location = two_href[0] + 'redirect_to=' + window.php_vars.siteUrl;
        }
    });
});