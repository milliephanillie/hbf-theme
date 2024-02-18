<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php'); // Load WordPress environment

if (current_user_can('view_extra_fields') || current_user_can('administrator')) {
    $fileName = urldecode($_GET['file']);
    $filePath = '/path/to/secure/directory/' . $fileName;

    if (file_exists($filePath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    }
}

// If not authorized or file does not exist
wp_die('You do not have permission to view this document.', 'Access Denied', ['response' => 403]);