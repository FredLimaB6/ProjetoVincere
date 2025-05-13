<?php
class QueueManager {
    private $queues;

    public function __construct() {
        $this->queues = get_option('game_system_queues', []);
    }

    public function getQueues() {
        return $this->queues;
    }

    public function saveQueues($queues) {
        update_option('game_system_queues', $queues);
    }
}

add_action('wp_ajax_join_queue', 'handle_join_queue');
add_action('wp_ajax_nopriv_join_queue', 'handle_join_queue');

function handle_join_queue() {
    check_ajax_referer('game_system_nonce', 'nonce');

    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        wp_send_json_error(['message' => 'ID de usuário inválido.']);
    }

    $userId = intval($_POST['user_id']);
    global $queueSystem;

    if (!$queueSystem) {
        wp_send_json_error(['message' => 'O sistema de filas não está inicializado.']);
    }

    $response = $queueSystem->joinQueue($userId);
    wp_send_json_success(['message' => $response]);
}
?>