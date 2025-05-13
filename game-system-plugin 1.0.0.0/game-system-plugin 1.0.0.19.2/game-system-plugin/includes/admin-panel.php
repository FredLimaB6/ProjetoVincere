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
    if (!isset($GLOBALS['gameSystem'])) {
        return '<p>O sistema não está inicializado.</p>';
    }
    $gameSystem = $GLOBALS['gameSystem'];
    $rankingManager = new RankingManager();

    echo '<div class="wrap">';
    echo '<h1>Rankings</h1>';

    // Adiciona o formulário de seleção de tipo de ranking e busca
    $currentType = $_GET['ranking_type'] ?? 'general';
    echo '<form method="get" style="margin-bottom: 20px;">';
    echo '<input type="hidden" name="page" value="game-system-rankings">';
    echo '<label for="ranking_type">Tipo de Ranking:</label>';
    echo '<select name="ranking_type" id="ranking_type">';
    echo '<option value="general"' . selected($currentType, 'general', false) . '>Geral</option>';
    echo '<option value="monthly"' . selected($currentType, 'monthly', false) . '>Mensal</option>';
    echo '</select>';
    echo '<input type="text" name="search_player" placeholder="Buscar por ID ou Nome" value="' . esc_attr($_GET['search_player'] ?? '') . '">';
    echo '<button type="submit" class="button button-primary">Filtrar</button>';
    echo '</form>';

    // Obtém o ranking com base no tipo selecionado
    $ranking = $rankingManager->getRankingByType($currentType);

    // Aplica o filtro de busca, se necessário
    if (isset($_GET['search_player'])) {
        $searchTerm = sanitize_text_field($_GET['search_player']);
        $ranking = $rankingManager->filterRanking($searchTerm, $ranking);
    }

    echo '<h2>Ranking ' . ($currentType === 'monthly' ? 'Mensal' : 'Geral') . '</h2>';
    if (empty($ranking)) {
        echo '<p>Nenhum jogador no ranking ainda.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Jogador</th><th>Pontos</th><th>ELO</th></thead>';
        echo '<tbody>';
        foreach ($ranking as $playerId => $score) {
            $elo = $gameSystem->eloManager->getElo($playerId) ?? 1000;
            echo "<tr><td>{$playerId}</td><td>{$score}</td><td>{$elo}</td></tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Página de Jogadores
function game_system_players_page() {
    if (!isset($GLOBALS['gameSystem'])) {
        return '<p>O sistema não está inicializado.</p>';
    }
    $bannedUsers = get_option('game_system_banned_users', []);

    echo '<div class="wrap">';
    echo '<h1>Gerenciamento de Jogadores</h1>';
    echo '<h2>Proibir Jogadores</h2>';
    ?>
    <form method="post">
        <label for="ban_user_id">Proibir Usuário (ID):</label>
        <input type="number" name="ban_user_id" id="ban_user_id" required>
        <button type="submit" class="button button-primary">Proibir</button>
    </form>
    <?php

    echo '<h3>Jogadores Proibidos</h3>';
    if (empty($bannedUsers)) {
        echo '<p>Nenhum jogador proibido.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID do Usuário</th><th>Ações</th></tr></thead>';
        echo '<tbody>';
        foreach ($bannedUsers as $userId) {
            echo "<tr><td>{$userId}</td><td>";
            ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="unban_user_id" value="<?php echo esc_attr($userId); ?>">
                <button type="submit" class="button button-secondary">Remover Proibição</button>
            </form>
            <?php
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

// Página de Logs do Sistema
function game_system_logs_page() {
    if (!isset($GLOBALS['gameSystem'])) {
        return '<p>O sistema não está inicializado.</p>';
    }
    $gameSystem = $GLOBALS['gameSystem'];
    $logs = $gameSystem->getLogs();

    echo '<div class="wrap">';
    echo '<h1>Logs do Sistema</h1>';

    if (empty($logs)) {
        echo '<p>Nenhum log disponível.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Data</th><th>Mensagem</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            echo "<tr><td>" . esc_html($log['timestamp']) . "</td><td>" . esc_html($log['message']) . "</td></tr>";
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
?>
