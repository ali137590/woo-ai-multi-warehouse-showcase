<?php
namespace WPMultiWarehouse\Inventory;

/**
 * Handles Product Inventory and Warehouse Assignments.
 * Demonstrates clean database operations and AJAX-based stock updates.
 */
class ProductHandler {

    public function __construct() {
        // Hook for non-refresh stock updates
        add_action('wp_ajax_mwa_update_stock', [$this, 'update_stock_ajax']);
    }

    /**
     * AJAX handler for updating stock in a specific warehouse.
     */
    public function update_stock_ajax() {
        check_ajax_referer('mwa_secure_action', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $product_id   = intval($_POST['product_id']);
        $warehouse_id = intval($_POST['warehouse_id']);
        $new_stock    = floatval($_POST['stock_qty']);

        $table_name = $wpdb->prefix . 'mwa_stock_relations';

        // Optimized DB update logic
        $updated = $wpdb->replace(
            $table_name,
            [
                'product_id'   => $product_id,
                'warehouse_id' => $warehouse_id,
                'stock'        => $new_stock
            ],
            ['%d', '%d', '%f']
        );

        if ($updated !== false) {
            wp_send_json_success('Stock successfully updated.');
        } else {
            wp_send_json_error('Database operation failed.');
        }
    }
}
