<?php
class LogManager {
    private $logs = [];

    public function __construct() {
        $this->logs = get_option('game_system_logs', []);
    }

    public function addLog($message, $type = 'info') {
        $this->logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type, // Tipo do log (info, error, warning, etc.)
            'message' => $message,
        ];
        update_option('game_system_logs', $this->logs);
    }

    public function addMatchLog($matchId, $action, $details = '') {
        $this->addLog("Partida ID {$matchId}: {$action}. {$details}", 'match');
    }

    public function addErrorLog($errorMessage) {
        $this->addLog("Erro: {$errorMessage}", 'error');
    }

    public function getLogs() {
        return $this->logs;
    }
}

function export_logs_to_csv() {
    if (isset($_GET['export_logs'])) {
        $logs = get_option('game_system_logs', []);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="logs.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Data', 'Tipo', 'Mensagem']);
        foreach ($logs as $log) {
            fputcsv($output, [$log['timestamp'], $log['type'], $log['message']]);
        }
        fclose($output);
        exit;
    }
}
add_action('admin_init', 'export_logs_to_csv');
?>