<?php
require_once plugin_dir_path(__FILE__) . '../managers/elo-manager.php';
require_once plugin_dir_path(__FILE__) . '../managers/log-manager.php';

class QueueSystem {
    private $queues = [];
    private $currentMatches = [];
    private $eloManager;
    private $logManager;

    public function __construct() {
        $this->createFilasTable(); // Cria a tabela de filas
        $this->createTables(); // Cria a tabela de partidas
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

        // Verifica se o jogador já está em uma fila
        foreach ($this->queues as $queueId => $queue) {
            if (in_array($userId, $queue)) {
                return "Você já está na fila {$queueId}.";
            }
        }

        foreach ($this->queues as $queueId => $queue) {
            if (count($queue) < 10) {
                $this->queues[$queueId][] = $userId;
                $this->setQueues($this->queues);
                $this->logActivity("Jogador ID {$userId} entrou na fila {$queueId}.");
                return "Você entrou na fila {$queueId}. Sua posição é " . count($queue) . ".";
            }
        }

        $newQueueId = count($this->queues) + 1;
        $this->queues[$newQueueId] = [$userId];
        $this->setQueues($this->queues);
        $this->logActivity("Jogador ID {$userId} criou e entrou na nova fila {$newQueueId}.");
        return "Você entrou na nova fila {$newQueueId}. Sua posição é 1.";
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

    // Criação de tabelas no banco de dados
    public function createTables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'partidas_de_filas';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            map VARCHAR(255) NOT NULL,
            team_gr TEXT NOT NULL, -- IDs dos jogadores no time GR
            team_bl TEXT NOT NULL, -- IDs dos jogadores no time BL
            status ENUM('active', 'finished') DEFAULT 'active',
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function createFilasTable() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'filas_tabela'; // Tabela para filas
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            queue_name VARCHAR(255) NOT NULL,
            user_ids TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
?>