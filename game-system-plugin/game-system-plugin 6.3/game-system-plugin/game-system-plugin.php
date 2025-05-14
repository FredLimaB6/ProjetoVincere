<?php
/*
Plugin Name: Sistema de Filas e Ranking
Description: Um plugin para gerenciar filas, partidas e rankings.
Version: 1.0.0.8
Author: Frederico Lima Baptista Duarte
*/

// Carrega os arquivos necessários
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-configurations.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/feedback.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/credits-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/badges-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/player-stats-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/global-stats-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/elo-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/log-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/managers/ranking-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/sistema-de-lobby/class-lobby-system.php';
require_once plugin_dir_path(__FILE__) . 'includes/sistema-de-lobby/lobby-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/sistema-de-lobby/lobby-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/sistema-de-filas/queue-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/sistema-de-filas/queue-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/sistema-de-filas/class-queue-system.php';
require_once plugin_dir_path(__FILE__) . 'includes/DatabaseManager.php';

// Inicializa o sistema de Filas
function game_system_init() {
    if (!class_exists('QueueSystem')) {
        error_log("Erro: A classe QueueSystem não foi carregada.");
        return;
    }

    if (!isset($GLOBALS['queueSystem'])) {
        $GLOBALS['queueSystem'] = new QueueSystem();
        error_log("Sistema de Filas inicializado com sucesso."); // Log para depuração
    }
}
add_action('plugins_loaded', 'game_system_init');

// Verifica se a classe QueueSystem está carregada
if (!class_exists('QueueSystem')) {
    error_log("Erro: A classe QueueSystem não foi carregada.");
    return;
} else {
    error_log("Classe QueueSystem carregada com sucesso.");
}

// Inicializa o sistema de rankings
function game_system_init_ranking() {
    if (!isset($GLOBALS['rankingManager'])) {
        $GLOBALS['rankingManager'] = new RankingManager();
        error_log("Sistema de Rankings inicializado com sucesso."); // Log para depuração
    }
}
add_action('plugins_loaded', 'game_system_init_ranking');

