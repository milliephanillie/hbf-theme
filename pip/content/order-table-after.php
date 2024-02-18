<?php
/**
 * WooCommerce Print Invoices/Packing Lists
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Print
 * Invoices/Packing Lists to newer versions in the future. If you wish to
 * customize WooCommerce Print Invoices/Packing Lists for your needs please refer
 * to http://docs.woocommerce.com/document/woocommerce-print-invoice-packing-list/
 *
 * @package   WC-Print-Invoices-Packing-Lists/Templates
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2023, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

require get_stylesheet_directory() . '/vendor/autoload.php';

// Generate Barcode
$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
$order_number = $order->get_order_number();  // Get the WooCommerce order number
$barcode = base64_encode($generator->getBarcode($order_number, $generator::TYPE_CODE_128));

// Display Barcode
echo '<div class="barcode-section">';
echo '<img src="data:image/png;base64,' . $barcode . '" />';
echo '</div>';

echo '<style>.barcode-section { text-align: center; margin-top: 20px; }</style>';
// Check if the document type is a pick list
if ( 'pick-list' === $type ) {

    // Retrieve the serialized box size data from the order meta
    $serialized_box_sizes = get_post_meta( $order->get_id(), '_wf_ups_stored_packages', true );

    // Check if box size data exists
    if ( !empty($serialized_box_sizes) ) {
        // Deserialize the data
        $box_sizes = maybe_unserialize( $serialized_box_sizes );

        // Check if the deserialization was successful and the result is an array
        if ( is_array( $box_sizes ) ) {
            echo '<div class="box-size-section">';
            echo '<h3>Box Sizes:</h3>';
            foreach ( $box_sizes as $box ) {
                // Assuming 'box_name' and 'Dimensions' are the keys you want
                $box_name = $box['Package']['box_name'];
                $dimensions = $box['Package']['Dimensions'];
                echo '<p>' . esc_html( $box_name ) . ': ' . esc_html( $dimensions['Length'] . 'x' . $dimensions['Width'] . 'x' . $dimensions['Height'] ) . '</p>';
            }
            echo '</div>';
        }
    }

    // Retrieve the shipping notes
    $shipping_notes = get_post_meta( $order->get_id(), '_shipping_notes', true );

    // Check if shipping notes exist and output them
    if ( !empty($shipping_notes) ) {
        echo '<div class="shipping-notes-section">';
        echo '<h3>Shipping Notes:</h3>';
        echo '<p>' . esc_html( $shipping_notes ) . '</p>';
        echo '</div>';
    }
}

/**
 * Template Body after content.
 *
 * @var \WC_Order $order order object
 * @var int $order_id order ID
 * @var \WC_PIP_Document $document document object
 * @var string $type document type
 * @var string $action current document action
 *
 * @version 3.11.2
 * @since 3.0.0
 */

							?>

							<?php if ( $type !== 'pick-list' ) : ?>

								<<?php echo $document->get_table_footer_html_tag(); ?> class="order-table-footer">

									<?php $rows = $document->get_table_footer(); ?>

									<?php foreach ( $rows as $cells ) : $i = 0; ?>

										<tr>
											<?php foreach ( $cells as $cell => $value ) : ?>

												<td class="<?php echo esc_attr( $cell ); ?>" <?php if ( 0 === $i ) { echo 'colspan="' . $document->get_table_footer_column_span( count( $cells ) ) . '"'; } ?>>
													<?php echo $value; $i++; ?>
												</td>

											<?php endforeach; ?>
										</tr>

									<?php endforeach; ?>

								</<?php echo $document->get_table_footer_html_tag(); ?>>

							<?php endif; ?>

						</table>
						<?php

						/**
						 * Fires after the document's body (order table).
						 *
						 * @since 3.0.0
						 *
						 * @param string $type document type
						 * @param string $action current action running on Document
						 * @param \WC_PIP_Document $document document object
						 * @param \WC_Order $order order object
						 */
						do_action( 'wc_pip_after_body', $type, $action, $document, $order );

						?>

						<?php if ( $document->show_coupons_used() ) : ?>

							<?php $coupons = $document->get_coupons_used(); ?>

							<?php if ( $coupons && is_array( $coupons ) ) : ?>

								<div class="coupons-used-wrapper">
									<?php /* translators: Placeholder: %1$s - opening <strong> tag, %2$s - coupons count (used in order), %3$s - closing </strong> tag - %4$s - coupons list */
									printf( '<div class="coupons-used">' . _n( '%1$sCoupon used:%3$s %4$s', '%1$sCoupons used (%2$s):%3$s %4$s', count( $coupons ), 'woocommerce-pip' ) . '</div><br>', '<strong>', count( $coupons ), '</strong>', '<span class="coupon">' . implode( '</span>, <span class="coupon">', $coupons ) . '</span>' ); ?>
								</div>

							<?php endif; ?>

						<?php endif; ?>

						<?php if ( $document->show_customer_details() ) : ?>

							<?php $customer_details = $document->get_customer_details(); ?>

							<?php if ( ! empty( $customer_details ) && is_array( $customer_details ) ) : ?>

								<div class="customer-details-wrapper">
									<h3><?php esc_html_e( 'Customer Details', 'woocommerce-pip' ); ?></h3>

									<ul class="customer-details">
										<?php foreach ( $customer_details as $id => $data ) : ?>

											<li class="<?php echo sanitize_html_class( $id ); ?>"><?php printf( '<strong>%1$s</strong> %2$s', $data['label'], $data['value'] ); ?></li>

										<?php endforeach; ?>
									</ul>
								</div>

							<?php endif; ?>

						<?php endif; ?>

						<?php if ( $document->show_customer_note() ) : ?>

							<?php $customer_note = $document->get_customer_note(); ?>

							<?php if ( '' !== $customer_note ) : ?>

								<div class="customer-note"><blockquote><?php echo $customer_note; ?></blockquote></div>

							<?php endif; ?>

						<?php endif; ?>

						<?php

						/**
						 * Fires after customer details.
						 *
						 * @since 3.0.0
						 *
						 * @param string $type document type
						 * @param string $action current action running on Document
						 * @param \WC_PIP_Document $document document object
						 * @param \WC_Order $order order object
						 */
						do_action( 'wc_pip_order_details_after_customer_details', $type, $action, $document, $order );

						?>
					</div><!-- .document-body-content -->
				</main>

				<br>

				<footer class="document-footer <?php echo $type; ?>-footer">
					<?php

					/**
					 * Fires before the document's footer.
					 *
					 * @since 3.0.0
					 *
					 * @param string $type document type
					 * @param string $action current action running on Document
					 * @param \WC_PIP_Document $document document object
					 * @param \WC_Order $order order object
					 */
					do_action( 'wc_pip_before_footer', $type, $action, $document, $order );

					?>

					<?php if ( $document->show_terms_and_conditions() ) : ?>

						<?php $terms = $document->get_return_policy(); ?>

						<?php if ( '' !== $terms ) : ?>

							<div class="terms-and-conditions"><?php echo $terms; ?></div>

						<?php endif; ?>

					<?php endif; ?>

					<hr>

					<?php if ( $document->show_footer() ) : ?>

						<?php $footer = $document->get_footer(); ?>

						<?php if ( '' !== $footer ) : ?>

							<div class="document-colophon <?php echo $type; ?>-colophon">
								<?php echo $footer; ?>
							</div>

						<?php endif; ?>

					<?php endif; ?>

					<?php

					/**
					 * Fires after the document's footer.
					 *
					 * @since 3.0.0
					 *
					 * @param string $type document type
					 * @param string $action current action running on Document
					 * @param \WC_PIP_Document $document document object
					 * @param \WC_Order $order order object
					 */
					do_action( 'wc_pip_after_footer', $type, $action, $document, $order );

					?>
				</footer>

				<?php if ( 'pick-list' !== $type && 'print' ===  $action ) : ?>

					<hr class="separator" />

				<?php endif; ?>

			</div><!-- .container -->
			<?php
