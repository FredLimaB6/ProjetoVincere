<?php
class PlayerStatsManager {
    private $logManager;

    public function __construct() {
        $this->logManager = new LogManager();
    }

    // Retorna as estatísticas detalhadas de um jogador
    public function getPlayerStats($playerId) {
        $logs = $this->logManager->getLogs();

        $totalMatches = 0;
        $wins = 0;
        $losses = 0;

        foreach ($logs as $log) {
            if (isset($log['match_data']) && in_array($playerId, $log['match_data']['players'])) {
                $totalMatches++;
                if ($log['match_data']['winner'] === $playerId) {
                    $wins++;
                } else {
                    $losses++;
                }
            }
        }

        return [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
        ];
    }
}
