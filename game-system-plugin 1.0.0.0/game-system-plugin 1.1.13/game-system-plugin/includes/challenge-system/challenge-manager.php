<?php
require_once plugin_dir_path(__FILE__) . 'challenge-manager.php';

class ChallengeManager {
    private $teams = [];
    private $matches = [];

    public function __construct() {
        $this->teams = get_option('challenge_teams', []);
        $this->matches = get_option('challenge_matches', []);
    }

    public function createTeam($teamName, $creatorId) {
        $teamId = uniqid('team_');
        $this->teams[$teamId] = [
            'name' => $teamName,
            'creator' => $creatorId,
            'players' => [$creatorId],
            'status' => 'incomplete', // incomplete or complete
        ];
        update_option('challenge_teams', $this->teams);
        return $teamId;
    }

    public function joinTeam($teamId, $playerId) {
        if (isset($this->teams[$teamId]) && count($this->teams[$teamId]['players']) < 5) {
            $this->teams[$teamId]['players'][] = $playerId;
            if (count($this->teams[$teamId]['players']) === 5) {
                $this->teams[$teamId]['status'] = 'complete';
            }
            update_option('challenge_teams', $this->teams);
            return true;
        }
        return false;
    }

    public function getTeams() {
        return $this->teams;
    }

    public function createMatch($team1Id, $team2Id) {
        if (count($this->teams[$team1Id]['players']) < 5 || count($this->teams[$team2Id]['players']) < 5) {
            return false; // Time ainda não está completo
        }
        $matchId = uniqid('match_');
        $this->matches[$matchId] = [
            'team1' => $team1Id,
            'team2' => $team2Id,
            'status' => 'pending', // pending, ongoing, or completed
        ];
        update_option('challenge_matches', $this->matches);
        return $matchId;
    }

    public function getMatches() {
        return $this->matches;
    }
}
?>