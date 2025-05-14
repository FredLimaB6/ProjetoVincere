<?php
require_once plugin_dir_path(__FILE__) . '../managers/log-manager.php';

class QueueSystem {
    private $queues = [];
    private $logManager;

    public function __construct() {
        $this->queues = get_option('game_system_queues', []);
        $this->logManager = new LogManager();
    }

    public function getQueues() {
        return $this->queues;
    }

    public function joinQueue($userId) {
        foreach ($this->queues as $queueId => $queue) {
            if (in_array($userId, $queue)) {
                return "Você já está na fila {$queueId}.";
            }
        }

        foreach ($this->queues as $queueId => $queue) {
            if (count($queue) < 10) {
                $this->queues[$queueId][] = $userId;
                $this->saveQueues();
                $this->logManager->addLog("Jogador {$userId} entrou na fila {$queueId}.");
                return "Você entrou na fila {$queueId}.";
            }
        }

        $newQueueId = count($this->queues) + 1;
        $this->queues[$newQueueId] = [$userId];
        $this->saveQueues();
        $this->logManager->addLog("Jogador {$userId} criou e entrou na fila {$newQueueId}.");
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
        if (empty($this->queues[$queueId])) {
            unset($this->queues[$queueId]);
        }

        $this->saveQueues();
        $this->logManager->addLog("Jogador {$userId} saiu da fila {$queueId}.");
        return "Você saiu da fila {$queueId}.";
    }

    private function saveQueues() {
        update_option('game_system_queues', $this->queues);
    }
}
?>