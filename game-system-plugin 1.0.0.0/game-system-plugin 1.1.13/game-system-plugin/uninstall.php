<?php
// Se o WordPress não estiver desinstalando o plugin, saia.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Limpa as opções ou dados criados pelo plugin no banco de dados
global $wpdb;

// Exclui tabelas personalizadas
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}game_system_queues");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}game_system_matches");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}game_system_rankings");

// Exclui qualquer dado relacionado ao plugin, como pontuações ou filas
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'game_system_%'");

// Exclui tabelas personalizadas, se houver
// Exemplo: $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}game_system_scores");

// Exclui as páginas criadas pelo plugin
$pages = ['fila', 'partida', 'ranking', 'feedbacks']; // Inclui a página de feedbacks
foreach ($pages as $page_slug) {
    $page = get_page_by_path($page_slug);
    if ($page) {
        wp_delete_post($page->ID, true); // Exclui permanentemente a página
    }
}

// Exclui dados persistentes
delete_option('game_system_player_elo');
delete_option('game_system_monthly_scores');
delete_option('game_system_player_scores');
delete_option('game_system_queues');
delete_option('game_system_current_matches');
delete_option('game_system_logs');
delete_option('game_system_feedbacks');
delete_option('game_system_banned_users');
delete_option('game_system_feedback_categories');
delete_option('game_system_available_maps');
delete_option('game_system_max_players_per_queue');
?>