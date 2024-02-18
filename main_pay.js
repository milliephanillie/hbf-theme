/* global wc_checkout_params */
jQuery( function( $ ) {

    $.blockUI.defaults.overlayCSS.cursor = 'default';
    $( document.body ).trigger( 'update_checkout' );
    console.log('Finish')
});
