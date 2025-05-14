<?php
require_once plugin_dir_path(__FILE__) . 'queue-manager.php';
require_once plugin_dir_path(__FILE__) . '../managers/elo-manager.php';
require_once plugin_dir_path(__FILE__) . '../managers/log-manager.php';

class QueueSystem {
    private $queueManager;
    private $eloManager;
    private $logManager;

    public function __construct() {
        $this->queueManager = new QueueManager();
        $this->eloManager = new EloManager();
        $this->logManager = new LogManager();
        $this->initializeQueues();
    }

    // Gerenciamento de Logs
    public function logActivity($message) {
        $this->logManager->addLog($message);
    }

    public function getLogs() {
        return $this->logManager->getLogs();
    }

    // Gerenciamento de Filas (Delegado ao QueueManager)
    public function getQueues() {
        return $this->queueManager->getQueues();
    }

    public function saveQueue($queueId, $userIds) {
        $this->queueManager->saveQueue($queueId, $userIds);
    }

    public function deleteQueue($queueId) {
        $this->queueManager->deleteQueue($queueId);
    }

    public function joinQueue($userId) {
        $queues = $this->getQueues();

        // Verifica se o jogador já está em uma fila
        foreach ($queues as $queueId => $queue) {
            if (in_array($userId, $queue)) {
                return "Você já está na fila {$queueId}.";
            }
        }

        foreach ($queues as $queueId => $queue) {
            if (count($queue) < 10) {
                $queue[] = $userId;
                $this->saveQueue($queueId, $queue);
                $this->logActivity("Jogador ID {$userId} entrou na fila {$queueId}.");
                return "Você entrou na fila {$queueId}. Sua posição é " . count($queue) . ".";
            }
        }

        $newQueueId = count($queues) + 1;
        $this->saveQueue($newQueueId, [$userId]);
        $this->logActivity("Jogador ID {$userId} criou e entrou na nova fila {$newQueueId}.");
        return "Você entrou na nova fila {$newQueueId}. Sua posição é 1.";
    }

    public function leaveQueue($userId, $queueId) {
        $queues = $this->getQueues();

        if (!isset($queues[$queueId])) {
            return "A fila {$queueId} não existe.";
        }

        if (!in_array($userId, $queues[$queueId])) {
            return "Você não está na fila {$queueId}.";
        }

        $queues[$queueId] = array_diff($queues[$queueId], [$userId]);
        $this->logActivity("Jogador ID {$userId} saiu da fila {$queueId}.");

        if (empty($queues[$queueId])) {
            $this->deleteQueue($queueId);
            $this->logActivity("Fila {$queueId} foi removida por estar vazia.");
        } else {
            $this->saveQueue($queueId, $queues[$queueId]);
        }

        return "Você saiu da fila {$queueId}.";
    }

    public function initializeQueues() {
        $queues = $this->getQueues();
        if (empty($queues)) {
            $this->saveQueue(1, []);
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

    public function createMatch($players) {
        global $wpdb;

        // Valida os jogadores
        foreach ($players as $playerId) {
            if (!get_userdata($playerId)) {
                $this->logActivity("Tentativa de criar partida com jogador inválido: ID {$playerId}", 'error');
                return false; // Jogador inválido
            }
        }

        // Verifica duplicação de partidas
        $table_name = $wpdb->prefix . 'partidas_de_filas';
        $existingMatch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE status = 'active' AND team_gr = %s AND team_bl = %s", maybe_serialize($players), maybe_serialize($players)));
        if ($existingMatch) {
            $this->logActivity("Tentativa de criar partida duplicada com os mesmos jogadores.", 'warning');
            return false; // Partida duplicada
        }

        // Escolhe um mapa automaticamente
        $availableMaps = get_option('game_system_available_maps', ['Mapa 1', 'Mapa 2', 'Mapa 3']);
        if (empty($availableMaps)) {
            $this->logActivity("Erro ao criar partida: Nenhum mapa disponível.", 'error');
            return false; // Nenhum mapa disponível
        }
        $map = $availableMaps[array_rand($availableMaps)];

        // Divide os jogadores entre os times GR e BL
        shuffle($players);
        $team_gr = array_slice($players, 0, ceil(count($players) / 2));
        $team_bl = array_slice($players, ceil(count($players) / 2));

        // Insere a partida na tabela
        $wpdb->insert($table_name, [
            'map' => $map,
            'team_gr' => maybe_serialize($team_gr),
            'team_bl' => maybe_serialize($team_bl),
            'status' => 'active',
        ]);

        $matchId = $wpdb->insert_id;
        $this->logActivity("Partida criada com sucesso: ID {$matchId}, Mapa: {$map}, Times: GR (" . implode(', ', $team_gr) . ") vs BL (" . implode(', ', $team_bl) . ")", 'match');

        return $matchId; // Retorna o ID da partida criada
    }

    public function finishMatch($matchId, $winningTeam) {
        global $wpdb;

        // Recupera os dados da partida
        $table_name = $wpdb->prefix . 'partidas_de_filas';
        $match = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $matchId), ARRAY_A);

        if (!$match) {
            $this->logActivity("Tentativa de finalizar partida inexistente: ID {$matchId}", 'error');
            return false; // Partida não encontrada
        }

        $team_gr = maybe_unserialize($match['team_gr']);
        $team_bl = maybe_unserialize($match['team_bl']);
        $map = $match['map'];

        // Determina os vencedores e perdedores
        $winners = ($winningTeam === 'GR') ? $team_gr : $team_bl;
        $losers = ($winningTeam === 'GR') ? $team_bl : $team_gr;

        // Atualiza o ranking e as estatísticas dos jogadores
        $rankingManager = new RankingManager();
        $rankingManager->updateRankingsAfterMatch($winners, $losers);
        $playerStatsManager = new PlayerStatsManager();

        foreach ($winners as $playerId) {
            $rankingManager->updateGeneralScore($playerId, 10);
            $playerStatsManager->updatePlayerStats($playerId, $map, true);
        }

        foreach ($losers as $playerId) {
            $rankingManager->updateGeneralScore($playerId, -5);
            $playerStatsManager->updatePlayerStats($playerId, $map, false);
        }

        // Remove a partida da tabela
        $wpdb->delete($table_name, ['id' => $matchId]);

        $this->logActivity("Partida finalizada: ID {$matchId}, Vencedor: {$winningTeam}, Mapa: {$map}", 'match');

        return true;
    }
}
?>