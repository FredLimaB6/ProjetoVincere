<?php
require_once plugin_dir_path(__FILE__) . 'lobby-manager.php';

class LobbySystem {
    private $lobbyManager;

    public function __construct() {
        $this->lobbyManager = new LobbyManager();
    }

    public function getLobbyManager() {
        return $this->lobbyManager;
    }
}
?>