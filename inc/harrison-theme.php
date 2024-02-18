<?php
/**
 * Project: Code Refactoring.
 * Version: 1.0.0
 * Description: Custom extensions for Harrison Bird Foods.
 * License: GNU General Public License v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HBF_THEME_INCLUDES', __FILE__);

if ( ! defined( 'HBF_THEME_INCLUDES_PATH' ) ) {
    define( 'HBF_THEME_INCLUDES_PATH', get_theme_file_path() . '/inc/' );
}

if ( ! defined( 'HBF_THEME_INCLUDES_URL' ) ) {
    define( 'HBF_THEME_INCLUDES_URL', get_stylesheet_directory_uri() . '/inc/');
}

if ( ! defined( 'HBF_THEME_ASSETS_PATH' ) ) {
    define( 'HBF_THEME_ASSETS_PATH', get_theme_file_path() . '/inc/assets/');
}

if ( ! defined( 'HBF_THEME_ASSETS_URL' ) ) {
    define( 'HBF_THEME_ASSETS_URL', get_stylesheet_directory_uri() . '/inc/assets/');
}


if ( ! defined( 'HBF_THEME_TEMPLATES_PATH' ) ) {
    define( 'HBF_THEME_TEMPLATES_PATH', get_theme_file_path() . '/inc/templates/');
}


require_once HBF_THEME_INCLUDES_PATH . '/vendor/autoload.php';

use Harrison\Includes\HBF_Init;
$init = new HBF_Init();

use Harrison\Includes\HBF_Theme;
use Harrison\Includes\HBF_User;
use Harrison\Includes\HBF_FooterModal;
use Harrison\Includes\HBF_TemplateRedirection;

use Harrison\Utils\HBF_PartialPaymentSubmission;
use Harrison\Utils\HBF_ManualOrderItem;
use Harrison\Utils\HBF_PDFGenerator;

use Harrison\Shortcodes\HBF_ManageQuoteShortcode;
use Harrison\Shortcodes\HBF_GuestUserCreationFormShortcode;
use Harrison\Shortcodes\HBF_ShippingNotesShortcode;
use Harrison\Shortcodes\HBF_RefundFormShortcode;
use Harrison\Shortcodes\HBF_PayLaterButtonShortcode;
use Harrison\Shortcodes\HBF_EmptyCartButtonShortcode;
use Harrison\Shortcodes\HBF_DisplayBillingShippingInfoShortcode;
use Harrison\Shortcodes\HBF_AddCreditsToCustomerShortcode;
use Harrison\Shortcodes\HBF_QuoteButtonShortcode;
use Harrison\Shortcodes\HBF_LoadUserShortcode;

use Harrison\WCHelpers\HBF_ProductTabsHelper;
use Harrison\WCHelpers\HBF_ReviewOrderHelper;
use Harrison\WCHelpers\HBF_RedirectHelper;
use Harrison\WCHelpers\HBF_OrderDataHelper;
use Harrison\WCHelpers\HBF_CreditInfoHelper;
use Harrison\WCHelpers\HBF_CheckoutHelper;
use Harrison\WCHelpers\HBF_WC_OrderStatus;

use Harrison\AJAX\HBF_CreateCustomer;
use Harrison\AJAX\HBF_CheckUserStatus;
use Harrison\AJAX\HBF_UpdateShippingMethod;
use Harrison\AJAX\HBF_OrderData;
use Harrison\AJAX\HBF_Cart;
use Harrison\AJAX\HBF_SearchUsersForManualOrders;
use Harrison\AJAX\HBF_ApplyCreditToCustomer;
use Harrison\AJAX\HBF_ApplyCreditToCustomerAccount;
use Harrison\AJAX\HBF_CreateGuestCustomer;
use Harrison\AJAX\HBF_GeneratePrintPageContent;
use Harrison\AJAX\HBF_GenerateRefundPDF;
use Harrison\AJAX\HBF_GetCustomerCreditInfo;
use Harrison\AJAX\HBF_UserBillingInfo;
use Harrison\AJAX\HBF_RemoveAppliedCredit;
use Harrison\AJAX\HBF_ResetCreditBalance;
use Harrison\AJAX\HBF_SwitchToSelectedUser;
use Harrison\AJAX\HBF_GetCustomerInfo;
use Harrison\AJAX\HBF_CheckoutUpdateStatus;
use Harrison\AJAX\HBF_ClearCustomerSession;
use Harrison\Utils\HBF_BardcodeGenerator;

function load_hbf_classes() {
    //Disabled
    //$footerModal = new HBF_FooterModal();

    //Includes
    $hbfTheme = new HBF_Theme();
    $hbfUser = new HBF_User();
    $templateRedirection = new HBF_TemplateRedirection();

    //Utils
    $partialPayment = new HBF_PartialPaymentSubmission();
    $manualOrderItem = new HBF_ManualOrderItem();

    //Shortcodes
    $manageQuoteSC = new HBF_ManageQuoteShortcode();
    $payLaterButtonSC = new HBF_PayLaterButtonShortcode();
    $shippingNotesSC = new HBF_ShippingNotesShortcode();
    $refundFormSC = new HBF_RefundFormShortcode();
    $guestUserCreationFormSC = new HBF_GuestUserCreationFormShortcode();
    $emptyCartButtonSC = new HBF_EmptyCartButtonShortcode();
    $displayBillingShippingInfoSC = new HBF_DisplayBillingShippingInfoShortcode();
    $addCreditsToCustomerSC = new HBF_AddCreditsToCustomerShortcode();
    $quoteButtonSC = new HBF_QuoteButtonShortcode();
    $loadUserSC = new HBF_LoadUserShortcode();

    //WCHelpers
    $productTabsHelper = new HBF_ProductTabsHelper();
    $reviewOrderHelper = new HBF_ReviewOrderHelper();
    $redirectHelper = new HBF_RedirectHelper();
    $orderDataHelper = new HBF_OrderDataHelper();
    $creditInfoHelper = new HBF_CreditInfoHelper();
    $checkoutHelper = new HBF_CheckoutHelper();

    // AJAX
    $updateShippingMethod = new HBF_UpdateShippingMethod();
    $orderDetails = new HBF_OrderData();
    $searchManualOrders = new HBF_SearchUsersForManualOrders();
    $applyCreditToCustomer = new HBF_ApplyCreditToCustomer();
    $applyCreditToCustomerAccount = new HBF_ApplyCreditToCustomerAccount();
    $cart = new HBF_Cart();
    $checkUserStatus = new HBF_CheckUserStatus();
    $createCustomer = new HBF_CreateCustomer();
    $createGuestCustomer = new HBF_CreateGuestCustomer();
    $generatePrintPageContent = new HBF_GeneratePrintPageContent();
    $generateRefundPDF = new HBF_GenerateRefundPDF();
    $customerCreditInfo = new HBF_GetCustomerCreditInfo();
    $userbillingshippinginfo = new HBF_UserBillingInfo();
    $removeAppliedCredit = new HBF_RemoveAppliedCredit();
    $resetCreditBalance = new HBF_ResetCreditBalance();
    $searchUsersForManualOrders = new HBF_SearchUsersForManualOrders();
    $switchToSelectedUser = new HBF_SwitchToSelectedUser();
    $updateShippingMethod = new HBF_UpdateShippingMethod();
    $checkoutUpdateStatus = new HBF_CheckoutUpdateStatus();
    $getCustomerInfo = new HBF_GetCustomerInfo();
    $hbfClearCustomerSession = new HBF_ClearCustomerSession();
}
add_action('init', 'load_hbf_classes');

use Harrison\Integrations\HBF_StoreLocator;

function do_integrations() {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    $wpsl_path = 'wp-store-locator/wp-store-locator.php';
    if(is_plugin_active($wpsl_path)) {
        $wpsl = new HBF_StoreLocator();
    }
}
add_action('plugins_loaded', 'do_integrations');




