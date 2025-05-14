<?php
class RankingManager {
    private $wpdb;
    private $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'game_system_rankings';
    }

    // Gerenciamento do Ranking Geral
    public function getGeneralRanking() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table} WHERE type = 'general' ORDER BY score DESC", ARRAY_A);
    }

    public function updateGeneralScore($playerId, $scoreAdjustment) {
        if (!is_numeric($playerId) || $playerId <= 0 || !is_numeric($scoreAdjustment)) {
            throw new InvalidArgumentException('Dados inválidos fornecidos.');
        }

        // Recupera o score atual do jogador
        $currentScore = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT score FROM {$this->table} WHERE player_id = %d AND type = 'general'",
            $playerId
        ));

        // Calcula o novo score
        $newScore = ($currentScore !== null) ? $currentScore + $scoreAdjustment : $scoreAdjustment;

        // Atualiza ou insere o score no banco de dados
        $this->wpdb->replace($this->table, [
            'player_id' => $playerId,
            'score' => max(0, $newScore), // Garante que o score não seja negativo
            'type' => 'general',
            'created_at' => current_time('mysql'),
        ]);
    }

    public function saveGeneralRankingToHistory() {
        $this->wpdb->query("
            INSERT INTO {$this->table}_history (player_id, score, type, created_at)
            SELECT player_id, score, 'general', NOW()
            FROM {$this->table}
            WHERE type = 'general'
        ");
    }

    // Gerenciamento do Ranking Mensal
    public function getMonthlyRanking() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table} WHERE type = 'monthly' ORDER BY score DESC", ARRAY_A);
    }

    public function updateMonthlyScore($playerId, $scoreAdjustment) {
        if (!is_numeric($playerId) || $playerId <= 0 || !is_numeric($scoreAdjustment)) {
            throw new InvalidArgumentException('Dados inválidos fornecidos.');
        }

        // Recupera o score atual do jogador
        $currentScore = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT score FROM {$this->table} WHERE player_id = %d AND type = 'monthly'",
            $playerId
        ));

        // Calcula o novo score
        $newScore = ($currentScore !== null) ? $currentScore + $scoreAdjustment : $scoreAdjustment;

        // Atualiza ou insere o score no banco de dados
        $this->wpdb->replace($this->table, [
            'player_id' => $playerId,
            'score' => max(0, $newScore), // Garante que o score não seja negativo
            'type' => 'monthly',
            'created_at' => current_time('mysql'),
        ]);
    }

    public function resetMonthlyScores() {
        $this->wpdb->query("DELETE FROM {$this->table} WHERE type = 'monthly'");
    }

    public function saveMonthlyRankingToHistory() {
        $this->wpdb->query("
            INSERT INTO {$this->table}_history (player_id, score, type, created_at)
            SELECT player_id, score, 'monthly', NOW()
            FROM {$this->table}
            WHERE type = 'monthly'
        ");
    }

    // Histórico de Rankings
    public function getGeneralRankingHistory() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table}_history WHERE type = 'general' ORDER BY created_at DESC", ARRAY_A);
    }

    public function getMonthlyRankingHistory() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table}_history WHERE type = 'monthly' ORDER BY created_at DESC", ARRAY_A);
    }

    public function searchRankingHistory($month) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT * FROM {$this->table}_history
            WHERE MONTH(created_at) = %d
        ", $month), ARRAY_A);
    }

    public function getAvailableHistoryMonths() {
        return $this->wpdb->get_col("SELECT DISTINCT MONTH(created_at) FROM {$this->table}_history ORDER BY MONTH(created_at)");
    }

    // Filtros de Ranking
    public function filterRanking($searchTerm, $ranking) {
        return array_filter($ranking, function ($entry) use ($searchTerm) {
            return stripos($entry['player_id'], $searchTerm) !== false;
        });
    }

    public function getRankingByType($type) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE type = %s ORDER BY score DESC",
            $type
        ), ARRAY_A);
    }

    public function filterRankingByType($type, $searchTerm) {
        $ranking = $this->getRankingByType($type);
        return $this->filterRanking($searchTerm, $ranking);
    }

    // Atualização Automática do Ranking ao Final de uma Partida
    public function updateRankingsAfterMatch($winningTeam, $losingTeam, $scoreAdjustmentWin = 10, $scoreAdjustmentLoss = -5) {
        foreach ($winningTeam as $playerId) {
            $this->updateGeneralScore($playerId, $scoreAdjustmentWin);
            $this->updateMonthlyScore($playerId, $scoreAdjustmentWin);
        }

        foreach ($losingTeam as $playerId) {
            $this->updateGeneralScore($playerId, $scoreAdjustmentLoss);
            $this->updateMonthlyScore($playerId, $scoreAdjustmentLoss);
        }
    }
}
?>