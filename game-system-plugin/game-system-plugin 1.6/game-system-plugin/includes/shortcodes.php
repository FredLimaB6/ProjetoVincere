<?php
if (!defined('ABSPATH')) {
    exit; // Sai se o arquivo for acessado diretamente.
}

// Inclui os arquivos de shortcodes
require_once plugin_dir_path(__FILE__) . 'shortcodes/match-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/ranking-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/achievements-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/store-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/player-stats-shortcode.php';

require_once plugin_dir_path(__FILE__) . 'sistema-de-lobby/lobby-shortcodes.php';

require_once plugin_dir_path(__FILE__) . 'managers/player-stats-manager.php';
require_once plugin_dir_path(__FILE__) . 'managers/badges-manager.php';
require_once plugin_dir_path(__FILE__) . 'managers/credits-manager.php';

// Registra os shortcodes
add_shortcode('game_match', 'display_match');
add_shortcode('game_ranking', 'display_ranking');
add_shortcode('game_achievements', 'display_achievements');
add_shortcode('game_store', 'display_store');
add_shortcode('player_stats', 'display_player_stats');
add_shortcode('game_ranking_with_filters', 'display_ranking_with_filters');

// Funções relacionadas ao sistema de filas e partidas foram removidas deste arquivo
// e centralizadas no arquivo queue-shortcode.php.
?>