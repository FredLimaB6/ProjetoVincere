<?php
// Inclui os arquivos de shortcodes
require_once plugin_dir_path(__FILE__) . 'shortcodes/queue-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/match-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/ranking-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/achievements-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/store-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/player-stats-shortcode.php';

require_once plugin_dir_path(__FILE__) . 'managers/player-stats-manager.php';
require_once plugin_dir_path(__FILE__) . 'managers/badges-manager.php';
require_once plugin_dir_path(__FILE__) . 'managers/credits-manager.php';

// Registra os shortcodes
add_shortcode('game_queue', 'display_queue');
add_shortcode('game_match', 'display_match');
add_shortcode('game_ranking', 'display_ranking');
add_shortcode('game_achievements', 'display_achievements');
add_shortcode('game_store', 'display_store');
add_shortcode('player_stats', 'display_player_stats');
add_shortcode('game_ranking_with_filters', 'display_ranking_with_filters');

if (!function_exists('game_system_process_queue')) {
    function game_system_process_queue() {
        check_ajax_referer('game_system_nonce', 'nonce');

        if (!isset($GLOBALS['gameSystem'])) {
            wp_send_json_error(['message' => 'O sistema não está inicializado.']);
        }

        $gameSystem = $GLOBALS['gameSystem'];
        $currentUserId = get_current_user_id();

        if (!isset($_POST['queue_id']) || !is_numeric($_POST['queue_id']) || intval($_POST['queue_id']) <= 0) {
            wp_send_json_error(['message' => 'ID da fila inválido.']);
        }
        $queueId = intval($_POST['queue_id']);

        if (!isset($_POST['action_type']) || !in_array($_POST['action_type'], ['join', 'leave'], true)) {
            wp_send_json_error(['message' => 'Ação inválida.']);
        }
        $action = sanitize_text_field($_POST['action_type']);

        if ($action === 'join') {
            $message = $gameSystem->joinQueue($currentUserId);
        } elseif ($action === 'leave') {
            $message = $gameSystem->leaveQueue($currentUserId, $queueId);
        } else {
            wp_send_json_error(['message' => 'Ação não reconhecida.']);
        }

        // Gera o HTML atualizado
        ob_start();
        echo display_queue();
        $html = ob_get_clean();

        wp_send_json_success([
            'message' => $message,
            'html' => $html,
        ]);
    }
    add_action('wp_ajax_game_system_process_queue', 'game_system_process_queue');
    add_action('wp_ajax_nopriv_game_system_process_queue', 'game_system_process_queue');
}

if (!function_exists('game_system_get_queue_state')) {
    function game_system_get_queue_state() {
        check_ajax_referer('game_system_nonce', 'nonce');

        if (!isset($GLOBALS['gameSystem'])) {
            wp_send_json_error(['message' => 'O sistema não está inicializado.']);
        }

        // Gera o HTML atualizado
        ob_start();
        echo display_queue();
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
    add_action('wp_ajax_game_system_get_queue_state', 'game_system_get_queue_state');
    add_action('wp_ajax_nopriv_game_system_get_queue_state', 'game_system_get_queue_state');
}
?>