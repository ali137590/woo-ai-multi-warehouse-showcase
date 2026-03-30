<?php
namespace WPMultiWarehouse\AI;

/**
 * Intelligent Warehouse Assistant using DeepSeek AI.
 * Demonstrates secure API handling and AI-driven insights.
 */
class DeepSeekAssistant {

    private $api_url = 'https://api.deepseek.com/chat/completions';

    public function __construct() {
        add_action('wp_ajax_mwa_ai_chat', [$this, 'handle_manager_query']);
    }

    /**
     * Bridges manager questions with the AI backend.
     */
    public function handle_manager_query() {
        check_ajax_referer('mwa_ai_nonce', 'security');

        $user_query = sanitize_text_field($_POST['message'] ?? '');
        $api_key    = get_option('mwa_deepseek_api_key'); // Securely stored key

        if (empty($api_key)) {
            wp_send_json_error('AI API key is missing in settings.');
        }

        $response = wp_remote_post($this->api_url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI Warehouse Assistant helping with stock and logistics.'],
                    ['role' => 'user', 'content' => $user_query]
                ]
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Could not connect to AI service.');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_success([
            'answer' => $data['choices'][0]['message']['content'] ?? 'No response.'
        ]);
    }
}
