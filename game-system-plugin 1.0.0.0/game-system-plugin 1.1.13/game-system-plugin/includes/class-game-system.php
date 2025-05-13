<?php
require_once plugin_dir_path(__FILE__) . 'managers/elo-manager.php';
require_once plugin_dir_path(__FILE__) . 'managers/log-manager.php';
require_once plugin_dir_path(__FILE__) . 'managers/ranking-manager.php';

class GameSystem {
    private $queues = [];
    private $currentMatches = [];
    private $eloManager;
    private $logManager;
    private $rankingManager;

    public function __construct() {
        $this->queues = get_option('game_system_queues', []);
        $this->currentMatches = get_option('game_system_current_matches', []);
        $this->eloManager = new EloManager();
        $this->logManager = new LogManager();
        $this->rankingManager = new RankingManager();
        $this->initializeQueues();
    }

    public function __destruct() {
        update_option('game_system_queues', $this->queues);
        update_option('game_system_current_matches', $this->currentMatches);
    }

    public function logActivity($message) {
        $this->logManager->addLog($message);
    }

    public function getLogs() {
        return $this->logManager->getLogs();
    }

    public function getQueues() {
        return $this->queues;
    }

    public function setQueues($queues) {
        $this->queues = $queues;
        update_option('game_system_queues', $this->queues);
    }

    public function joinQueue($userId) {
        if (!is_numeric($userId) || $userId <= 0) {
            return "ID de usuário inválido.";
        }

        // Remove o usuário de qualquer fila existente
        foreach ($this->queues as $queueId => $queue) {
            if (in_array($userId, $queue)) {
                $this->queues[$queueId] = array_diff($queue, [$userId]);
            }
        }

        // Adiciona o usuário à primeira fila disponível
        foreach ($this->queues as $queueId => $queue) {
            if (count($queue) < 10) {
                $this->queues[$queueId][] = $userId;
                $this->setQueues($this->queues); // Salva as alterações no banco de dados
                $this->logActivity("Jogador ID {$userId} entrou na fila {$queueId}.");
                return "Você entrou na fila {$queueId}.";
            }
        }

        // Cria uma nova fila se todas estiverem cheias
        $newQueueId = count($this->queues) + 1;
        $this->queues[$newQueueId] = [$userId];
        $this->setQueues($this->queues); // Salva as alterações no banco de dados
        $this->logActivity("Jogador ID {$userId} criou e entrou na nova fila {$newQueueId}.");
        return "Você entrou na nova fila {$newQueueId}.";
    }

    public function leaveQueue($userId, $queueId) {
        if (!isset($this->queues[$queueId])) {
            error_log("Erro: A fila {$queueId} não existe.");
            return "A fila {$queueId} não existe.";
        }

        if (!in_array($userId, $this->queues[$queueId])) {
            error_log("Erro: Usuário {$userId} não está na fila {$queueId}.");
            return "Você não está na fila {$queueId}.";
        }

        // Remove o usuário da fila
        $this->queues[$queueId] = array_diff($this->queues[$queueId], [$userId]);
        $this->logActivity("Jogador ID {$userId} saiu da fila {$queueId}.");
        error_log("Usuário {$userId} removido da fila {$queueId}.");

        // Remove a fila se estiver vazia
        if (empty($this->queues[$queueId])) {
            unset($this->queues[$queueId]);
            $this->logActivity("Fila {$queueId} foi removida por estar vazia.");
            error_log("Fila {$queueId} removida por estar vazia.");
        }

        $this->setQueues($this->queues); // Salva as alterações no banco de dados
        return "Você saiu da fila {$queueId}.";
    }

    public function initializeQueues() {
        if (empty($this->queues)) {
            $this->queues[1] = []; // Cria uma fila padrão com ID 1
            $this->logActivity("Fila padrão criada automaticamente."); // Log de atividade

            // Salva a fila padrão no banco de dados
            global $wpdb;
            $table_name = $wpdb->prefix . 'game_system_queues';
            $wpdb->insert($table_name, [
                'queue_data' => json_encode($this->queues[1]),
            ]);

            error_log("Fila padrão criada e salva no banco de dados: " . print_r($this->queues, true)); // Log para depuração
        }
    }

    public function updateGeneralScore($playerId, $score) {
        $this->rankingManager->updateGeneralScore($playerId, $score);
    }

    public function updateMonthlyScore($playerId, $score) {
        $this->rankingManager->updateMonthlyScore($playerId, $score);
    }

    public function getScores() {
        $scores = $this->rankingManager->getGeneralRanking();
        if (empty($scores)) {
            return [];
        }
        return $scores;
    }

    public function isGameActive() {
        return !empty($this->currentMatches);
    }

    public function getCurrentMatch() {
        return reset($this->currentMatches); // Retorna a primeira partida ativa
    }
}
