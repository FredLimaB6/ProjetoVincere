<?php
class DatabaseManager {
    private $wpdb;
    private $charset_collate;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    // Cria todas as tabelas necessárias
    public function createTables() {
        $this->createQueuesTable();
        $this->createMatchesTable();
        $this->createRankingsTable();
        $this->createRankingsHistoryTable();
    }

    // Tabela para filas
    private function createQueuesTable() {
        $table_name = $this->wpdb->prefix . 'filas_tabela';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            queue_name VARCHAR(255) NOT NULL,
            user_ids TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $this->charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // Tabela para partidas
    private function createMatchesTable() {
        $table_name = $this->wpdb->prefix . 'partidas_de_filas';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            map VARCHAR(255) NOT NULL,
            team_gr TEXT NOT NULL,
            team_bl TEXT NOT NULL,
            status ENUM('active', 'finished') DEFAULT 'active',
            PRIMARY KEY (id)
        ) $this->charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // Tabela para rankings
    private function createRankingsTable() {
        $table_name = $this->wpdb->prefix . 'game_system_rankings';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            player_id BIGINT(20) UNSIGNED NOT NULL,
            score INT NOT NULL,
            type ENUM('general', 'monthly') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $this->charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // Tabela para rankings históricos
    private function createRankingsHistoryTable() {
        $table_name = $this->wpdb->prefix . 'game_system_rankings_history';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            player_id BIGINT(20) UNSIGNED NOT NULL,
            score INT NOT NULL,
            type ENUM('general', 'monthly') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $this->charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
?>