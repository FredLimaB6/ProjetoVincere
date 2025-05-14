<?php
class LobbyManager {
    private $teams = [];
    private $matches = [];

    public function __construct() {
        $this->teams = get_option('lobby_teams', []);
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
        update_option('lobby_teams', $this->teams);
        return $teamId;
    }

    public function getTeams() {
        return $this->teams;
    }

    public function joinTeam($teamId, $playerId) {
        if (isset($this->teams[$teamId]) && count($this->teams[$teamId]['players']) < 5) {
            $this->teams[$teamId]['players'][] = $playerId;
            if (count($this->teams[$teamId]['players']) === 5) {
                $this->teams[$teamId]['status'] = 'complete';
            }
            update_option('lobby_teams', $this->teams);
            return true;
        }
        return false;
    }

    public function leaveTeam($teamId, $playerId) {
        if (!isset($this->teams[$teamId])) {
            return ['success' => false, 'message' => 'O time não existe.'];
        }

        $team = $this->teams[$teamId];

        // Verifica se o jogador está no time
        if (!in_array($playerId, $team['players'])) {
            return ['success' => false, 'message' => 'Você não está neste time.'];
        }

        // Se o jogador for o dono do time
        if ($team['creator'] === $playerId) {
            if (count($team['players']) > 1) {
                return ['success' => false, 'message' => 'Você precisa remover todos os jogadores antes de fechar o time.'];
            }

            // Exclui o time se for o último jogador
            unset($this->teams[$teamId]);
            update_option('lobby_teams', $this->teams);
            return ['success' => true, 'message' => 'O time foi excluído com sucesso.'];
        }

        // Remove o jogador do time
        $this->teams[$teamId]['players'] = array_diff($team['players'], [$playerId]);
        update_option('lobby_teams', $this->teams);
        return ['success' => true, 'message' => 'Você saiu do time com sucesso.'];
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