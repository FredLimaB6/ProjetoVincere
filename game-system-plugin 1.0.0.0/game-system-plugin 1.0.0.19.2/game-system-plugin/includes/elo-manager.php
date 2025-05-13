<?php
class EloManager {
    private $playerElo = [];

    public function __construct() {
        $this->playerElo = get_option('game_system_player_elo', []);
    }

    public function getElo($playerId) {
        return $this->playerElo[$playerId] ?? 1000; // ELO padrão
    }

    public function setElo($playerId, $elo) {
        $this->playerElo[$playerId] = $elo;
        update_option('game_system_player_elo', $this->playerElo);
    }

    public function adjustElo($playerId, $adjustment) {
        $currentElo = $this->getElo($playerId);
        $newElo = max(0, $currentElo + $adjustment); // Garante que o ELO não seja negativo
        $this->setElo($playerId, $newElo);
    }

    public function getAllElo() {
        return $this->playerElo;
    }
}
?>