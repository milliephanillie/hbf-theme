<?php
require 'vendor/autoload.php';  // Adjust the path as needed

use Picqer\Barcode\BarcodeGeneratorPNG;

$generator = new BarcodeGeneratorPNG();
$order_id = 12345;  // Replace this with the actual order ID

header('Content-Type: image/png');
echo $generator->getBarcode($order_id, $generator::TYPE_CODE_128);