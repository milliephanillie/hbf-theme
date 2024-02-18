<?php
namespace Harrison\Utils;

use Dompdf\Dompdf;

class HBF_PDFGenerator {
    public static function user_can_view_pdf() {
        return current_user_can('view_extra_fields') || current_user_can('administrator');
    }

    public static function generate_pdf($htmlContent, $fileName = 'document.pdf', $order_id) {
        if (!self::user_can_view_pdf()) {
            wp_die('You do not have permission to view this document.', 'Access Denied', ['response' => 403]);
        }

        $dompdf = new Dompdf();

        $dompdf->set_option('isRemoteEnabled', TRUE);

        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $upload_dir = wp_upload_dir();
        $pdf_path = $upload_dir['basedir'] . '/custom_pdfs/' . $fileName;

        file_put_contents($pdf_path, $dompdf->output());

        return $upload_dir['baseurl'] . '/custom_pdfs/' . $fileName;
    }
}

