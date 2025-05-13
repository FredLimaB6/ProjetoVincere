<?php
/*
Plugin Name: Sistema de Filas e Ranking
Description: Um plugin para gerenciar filas, partidas e rankings.
Version: 1.0.0.8
Author: Frederico Lima Baptista Duarte
*/

// Carrega os arquivos necessários
require_once plugin_dir_path(__FILE__) . 'includes/class-game-system.php';
require_once plugin_dir_path(__FILE__) . 'includes/elo-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/log-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/ranking-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-panel.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/feedback.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/credits-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/badges-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/player-stats-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/global-stats-manager.php';

// Inicializa o sistema
function game_system_init() {
    $GLOBALS['gameSystem'] = new GameSystem();
}
add_action('plugins_loaded', 'game_system_init');

// Verifica e configura o banco de dados ao ativar o plugin
function game_system_activate() {
    // Verifica e cria as opções necessárias
    $required_options = [
        'game_system_player_elo',
        'game_system_player_scores',
        'game_system_monthly_scores',
        'game_system_queues',
        'game_system_current_matches',
        'game_system_logs',
        'game_system_feedbacks',
        'game_system_banned_users',
    ];

    foreach ($required_options as $option) {
        if (!get_option($option)) {
            update_option($option, []);
        }
    }
}
register_activation_hook(__FILE__, 'game_system_activate');

// Verifica as opções ao carregar o plugin
function game_system_check_options() {
    $required_options = [
        'game_system_player_elo',
        'game_system_player_scores',
        'game_system_monthly_scores',
        'game_system_queues',
        'game_system_current_matches',
        'game_system_logs',
        'game_system_feedbacks',
        'game_system_banned_users',
    ];

    foreach ($required_options as $option) {
        if (!get_option($option)) {
            update_option($option, []); // Cria a opção com um valor padrão
        }
    }
}
add_action('plugins_loaded', 'game_system_check_options');

// Registra o shortcode [game_match] antes de criar a página
add_shortcode('game_match', 'display_match');

// Registra o shortcode [game_ranking_with_filters] antes de criar a página
add_shortcode('game_ranking_with_filters', 'display_ranking_with_filters');

// Cria páginas automaticamente ao ativar o plugin
function create_plugin_pages() {
    $pages = [
        'fila' => '[game_queue]',
        'partida' => '[game_match]',
        'ranking' => '[game_ranking_with_filters]',
        'feedbacks' => '[game_feedback_form]',
    ];

    foreach ($pages as $slug => $shortcode) {
        if (!get_page_by_path($slug)) {
            wp_insert_post([
                'post_title' => ucfirst($slug),
                'post_name' => $slug,
                'post_content' => shortcode_exists(trim($shortcode, '[]')) ? $shortcode : '',
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
        }
    }
}
register_activation_hook(__FILE__, 'create_plugin_pages');

// Cria tabelas personalizadas ao ativar o plugin
function game_system_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Exemplo de tabela para armazenar partidas
    $table_name = $wpdb->prefix . 'game_system_matches';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        match_data LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'game_system_create_tables');

// Enfileira o JavaScript para AJAX
function game_system_enqueue_scripts() {
    if (!is_admin()) {
        wp_enqueue_script(
            'game-system-ajax',
            plugin_dir_url(__FILE__) . 'assets/js/game-system.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('game-system-ajax', 'gameSystemAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('game_system_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'game_system_enqueue_scripts');

function distribute_monthly_rewards() {
    $rankingManager = new RankingManager();
    $creditsManager = new CreditsManager();
    $badgesManager = new BadgesManager();

    $monthlyRanking = $rankingManager->getMonthlyRanking();
    $topPlayers = array_slice($monthlyRanking, 0, 3, true);

    foreach ($topPlayers as $playerId => $score) {
        $creditsManager->addCredits($playerId, 100);
        $badgesManager->addBadge($playerId, 'Top 3 do Mês');
    }

    $rankingManager->saveMonthlyRankingToHistory();
    $rankingManager->resetMonthlyScores();
}
add_action('init', 'distribute_monthly_rewards');

function game_system_load_textdomain() {
    load_plugin_textdomain('game-system-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'game_system_load_textdomain');
?>
