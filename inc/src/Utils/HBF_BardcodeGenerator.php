<?php
namespace Harrison\Utils;

use Picqer\Barcode\BarcodeGeneratorPNG;

class HBF_BardcodeGenerator {
    public function __construct() {
        $this->generator = new BarcodeGeneratorPNG();
    }

    public function get_barcode() {
        $order_id = 12345;  // Replace this with the actual order ID
        echo $this->generator->getBarcode($order_id, $this->generator::TYPE_CODE_128);

        header('Content-Type: image/png');
    }
}









