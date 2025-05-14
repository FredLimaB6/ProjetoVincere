<?php
class GlobalStatsManager {
    private $logManager;

    public function __construct() {
        $this->logManager = new LogManager();
    }

    // Retorna o total de partidas jogadas
    public function getTotalMatchesPlayed() {
        $logs = $this->logManager->getLogs();
        return count($logs);
    }

    // Retorna o total de jogadores registrados
    public function getTotalRegisteredPlayers() {
        $users = get_users(['role__not_in' => ['Administrator']]);
        return count($users);
    }

    // Retorna o total de reclamações enviadas
    public function getTotalComplaints() {
        $feedbacks = get_option('game_system_feedbacks', []);
        $complaints = array_filter($feedbacks, function ($feedback) {
            return isset($feedback['category']) && $feedback['category'] === 'Reclamações';
        });
        return count($complaints);
    }

    // Retorna todas as estatísticas globais
    public function getGlobalStats() {
        return [
            'total_matches' => $this->getTotalMatchesPlayed(),
            'total_players' => $this->getTotalRegisteredPlayers(),
            'total_complaints' => $this->getTotalComplaints(),
        ];
    }
}
?>