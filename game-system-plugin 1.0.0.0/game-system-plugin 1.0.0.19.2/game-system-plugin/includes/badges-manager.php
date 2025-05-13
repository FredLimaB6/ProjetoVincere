<?php
class BadgesManager {
    private $playerBadges = [];

    public function __construct() {
        $this->playerBadges = get_option('game_system_player_badges', []);
    }

    public function getBadges($playerId) {
        return $this->playerBadges[$playerId] ?? [];
    }

    public function addBadge($playerId, $badge) {
        if (!isset($this->playerBadges[$playerId])) {
            $this->playerBadges[$playerId] = [];
        }
        if (!in_array($badge, $this->playerBadges[$playerId])) {
            $this->playerBadges[$playerId][] = $badge;
            update_option('game_system_player_badges', $this->playerBadges);
        }
    }

    public function addAchievement($playerId, $achievement) {
        if (!isset($this->playerBadges[$playerId])) {
            $this->playerBadges[$playerId] = [];
        }
        if (!in_array($achievement, $this->playerBadges[$playerId])) {
            $this->playerBadges[$playerId][] = $achievement;
            update_option('game_system_player_badges', $this->playerBadges);
        }
    }

    public function getAllBadges() {
        return $this->playerBadges;
    }
}
?>