<?php
if (!function_exists('selected')) {
    function selected($selected, $current, $echo = true) {
        $result = $selected == $current ? 'selected="selected"' : '';
        if ($echo) {
            echo $result;
        }
        return $result;
    }
}

// Adiciona o menu principal e os submenus no admin dashboard
function game_system_admin_menu() {
    // Menu principal
    add_menu_page(
        'Painel de Controle',
        'Painel Vincere',
        'manage_options',
        'game-system-panel',
        'game_system_dashboard_page',
        'dashicons-chart-bar',
        6
    );

    // Submenus
    add_submenu_page(
        'game-system-panel',
        'Rankings',
        'Rankings',
        'manage_options',
        'game-system-rankings',
        'game_system_rankings_page'
    );

    add_submenu_page(
        'game-system-panel',
        'Jogadores',
        'Jogadores',
        'manage_options',
        'game-system-players',
        'game_system_players_page'
    );

    add_submenu_page(
        'game-system-panel',
        'Configurações',
        'Configurações',
        'manage_options',
        'game-system-settings',
        'game_system_settings_page' // Chama a função do admin-settings.php
    );

    add_submenu_page(
        'game-system-panel',
        'Logs do Sistema',
        'Logs',
        'manage_options',
        'game-system-logs',
        'game_system_logs_page'
    );

    // Submenu para Estatísticas Gerais
    add_submenu_page(
        'game-system-panel',
        'Estatísticas Gerais',
        'Estatísticas',
        'manage_options',
        'game-system-general-stats',
        'game_system_general_stats_page'
    );

    // Adiciona o submenu para Feedbacks
    add_submenu_page(
        'game-system-panel',
        'Feedbacks',
        'Feedbacks',
        'manage_options',
        'game-system-feedbacks',
        'game_system_feedbacks_page'
    );
}
add_action('admin_menu', 'game_system_admin_menu');

// Página principal do Painel Vincere
function game_system_dashboard_page() {
    echo '<div class="wrap">';
    echo '<h1>Bem-vindo ao Painel Vincere</h1>';
    echo '<p>Use o menu à esquerda para navegar pelas funcionalidades do sistema.</p>';
    echo '</div>';
}

