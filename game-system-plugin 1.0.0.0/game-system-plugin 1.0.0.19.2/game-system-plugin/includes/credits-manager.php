<?php
class CreditsManager {
    private $playerCredits = [];

    public function __construct() {
        $this->playerCredits = get_option('game_system_player_credits', []);
    }

    public function getCredits($playerId) {
        return $this->playerCredits[$playerId] ?? 0;
    }

    public function addCredits($playerId, $amount) {
        $this->playerCredits[$playerId] = ($this->playerCredits[$playerId] ?? 0) + $amount;
        update_option('game_system_player_credits', $this->playerCredits);
    }

    public function deductCredits($playerId, $amount) {
        $this->playerCredits[$playerId] = max(0, ($this->playerCredits[$playerId] ?? 0) - $amount);
        update_option('game_system_player_credits', $this->playerCredits);
    }

    public function getAllCredits() {
        return $this->playerCredits;
    }

    public function purchaseItem($playerId, $itemCost) {
        if ($this->getCredits($playerId) >= $itemCost) {
            $this->deductCredits($playerId, $itemCost);
            return true;
        }
        return false;
    }
}
?>