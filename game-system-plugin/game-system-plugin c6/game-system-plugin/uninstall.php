<?php
// Sai se o WordPress não estiver desinstalando o plugin
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Global do banco de dados
global $wpdb;

// Exclui tabelas personalizadas criadas pelo plugin
$tables = [
    "{$wpdb->prefix}game_system_matches",
    "{$wpdb->prefix}game_system_queues", // Tabela antiga de filas
    "{$wpdb->prefix}game_system_rankings",
    "{$wpdb->prefix}lobby_teams", // Tabela dos times do lobby
    "{$wpdb->prefix}filas_tabela", // Tabela das filas
    "{$wpdb->prefix}partidas_de_filas", // Tabela das partidas de filas
];
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Exclui opções criadas pelo plugin
$options = [
    'game_system_queues',
    'game_system_player_elo',
    'game_system_player_scores',
    'game_system_monthly_scores',
    'game_system_current_matches',
    'game_system_logs',
    'game_system_feedbacks',
    'game_system_feedback_categories',
    'game_system_banned_users',
    'game_system_available_maps',
    'game_system_max_players_per_queue',
    'game_system_general_ranking_history',
    'game_system_monthly_ranking_history',
];
foreach ($options as $option) {
    delete_option($option);
}

// Exclui páginas criadas pelo plugin
$pages = ['fila', 'partida', 'ranking', 'feedbacks', 'lobby', 'partida-lobby'];
foreach ($pages as $page_slug) {
    $page = get_page_by_path($page_slug);
    if ($page) {
        wp_delete_post($page->ID, true); // Exclui permanentemente a página
    }
}

// Exclui arquivos temporários ou logs criados pelo plugin (se houver)
$upload_dir = wp_upload_dir();
$log_file = $upload_dir['basedir'] . '/game-system-logs.txt';
if (file_exists($log_file)) {
    unlink($log_file);
}

// Limpa qualquer cache relacionado ao plugin
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}
?>