// Página de Rankings
function game_system_rankings_page() {
    if (!isset($GLOBALS['queueSystem'])) {
        echo '<p>O sistema de filas não está inicializado.</p>';
        return;
    }

    $rankingManager = new RankingManager();
    $ranking = $rankingManager->getGeneralRanking();

    echo '<div class="wrap">';
    echo '<h1>Ranking Geral</h1>';

    if (empty($ranking)) {
        echo '<p>Nenhum jogador no ranking ainda.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Jogador</th><th>Pontos</th></tr></thead>';
        echo '<tbody>';
        foreach ($ranking as $playerId => $score) {
            echo "<tr><td>{$playerId}</td><td>{$score}</td></tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Página de Jogadores
function game_system_players_page() {
    $bannedUsers = get_option('game_system_banned_users', []);

    echo '<div class="wrap">';
    echo '<h1>Gerenciamento de Jogadores</h1>';

    echo '<h2>Jogadores Proibidos</h2>';
    if (empty($bannedUsers)) {
        echo '<p>Nenhum jogador proibido.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID do Jogador</th><th>Ações</th></tr></thead>';
        echo '<tbody>';
        foreach ($bannedUsers as $userId) {
            echo "<tr><td>{$userId}</td><td>";
            echo '<form method="post">';
            echo '<input type="hidden" name="unban_user_id" value="' . esc_attr($userId) . '">';
            echo '<button type="submit" class="button button-secondary">Remover Proibição</button>';
            echo '</form>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Página de Logs do Sistema
function game_system_logs_page() {
    if (!isset($GLOBALS['queueSystem'])) {
        echo '<p>O sistema de filas não está inicializado.</p>';
        return;
    }

    $queueSystem = $GLOBALS['queueSystem'];
    $logs = $queueSystem->getLogs();

    echo '<div class="wrap">';
    echo '<h1>Logs do Sistema</h1>';

    if (empty($logs)) {
        echo '<p>Nenhum log disponível.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Data</th><th>Mensagem</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            echo "<tr><td>{$log['timestamp']}</td><td>{$log['message']}</td></tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Página de Estatísticas Gerais
function game_system_general_stats_page() {
    $playerStatsManager = new PlayerStatsManager();
    $globalStatsManager = new GlobalStatsManager();

    // Obter estatísticas
    $playerStats = $playerStatsManager->getPlayerStats(get_current_user_id());
    $globalStats = $globalStatsManager->getGlobalStats();

    // Determinar a visualização selecionada
    $view = $_GET['view'] ?? 'all';

    echo '<div class="wrap">';
    echo '<h1>Estatísticas Gerais</h1>';

    // Menu de navegação
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=game-system-general-stats&view=global" class="nav-tab ' . ($view === 'global' ? 'nav-tab-active' : '') . '">Estatísticas Globais</a>';
    echo '<a href="?page=game-system-general-stats&view=player" class="nav-tab ' . ($view === 'player' ? 'nav-tab-active' : '') . '">Estatísticas de Jogador</a>';
    echo '<a href="?page=game-system-general-stats&view=all" class="nav-tab ' . ($view === 'all' ? 'nav-tab-active' : '') . '">Todas as Estatísticas</a>';
    echo '</nav>';

    // Exibir estatísticas com base na visualização selecionada
    if ($view === 'global' || $view === 'all') {
        echo '<h2>Estatísticas Globais</h2>';
        echo '<p><strong>Total de Partidas Jogadas:</strong> ' . esc_html($globalStats['total_matches']) . '</p>';
        echo '<p><strong>Total de Jogadores Registrados:</strong> ' . esc_html($globalStats['total_players']) . '</p>';
        echo '<p><strong>Total de Reclamações:</strong> ' . esc_html($globalStats['total_complaints']) . '</p>';
    }

    if ($view === 'player' || $view === 'all') {
        echo '<h2>Estatísticas de Jogador</h2>';
        echo '<p><strong>Partidas Jogadas:</strong> ' . esc_html($playerStats['total_matches']) . '</p>';
        echo '<p><strong>Vitórias:</strong> ' . esc_html($playerStats['wins']) . '</p>';
        echo '<p><strong>Derrotas:</strong> ' . esc_html($playerStats['losses']) . '</p>';
    }

    echo '</div>';
}

// Página de Feedbacks
function game_system_feedbacks_page() {
    $feedbacks = get_option('game_system_feedbacks', []);
    $categories = get_option('game_system_feedback_categories', []);

    // Organiza os feedbacks por categoria
    $categorizedFeedbacks = [];
    foreach ($categories as $category) {
        $categorizedFeedbacks[$category] = [];
    }

    foreach ($feedbacks as $feedback) {
        if (isset($categorizedFeedbacks[$feedback['category']])) {
            $categorizedFeedbacks[$feedback['category']][] = $feedback;
        }
    }

    // Determina a aba ativa
    $activeTab = $_GET['tab'] ?? (count($categories) > 0 ? $categories[0] : '');

    echo '<div class="wrap">';
    echo '<h1>Feedbacks dos Usuários</h1>';

    // Menu de navegação entre as categorias
    echo '<nav class="nav-tab-wrapper">';
    foreach ($categories as $category) {
        $activeClass = ($activeTab === $category) ? 'nav-tab-active' : '';
        echo '<a href="?page=game-system-feedbacks&tab=' . urlencode($category) . '" class="nav-tab ' . $activeClass . '">' . esc_html($category) . '</a>';
    }
    echo '</nav>';

    echo '<a href="?page=game-system-feedbacks&export_feedbacks=1" class="button button-primary">Exportar Feedbacks</a>';

    // Exibe os feedbacks da aba ativa
    if (isset($categorizedFeedbacks[$activeTab])) {
        echo '<h2>' . esc_html($activeTab) . '</h2>';

        if (empty($categorizedFeedbacks[$activeTab])) {
            echo '<p>Nenhum feedback nesta categoria.</p>';
        } else {
            echo '<table class="widefat fixed">';
            echo '<thead><tr><th>Usuário</th><th>Mensagem</th><th>Data</th><th>Resposta</th><th>Ações</th></tr></thead>';
            echo '<tbody>';
            foreach ($categorizedFeedbacks[$activeTab] as $feedback) {
                $user = get_userdata($feedback['user_id']);
                $username = $user ? $user->user_login : 'Usuário Anônimo';
                $response = $feedback['response'] ? esc_html($feedback['response']) : 'Não respondido';

                echo "<tr>
                    <td>{$username}</td>
                    <td>{$feedback['message']}</td>
                    <td>{$feedback['date']}</td>
                    <td>{$response}</td>
                    <td>
                        <form method='post'>
                            <input type='hidden' name='feedback_index' value='{$feedback['index']}'>
                            <textarea name='feedback_response' rows='2' placeholder='Responder...'></textarea>
                            <button type='submit' class='button button-primary'>Enviar Resposta</button>
                        </form>
                    </td>
                </tr>";
            }
            echo '</tbody></table>';
        }
    } else {
        echo '<p>Categoria inválida ou não selecionada.</p>';
    }

    echo '</div>';
}
?>
