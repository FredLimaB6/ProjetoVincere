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

    public function updateGeneralScore($playerId, $score) {
        if (!is_numeric($playerId) || $playerId <= 0 || !is_numeric($score)) {
            throw new InvalidArgumentException('Dados inválidos fornecidos.');
        }
        $this->wpdb->replace($this->table, [
            'player_id' => $playerId,
            'score' => $score,
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

    public function updateMonthlyScore($playerId, $score) {
        if (!is_numeric($playerId) || $playerId <= 0 || !is_numeric($score)) {
            throw new InvalidArgumentException('Dados inválidos fornecidos.');
        }
        $this->wpdb->replace($this->table, [
            'player_id' => $playerId,
            'score' => $score,
            'type' => 'monthly',
            'created_at' => current_time('mysql'),
        ]);
    }

    public function resetMonthlyScores() {
        $this->wpdb->query("DELETE FROM {$this->table} WHERE type = 'monthly'");
    }

    public function saveMonthlyRankingToHistory() {
        // Lógica para salvar o ranking mensal no histórico
    }

    // Histórico de Rankings
    //Lógica para obter o histórico do ranking geral
    public function getGeneralRankingHistory() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table}_history WHERE type = 'general' ORDER BY created_at DESC", ARRAY_A);
    }

    //Lógica para obter o histórico do ranking mensal
    public function getMonthlyRankingHistory() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table}_history WHERE type = 'monthly' ORDER BY created_at DESC", ARRAY_A);
    }

    //Lógica para buscar no histório por mês
    public function searchRankingHistory($month) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT * FROM {$this->table}_history
            WHERE MONTH(created_at) = %d
        ", $month), ARRAY_A);
    }

    //Lógica para obter os meses disponíveis no histórico
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
        return $this->wpdb->get_results("SELECT * FROM {$this->table} WHERE type = '{$type}' ORDER BY score DESC", ARRAY_A);
    }

    public function filterRankingByType($type, $searchTerm) {
        $ranking = $this->getRankingByType($type);
        return $this->filterRanking($searchTerm, $ranking);
    }
}
?>