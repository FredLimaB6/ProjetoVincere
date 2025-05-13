<?php
class RankingManager {
    private $playerScores = [];
    private $monthlyScores = [];

    public function __construct() {
        $this->playerScores = get_option('game_system_player_scores', []);
        $this->monthlyScores = get_option('game_system_monthly_scores', []);
    }

    // Gerenciamento do Ranking Geral
    public function getGeneralRanking() {
        if (empty($this->playerScores)) {
            return [];
        }
        arsort($this->playerScores);
        return $this->playerScores;
    }

    public function updateGeneralScore($playerId, $score) {
        $this->playerScores[$playerId] = ($this->playerScores[$playerId] ?? 0) + $score;
        update_option('game_system_player_scores', $this->playerScores);
    }

    public function saveGeneralRankingToHistory() {
        $history = get_option('game_system_general_ranking_history', []);
        $today = date('Y-m-d');

        if (!isset($history[$today])) {
            $history[$today] = $this->playerScores;
            update_option('game_system_general_ranking_history', $history);
        }
    }

    public function getGeneralRankingHistory() {
        return get_option('game_system_general_ranking_history', []);
    }

    // Gerenciamento do Ranking Mensal
    public function getMonthlyRanking() {
        if (empty($this->monthlyScores)) {
            return [];
        }
        arsort($this->monthlyScores);
        return $this->monthlyScores;
    }

    public function updateMonthlyScore($playerId, $score) {
        $this->monthlyScores[$playerId] = ($this->monthlyScores[$playerId] ?? 0) + $score;
        update_option('game_system_monthly_scores', $this->monthlyScores);
    }

    public function resetMonthlyScores() {
        $this->monthlyScores = [];
        update_option('game_system_monthly_scores', $this->monthlyScores);
    }

    public function saveMonthlyRankingToHistory() {
        $history = get_option('game_system_monthly_ranking_history', []);
        $history[date('Y-m')] = $this->monthlyScores;
        update_option('game_system_monthly_ranking_history', $history);
    }

    public function getMonthlyRankingHistory() {
        return get_option('game_system_monthly_ranking_history', []);
    }

    public function searchRankingHistory($month) {
        $history = get_option('game_system_monthly_ranking_history', []);
        return $history[$month] ?? [];
    }

    public function getAvailableHistoryMonths() {
        $history = get_option('game_system_monthly_ranking_history', []);
        return array_keys($history);
    }

    // Reseta o ranking mensal automaticamente no início de cada mês
    public function resetMonthlyRankingAutomatically() {
        $this->resetMonthlyScores();
        // Log de atividade (se necessário)
        if (class_exists('LogManager')) {
            $logManager = new LogManager();
            $logManager->addLog("Ranking mensal resetado automaticamente.");
        }
    }

    public function filterRanking($searchTerm, $ranking) {
        return array_filter($ranking, function ($playerId) use ($searchTerm) {
            return strpos((string)$playerId, $searchTerm) !== false;
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getRankingByType($type) {
        if ($type === 'monthly') {
            return $this->getMonthlyRanking();
        }
        return $this->getGeneralRanking();
    }

    public function filterRankingByType($type, $searchTerm) {
        $ranking = $this->getRankingByType($type);
        return $this->filterRanking($searchTerm, $ranking);
    }
}
?>