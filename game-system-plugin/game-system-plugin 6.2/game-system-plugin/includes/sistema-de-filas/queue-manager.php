<?php
require_once plugin_dir_path(__FILE__) . '../DatabaseManager.php';

class QueueManager {
    private $table_filas;
    private $table_partidas;

    public function __construct() {
        global $wpdb;
        $this->table_filas = $wpdb->prefix . 'filas_tabela';
        $this->table_partidas = $wpdb->prefix . 'partidas_de_filas';

        // Garante que as tabelas existam
        $dbManager = new DatabaseManager();
        $dbManager->createTables();
    }

    // Recupera todas as filas do banco de dados
    public function getQueues() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$this->table_filas}", ARRAY_A);

        $queues = [];
        foreach ($results as $row) {
            $queues[$row['id']] = maybe_unserialize($row['user_ids']);
        }

        return $queues;
    }

    // Recupera uma fila específica pelo ID
    public function getQueueById($queueId) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_filas} WHERE id = %d", $queueId), ARRAY_A);

        if ($result) {
            return maybe_unserialize($result['user_ids']);
        }

        return null;
    }

    // Salva ou atualiza uma fila no banco de dados
    public function saveQueue($queueId, $userIds) {
        global $wpdb;
        $wpdb->replace($this->table_filas, [
            'id' => $queueId,
            'queue_name' => "Fila {$queueId}",
            'user_ids' => maybe_serialize($userIds),
        ]);
    }

    // Exclui uma fila do banco de dados
    public function deleteQueue($queueId) {
        global $wpdb;
        $wpdb->delete($this->table_filas, ['id' => $queueId]);
    }
}

add_action('wp_ajax_join_queue', 'handle_join_queue');
add_action('wp_ajax_nopriv_join_queue', 'handle_join_queue');

// Processa a ação de entrar na fila via AJAX
function handle_join_queue() {
    // Verifica o nonce para segurança
    check_ajax_referer('queue_system_nonce', 'nonce');

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