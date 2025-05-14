<?php
require_once plugin_dir_path(__FILE__) . '../managers/elo-manager.php';
require_once plugin_dir_path(__FILE__) . '../managers/log-manager.php';

class QueueSystem {
    private $queues = [];
    private $currentMatches = [];
    private $eloManager;
    private $logManager;

    public function __construct() {
        $this->queues = get_option('game_system_queues', []);
        $this->currentMatches = get_option('game_system_current_matches', []);
        $this->eloManager = new EloManager();
        $this->logManager = new LogManager();
        $this->initializeQueues();
    }

    public function __destruct() {
        update_option('game_system_queues', $this->queues);
        update_option('game_system_current_matches', $this->currentMatches);
    }

    // Gerenciamento de Logs
    public function logActivity($message) {
        $this->logManager->addLog($message);
    }

    public function getLogs() {
        return $this->logManager->getLogs();
    }

    // Gerenciamento de Filas
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

        foreach ($this->queues as $queueId => $queue) {
            if (in_array($userId, $queue)) {
                $this->queues[$queueId] = array_diff($queue, [$userId]);
            }
        }

        foreach ($this->queues as $queueId => $queue) {
            if (count($queue) < 10) {
                $this->queues[$queueId][] = $userId;
                $this->setQueues($this->queues);
                $this->logActivity("Jogador ID {$userId} entrou na fila {$queueId}.");
                return "Você entrou na fila {$queueId}.";
            }
        }

        $newQueueId = count($this->queues) + 1;
        $this->queues[$newQueueId] = [$userId];
        $this->setQueues($this->queues);
        $this->logActivity("Jogador ID {$userId} criou e entrou na nova fila {$newQueueId}.");
        return "Você entrou na nova fila {$newQueueId}.";
    }

    public function leaveQueue($userId, $queueId) {
        if (!isset($this->queues[$queueId])) {
            return "A fila {$queueId} não existe.";
        }

        if (!in_array($userId, $this->queues[$queueId])) {
            return "Você não está na fila {$queueId}.";
        }

        $this->queues[$queueId] = array_diff($this->queues[$queueId], [$userId]);
        $this->logActivity("Jogador ID {$userId} saiu da fila {$queueId}.");

        if (empty($this->queues[$queueId])) {
            unset($this->queues[$queueId]);
            $this->logActivity("Fila {$queueId} foi removida por estar vazia.");
        }

        $this->setQueues($this->queues);
        return "Você saiu da fila {$queueId}.";
    }

    public function initializeQueues() {
        if (empty($this->queues)) {
            $this->queues[1] = [];
            $this->logActivity("Fila padrão criada automaticamente.");
        }
    }

    // Gerenciamento de Partidas
    public function isGameActive() {
        return !empty($this->currentMatches);
    }

    public function getCurrentMatch() {
        return reset($this->currentMatches);
    }
}
?>