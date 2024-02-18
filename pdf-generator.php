<?php
require 'vendor/autoload.php';

function user_can_view_pdf() {
    // Check if the user has the 'view_extra_fields' capability or is an administrator
    return current_user_can('view_extra_fields') || current_user_can('administrator');
}

function generate_pdf($htmlContent, $fileName = 'document.pdf', $order_id) {
	// Check if the user has permission to view the PDF
    if (!user_can_view_pdf()) {
        wp_die('You do not have permission to view this document.', 'Access Denied', ['response' => 403]);
    }
	
    $dompdf = new \Dompdf\Dompdf();
	
	// Enable remote image loading
    $dompdf->set_option('isRemoteEnabled', TRUE);
	
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Define path to save PDF
    $upload_dir = wp_upload_dir();
    $pdf_path = $upload_dir['basedir'] . '/custom_pdfs/' . $fileName;

    // Save the PDF
    file_put_contents($pdf_path, $dompdf->output());

    // Return the URL to the PDF
    return $upload_dir['baseurl'] . '/custom_pdfs/' . $fileName;
}