// Verifica e configura o banco de dados ao ativar o plugin
function game_system_activate() {
    // Inicializa o DatabaseManager para criar as tabelas necessárias
    $dbManager = new DatabaseManager();
    $dbManager->createTables(); // Cria todas as tabelas necessárias

    // Garante que as opções padrão do sistema sejam criadas
    $required_options = [
        'game_system_player_elo',
        'game_system_player_scores',
        'game_system_monthly_scores',
        'game_system_queues',
        'game_system_current_matches',
        'game_system_logs',
        'game_system_feedbacks',
        'game_system_feedback_categories',
        'game_system_banned_users',
    ];

    foreach ($required_options as $option) {
        if (!get_option($option)) {
            update_option($option, []); // Cria a opção com um valor padrão
        }
    }

    // Adiciona categorias padrão de feedbacks
    if (!get_option('game_system_feedback_categories')) {
        update_option('game_system_feedback_categories', ['Sugestões', 'Reclamações', 'Problemas técnicos']);
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
    $dbManager = new DatabaseManager();
    $dbManager->createTables();
}
register_activation_hook(__FILE__, 'game_system_create_tables');

// Enfileira o JavaScript para AJAX
function enqueue_game_system_scripts() {
    wp_enqueue_script(
        'sistema-filas-ajax',
        plugin_dir_url(__FILE__) . 'includes/sistema-de-filas/sistema-filas-ajax.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('sistema-filas-ajax', 'queueSystemAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('queue_system_nonce'),
        'pluginDirUrl' => plugin_dir_url(__FILE__),
    ]);

    // Enfileira o arquivo lobby-system.js
    wp_enqueue_script(
        'lobby-system-js',
        plugin_dir_url(__FILE__) . 'includes/sistema-de-lobby/js/lobby-system.js',
        ['jquery'],
        '1.0.0',
        true
    );

    // Passa variáveis para o arquivo lobby-system.js
    wp_localize_script('lobby-system-js', 'lobbySystemAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('game_system_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_game_system_scripts');

// Enfileira os estilos e scripts para o sistema de filas
function enqueue_game_queue_assets() {
    if (did_action('elementor/loaded')) {
        wp_enqueue_style(
            'game-widgets-styles',
            plugin_dir_url(__FILE__) . 'includes/elementor-widgets/game-widgets.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'barra-progresso',
            plugin_dir_url(__FILE__) . 'includes/sistema-de-filas/barra-progresso.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('barra-progresso', 'queueSystemAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('queue_system_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_game_queue_assets');

// Função para distribuir recompensas mensais
function distribute_monthly_rewards() {
    $rankingManager = new RankingManager();
    $creditsManager = new CreditsManager();
    $badgesManager = new BadgesManager();

    $monthlyRanking = $rankingManager->getMonthlyRanking();
    $topPlayers = array_slice($monthlyRanking, 0, 3, true);

    foreach ($topPlayers as $playerId => $score) {
        $creditsManager->addCredits($playerId, 100);
        $badgesManager->addBadge($playerId, 'Top 3 do Mês');
        error_log("Recompensa distribuída para o jogador ID {$playerId}: 100 créditos e badge 'Top 3 do Mês'.");
    }

    $rankingManager->saveMonthlyRankingToHistory();
    $rankingManager->resetMonthlyScores();
    error_log("Recompensas mensais distribuídas e rankings mensais resetados.");
}
add_action('init', 'distribute_monthly_rewards');

// Carrega o textdomain para tradução
function game_system_load_textdomain() {
    load_plugin_textdomain('game-system-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'game_system_load_textdomain');

function create_lobby_pages() {
    $pages = [
        'lobby' => '[lobby]',
        'partida-lobby' => '[lobby_match]',
    ];

    foreach ($pages as $slug => $shortcode) {
        if (!get_page_by_path($slug)) {
            wp_insert_post([
                'post_title' => ucfirst($slug),
                'post_name' => $slug,
                'post_content' => $shortcode,
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
        }
    }
}
register_activation_hook(__FILE__, 'create_lobby_pages');
register_activation_hook(__FILE__, 'game_system_create_tables');

// Registra os widgets personalizados do plugin
function register_game_widgets($widgets_manager) {
    require_once plugin_dir_path(__FILE__) . 'includes/elementor-widgets/game-widgets.php';

    $widgets_manager->register(new \Game_Match_Widget());
    $widgets_manager->register(new \Game_Ranking_Widget());
    $widgets_manager->register(new \Game_Achievements_Widget());
    $widgets_manager->register(new \Game_Store_Widget());
    $widgets_manager->register(new \Player_Stats_Widget());
}
add_action('elementor/widgets/register', 'register_game_widgets');

if (!did_action('elementor/loaded')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>O Elementor precisa estar ativo para usar os widgets do Game System Plugin.</p></div>';
    });
    return;
}

// Adiciona a categoria de widgets "Vincere" ao Elementor
function add_vincere_widget_category($elements_manager) {
    $elements_manager->add_category(
        'vincere',
        [
            'title' => __('Vincere', 'game-system-plugin'),
            'icon' => 'fa fa-plug',
        ]
    );
}
add_action('elementor/elements/categories_registered', 'add_vincere_widget_category');

// Enfileira estilos e scripts personalizados
function enqueue_custom_styles_and_scripts() {
    // Estilos globais
    wp_enqueue_style(
        'estilos-globais',
        plugin_dir_url(__FILE__) . 'estetica-e-estilos/estilos-globais.css',
        [],
        '1.0.0'
    );

    // Sobrescrições do Elementor
    wp_enqueue_style(
        'sobrescricoes-elementor',
        plugin_dir_url(__FILE__) . 'estetica-e-estilos/sobrescricoes-elementor.css',
        ['elementor-frontend'], // Garante que carregue após os estilos do Elementor
        '1.0.0'
    );

    // Animações personalizadas
    wp_enqueue_style(
        'animacoes',
        plugin_dir_url(__FILE__) . 'estetica-e-estilos/animacoes.css',
        [],
        '1.0.0'
    );

    // Scripts personalizados
    wp_enqueue_script(
        'scripts-personalizados',
        plugin_dir_url(__FILE__) . 'estetica-e-estilos/scripts-personalizados.js',
        ['jquery'],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles_and_scripts');
?>
