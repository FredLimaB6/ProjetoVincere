<?php
require_once plugin_dir_path(__FILE__) . 'challenge-manager.php';

class ChallengeSystem {
    private $challengeManager;

    public function __construct() {
        $this->challengeManager = new ChallengeManager();
    }

    public function getChallengeManager() {
        return $this->challengeManager;
    }
}
?>