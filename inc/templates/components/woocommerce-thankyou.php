<?php
$printUrl = esc_url($printUrl);
if ($order && $order->has_status('on-hold')) { ?>
    <a href="<?php echo $printUrl; ?>" class="wc_pip_view_invoice" style="display: none;">View Invoice</a>
<?php } ?>

<div class="modal-sw">
    <p style="text-align: center"><strong>Order is complete.</strong></p>
    <p>To print the invoice, click the "print" option below. To switch back to <?php echo $old_user->first_name . ' ' . $old_user->last_name; ?> and continue taking orders, click "done."</p>
    <div style="text-align: center; margin-top: 20px;">
        <button id="printInvoice" style="margin-right: 10px;" data-print-url="<?php echo $printUrl; ?>">Print</button>
        <button id="completeProcess">Done</button>
    </div>
</div>