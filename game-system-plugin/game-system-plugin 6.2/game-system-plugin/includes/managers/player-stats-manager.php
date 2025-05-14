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

    public function updatePlayerStats($playerId, $map, $isWin) {
        $stats = get_option('game_system_player_stats', []);

        if (!isset($stats[$playerId])) {
            $stats[$playerId] = [
                'total_matches' => 0,
                'wins' => 0,
                'losses' => 0,
                'maps_played' => [],
            ];
        }

        $stats[$playerId]['total_matches']++;
        if ($isWin) {
            $stats[$playerId]['wins']++;
        } else {
            $stats[$playerId]['losses']++;
        }

        if (!isset($stats[$playerId]['maps_played'][$map])) {
            $stats[$playerId]['maps_played'][$map] = 0;
        }
        $stats[$playerId]['maps_played'][$map]++;

        update_option('game_system_player_stats', $stats);
    }
}
