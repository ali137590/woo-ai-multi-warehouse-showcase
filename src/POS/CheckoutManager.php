<?php
namespace WPMultiWarehouse\POS;

/**
 * Manages the POS Checkout flow.
 * Shows deep integration with WooCommerce Order Core.
 */
class CheckoutManager {

    public function __construct() {
        add_action('wp_ajax_pos_checkout', [$this, 'process_pos_order']);
    }

    /**
     * Creates a WooCommerce order from POS cart data.
     */
    public function process_pos_order() {
        check_ajax_referer('pos_nonce', 'security');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Security check failed.');
        }

        $cart_items = $_POST['cart'] ?? [];
        
        try {
            $order = wc_create_order();
            
            foreach ($cart_items as $item) {
                $product = wc_get_product($item['id']);
                $order->add_product($product, $item['qty']);
                
                // Custom logic: Deduct stock from the specific warehouse selected in POS
                $this->deduct_warehouse_stock($item['id'], $item['warehouse_id'], $item['qty']);
            }

            $order->calculate_totals();
            $order->update_status('completed', 'In-person sale via POS system.');
            
            wp_send_json_success(['order_id' => $order->get_id()]);
            
        } catch (\Exception $e) {
            wp_send_json_error('Order processing failed: ' . $e->getMessage());
        }
    }

    private function deduct_warehouse_stock($p_id, $w_id, $qty) {
        global $wpdb;
        // Logic to update custom multi-warehouse tables
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}mwa_stock_relations SET stock = stock - %f WHERE product_id = %d AND warehouse_id = %d",
            $qty, $p_id, $w_id
        ));
    }
}
