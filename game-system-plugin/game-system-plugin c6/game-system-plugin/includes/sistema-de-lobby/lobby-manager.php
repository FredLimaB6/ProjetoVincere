<?php
class LobbyManager {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'lobby_teams';

        // Cria a tabela se não existir
        $this->createTable();
    }

    private function createTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            creator_id BIGINT(20) UNSIGNED NOT NULL,
            players TEXT NOT NULL,
            status ENUM('incomplete', 'complete') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // Função para registrar logs
    private function logActivity($message) {
        error_log("[LobbyManager] " . $message);
    }

    // Cria um novo time
    public function createTeam($creatorId) {
        global $wpdb;

        // Verifica se o usuário já está em um time
        if ($this->isUserInTeam($creatorId)) {
            return ['success' => false, 'message' => 'Você não consegue mais criar um time, porque já está em um.'];
        }

        $user = get_userdata($creatorId);
        $teamName = 'Time ' . ($user ? $user->user_login : 'Anônimo');

        $result = $wpdb->insert($this->table_name, [
            'name' => $teamName,
            'creator_id' => $creatorId,
            'players' => maybe_serialize([$creatorId]),
            'status' => 'incomplete',
        ]);

        if ($result) {
            $this->logActivity("Time criado: {$teamName} pelo usuário ID {$creatorId}");
            return ['success' => true, 'message' => 'Time criado com sucesso!', 'team_id' => $wpdb->insert_id];
        }

        return ['success' => false, 'message' => 'Erro ao criar o time.'];
    }

    // Adiciona um jogador ao time
    public function joinTeam($teamId, $playerId) {
        global $wpdb;

        $team = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $teamId), ARRAY_A);

        if (!$team) {
            return false;
        }

        $players = maybe_unserialize($team['players']);
        if (in_array($playerId, $players)) {
            return false;
        }

        $players[] = $playerId;
        $status = count($players) >= 5 ? 'complete' : 'incomplete';

        $wpdb->update($this->table_name, [
            'players' => maybe_serialize($players),
            'status' => $status,
        ], ['id' => $teamId]);

        $this->logActivity("Jogador ID {$playerId} entrou no time ID {$teamId}");
        return true;
    }

    // Remove um jogador do time
    public function leaveTeam($teamId, $playerId) {
        global $wpdb;

        // Verifica se o time existe
        $team = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $teamId), ARRAY_A);
        if (!$team) {
            return ['success' => false, 'message' => 'O time não existe.'];
        }

        $players = maybe_unserialize($team['players']);
        if (!in_array($playerId, $players)) {
            return ['success' => false, 'message' => 'Você não está neste time.'];
        }

        // Remove o jogador do time
        $players = array_diff($players, [$playerId]);

        if (empty($players)) {
            // Exclui o time se não houver mais jogadores
            $wpdb->delete($this->table_name, ['id' => $teamId]);
            $this->logActivity("Time ID {$teamId} foi excluído porque não há mais jogadores.");
            return ['success' => true, 'message' => 'O time foi excluído com sucesso.'];
        }

        $status = count($players) < 5 ? 'incomplete' : 'complete';

        // Atualiza os jogadores no banco de dados
        $wpdb->update($this->table_name, [
            'players' => maybe_serialize($players),
            'status' => $status,
        ], ['id' => $teamId]);

        $this->logActivity("Jogador ID {$playerId} saiu do time ID {$teamId}");
        return ['success' => true, 'message' => 'Você saiu do time com sucesso.'];
    }

    public function deleteTeam($teamId) {
        global $wpdb;
        $wpdb->delete($this->table_name, ['id' => $teamId]);
    }

    public function getTeams() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

        foreach ($results as &$team) {
            $team['players'] = maybe_unserialize($team['players']);
        }

        return $results;
    }

    public function isUserInTeam($userId) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

        foreach ($results as $team) {
            $players = maybe_unserialize($team['players']);
            if (in_array($userId, $players)) {
                return true;
            }
        }

        return false;
    }
}
?>