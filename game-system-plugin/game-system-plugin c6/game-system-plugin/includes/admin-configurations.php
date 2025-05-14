<?php

// Adiciona o menu principal e os submenus no admin dashboard
function game_system_admin_menu() {
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
        'Configurações',
        'Configurações',
        'manage_options',
        'game-system-settings',
        'game_system_settings_page'
    );

    add_submenu_page(
        'game-system-panel',
        'Feedbacks',
        'Feedbacks',
        'manage_options',
        'game-system-feedbacks',
        'game_system_feedbacks_page'
    );

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
        'Logs do Sistema',
        'Logs',
        'manage_options',
        'game-system-logs',
        'game_system_logs_page'
    );

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

// Processa ações administrativas
function game_system_process_admin_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica o nonce para segurança
        if (!isset($_POST['admin_nonce']) || !wp_verify_nonce($_POST['admin_nonce'], 'game_system_admin_actions')) {
            wp_die('Falha na verificação de segurança.', 'Erro', ['response' => 403]);
        }

        // Adicionar nova categoria de feedback
        if (isset($_POST['new_feedback_category'])) {
            $categories = get_option('game_system_feedback_categories', []);
            $newCategory = sanitize_text_field($_POST['new_feedback_category']);

            if (!in_array($newCategory, $categories)) {
                $categories[] = $newCategory;
                update_option('game_system_feedback_categories', $categories);
                add_settings_error('game_system_messages', 'category_added', 'Categoria adicionada com sucesso!', 'updated');
            } else {
                add_settings_error('game_system_messages', 'category_exists', 'A categoria já existe!', 'error');
            }

            // Redireciona para evitar o reenvio do formulário
            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=feedbacks'));
            exit;
        }

        // Excluir categoria de feedback
        if (isset($_POST['delete_feedback_category'])) {
            $categories = get_option('game_system_feedback_categories', []);
            $categoryToDelete = sanitize_text_field($_POST['delete_feedback_category']);

            if (($key = array_search($categoryToDelete, $categories)) !== false) {
                unset($categories[$key]);
                update_option('game_system_feedback_categories', $categories);
                add_settings_error('game_system_messages', 'category_deleted', 'Categoria excluída com sucesso!', 'updated');
            } else {
                add_settings_error('game_system_messages', 'category_not_found', 'Categoria não encontrada!', 'error');
            }

            // Redireciona para evitar o reenvio do formulário
            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=feedbacks'));
            exit;
        }

        // Gerenciar mapas
        if (isset($_POST['new_map'])) {
            $maps = get_option('game_system_available_maps', []);
            $newMap = sanitize_text_field($_POST['new_map']);

            if (in_array($newMap, $maps)) {
                error_log("Tentativa de adicionar mapa duplicado: {$newMap}");
                add_settings_error('game_system_messages', 'map_exists', 'O mapa já existe!', 'error');
            } else {
                $maps[] = $newMap;
                update_option('game_system_available_maps', $maps);
                error_log("Mapa adicionado com sucesso: {$newMap}");
                add_settings_error('game_system_messages', 'map_added', 'Mapa adicionado com sucesso!', 'updated');
            }

            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=sistema-de-filas'));
            exit;
        }

        if (isset($_POST['delete_map'])) {
            $maps = get_option('game_system_available_maps', []);
            $mapToDelete = sanitize_text_field($_POST['delete_map']);

            if (($key = array_search($mapToDelete, $maps)) !== false) {
                unset($maps[$key]);
                update_option('game_system_available_maps', $maps);
                error_log("Mapa excluído com sucesso: {$mapToDelete}");
                add_settings_error('game_system_messages', 'map_deleted', 'Mapa excluído com sucesso!', 'updated');
            } else {
                error_log("Tentativa de excluir mapa inexistente: {$mapToDelete}");
                add_settings_error('game_system_messages', 'map_not_found', 'Mapa não encontrado!', 'error');
            }

            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=sistema-de-filas'));
            exit;
        }
    }
}
add_action('admin_post_game_system_process_admin_actions', 'game_system_process_admin_actions');

// Função para exibir mensagens de configuração
function game_system_admin_notices() {
    settings_errors('game_system_messages');
}
add_action('admin_notices', 'game_system_admin_notices');

// Página de Configurações
function game_system_settings_page() {
    $activeTab = $_GET['tab'] ?? 'sistema-de-filas';

    echo '<div class="wrap">';
    echo '<h1>Configurações do Sistema</h1>';

    // Menu de navegação entre as abas
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=game-system-settings&tab=sistema-de-filas" class="nav-tab ' . ($activeTab === 'sistema-de-filas' ? 'nav-tab-active' : '') . '">Sistema de Filas</a>';
    echo '<a href="?page=game-system-settings&tab=sistema-de-lobby" class="nav-tab ' . ($activeTab === 'sistema-de-lobby' ? 'nav-tab-active' : '') . '">Sistema de Lobby</a>';
    echo '<a href="?page=game-system-settings&tab=jogadores" class="nav-tab ' . ($activeTab === 'jogadores' ? 'nav-tab-active' : '') . '">Jogadores</a>';
    echo '<a href="?page=game-system-settings&tab=rankings" class="nav-tab ' . ($activeTab === 'rankings' ? 'nav-tab-active' : '') . '">Rankings</a>';
    echo '<a href="?page=game-system-settings&tab=feedbacks" class="nav-tab ' . ($activeTab === 'feedbacks' ? 'nav-tab-active' : '') . '">Feedbacks</a>';
    echo '</nav>';

    // Carrega o conteúdo da aba ativa
    switch ($activeTab) {
        case 'sistema-de-filas':
            game_system_settings_tab_filas();
            break;
        case 'sistema-de-lobby':
            game_system_settings_tab_lobby();
            break;
        case 'jogadores':
            game_system_settings_tab_jogadores();
            break;
        case 'rankings':
            game_system_settings_tab_rankings();
            break;
        case 'feedbacks':
            game_system_settings_tab_feedbacks();
            break;
        default:
            echo '<p>Aba inválida.</p>';
            break;
    }

    echo '</div>';
}

// Aba: Sistema de Filas
function game_system_settings_tab_filas() {
    $maps = get_option('game_system_available_maps', []);
    $maxPlayers = get_option('game_system_max_players_per_queue', 10);

    echo '<h2>Configurações do Sistema de Filas</h2>';

    // Formulário para adicionar mapas
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="new_map">Adicionar Novo Mapa:</label>';
    echo '<input type="text" name="new_map" id="new_map" required>';
    echo '<button type="submit" class="button button-primary">Adicionar Mapa</button>';
    echo '</form>';

    // Lista de mapas existentes com opção de exclusão
    echo '<h3>Mapas Disponíveis</h3>';
    if (empty($maps)) {
        echo '<p>Nenhum mapa disponível.</p>';
    } else {
        echo '<ul>';
        foreach ($maps as $map) {
            echo '<li>';
            echo esc_html($map);
            echo ' <form method="post" action="admin-post.php" style="display:inline;">';
            echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
            wp_nonce_field('game_system_admin_actions', 'admin_nonce');
            echo '<input type="hidden" name="delete_map" value="' . esc_attr($map) . '">';
            echo '<button type="submit" class="button button-secondary">Excluir</button>';
            echo '</form>';
            echo '</li>';
        }
        echo '</ul>';
    }

    // Formulário para alterar o número máximo de jogadores por fila
    echo '<h3>Configuração de Filas</h3>';
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="max_players_per_queue">Máximo de Jogadores por Fila:</label>';
    echo '<input type="number" name="max_players_per_queue" id="max_players_per_queue" value="' . esc_attr($maxPlayers) . '">';
    echo '<button type="submit" class="button button-primary">Salvar Configuração</button>';
    echo '</form>';
}

// Aba: Sistema de Lobby
function game_system_settings_tab_lobby() {
    echo '<h2>Configurações do Sistema de Lobby</h2>';
    echo '<p>Configurações específicas do sistema de lobby serão adicionadas aqui.</p>';
}

// Aba: Jogadores
function game_system_settings_tab_jogadores() {
    $bannedUsers = get_option('game_system_banned_users', []);

    echo '<h2>Gerenciamento de Jogadores</h2>';

    // Formulário para banir jogadores
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="ban_user_id">Proibir Usuário (ID):</label>';
    echo '<input type="number" name="ban_user_id" id="ban_user_id" required>';
    echo '<button type="submit" class="button button-primary">Proibir</button>';
    echo '</form>';

    // Lista de jogadores banidos
    echo '<h3>Jogadores Proibidos</h3>';
    if (empty($bannedUsers)) {
        echo '<p>Nenhum jogador proibido.</p>';
    } else {
        echo '<ul>';
        foreach ($bannedUsers as $userId) {
            echo '<li>';
            echo esc_html($userId);
            echo ' <form method="post" action="admin-post.php" style="display:inline;">';
            echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
            wp_nonce_field('game_system_admin_actions', 'admin_nonce');
            echo '<input type="hidden" name="unban_user_id" value="' . esc_attr($userId) . '">';
            echo '<button type="submit" class="button button-secondary">Remover Proibição</button>';
            echo '</form>';
            echo '</li>';
        }
        echo '</ul>';

        // Formulário para alterar pontuação ou ELO
    echo '<h3>Alterar Pontuação ou ELO</h3>';
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="player_id">ID do Jogador:</label>';
    echo '<input type="number" name="player_id" id="player_id" required>';
    echo '<label for="player_score">Pontuação:</label>';
    echo '<input type="number" name="player_score" id="player_score">';
    echo '<label for="player_elo">ELO:</label>';
    echo '<input type="number" name="player_elo" id="player_elo">';
    echo '<button type="submit" class="button button-primary">Atualizar</button>';
    echo '</form>';
    }
}

// Aba: Rankings
function game_system_settings_tab_rankings() {
    echo '<h2>Gerenciamento de Rankings</h2>';

    // Formulário para resetar rankings
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<button type="submit" name="reset_general_ranking" class="button button-primary">Resetar Ranking Geral</button>';
    echo '<button type="submit" name="reset_monthly_ranking" class="button button-primary">Resetar Ranking Mensal</button>';
    echo '</form>';

    // Formulário para alterar pontuação no ranking
    echo '<h3>Alterar Pontuação no Ranking</h3>';
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="ranking_player_id">ID do Jogador:</label>';
    echo '<input type="number" name="ranking_player_id" id="ranking_player_id" required>';
    echo '<label for="ranking_score">Nova Pontuação:</label>';
    echo '<input type="number" name="ranking_score" id="ranking_score" required>';
    echo '<button type="submit" class="button button-primary">Atualizar Pontuação</button>';
    echo '</form>';
}

// Aba: Logs
function game_system_settings_tab_logs() {
    $logManager = new LogManager();
    $logs = $logManager->getLogs();

    echo '<h2>Logs do Sistema</h2>';

    if (empty($logs)) {
        echo '<p>Nenhum log disponível.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Data</th><th>Tipo</th><th>Mensagem</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            echo "<tr><td>{$log['timestamp']}</td><td>{$log['type']}</td><td>{$log['message']}</td></tr>";
        }
        echo '</tbody></table>';
    }

    echo '<a href="' . admin_url('admin.php?page=game-system-logs&export_logs=1') . '" class="button button-primary">Exportar Logs para CSV</a>';
}

// Página de Logs do Sistema
function game_system_logs_page() {
    $logManager = new LogManager();
    $logs = $logManager->getLogs();

    echo '<div class="wrap">';
    echo '<h1>Logs do Sistema</h1>';

    if (empty($logs)) {
        echo '<p>Nenhum log disponível.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Data</th><th>Tipo</th><th>Mensagem</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            echo "<tr><td>{$log['timestamp']}</td><td>{$log['type']}</td><td>{$log['message']}</td></tr>";
        }
        echo '</tbody></table>';
    }

    echo '<a href="' . admin_url('admin.php?page=game-system-logs&export_logs=1') . '" class="button button-primary">Exportar Logs para CSV</a>';
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
    $activeTab = $_GET['tab'] ?? (isset($categories[0]) ? $categories[0] : '');

    // Verifica se a aba ativa é válida
    if (!isset($categorizedFeedbacks[$activeTab])) {
        $activeTab = isset($categories[0]) ? $categories[0] : '';
    }

    echo '<div class="wrap">';
    echo '<h1>Feedbacks dos Usuários</h1>';

    // Menu de navegação entre as categorias
    echo '<nav class="nav-tab-wrapper">';
    foreach ($categories as $category) {
        $activeClass = ($activeTab === $category) ? 'nav-tab-active' : '';
        echo '<a href="?page=game-system-feedbacks&tab=' . urlencode($category) . '" class="nav-tab ' . $activeClass . '">' . esc_html($category) . '</a>';
    }
    echo '</nav>';

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
                        <form method='post' action='admin-post.php'>
                            <input type='hidden' name='action' value='process_feedback_response'>
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

function game_system_settings_tab_feedbacks() {
    $categories = get_option('game_system_feedback_categories', []);

    echo '<h2>Gerenciamento de Categorias de Feedback</h2>';

    // Formulário para adicionar categorias
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="new_feedback_category">Nova Categoria:</label>';
    echo '<input type="text" name="new_feedback_category" id="new_feedback_category" required>';
    echo '<button type="submit" class="button button-primary">Adicionar Categoria</button>';
    echo '</form>';

    // Lista de categorias existentes com opção de exclusão
    echo '<h3>Categorias Existentes</h3>';
    if (empty($categories)) {
        echo '<p>Nenhuma categoria disponível.</p>';
    } else {
        echo '<ul>';
        foreach ($categories as $category) {
            echo '<li>' . esc_html($category) . ' <form method="post" style="display:inline;" action="admin-post.php">';
            echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
            wp_nonce_field('game_system_admin_actions', 'admin_nonce');
            echo '<input type="hidden" name="delete_feedback_category" value="' . esc_attr($category) . '">';
            echo '<button type="submit" class="button-link-delete">Excluir</button>';
            echo '</form></li>';
        }
        echo '</ul>';
    }
}

// Página de Rankings
function game_system_rankings_page() {
    $rankingManager = new RankingManager();
    $generalRanking = $rankingManager->getGeneralRanking();
    $monthlyRanking = $rankingManager->getMonthlyRanking();

    echo '<div class="wrap">';
    echo '<h1>Rankings</h1>';

    // Exibir Ranking Geral
    echo '<h2>Ranking Geral</h2>';
    if (empty($generalRanking)) {
        echo '<p>Nenhum jogador no ranking geral ainda.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Jogador</th><th>Pontos</th></tr></thead>';
        echo '<tbody>';
        foreach ($generalRanking as $entry) {
            echo "<tr><td>Jogador ID: {$entry['player_id']}</td><td>{$entry['score']}</td></tr>";
        }
        echo '</tbody></table>';
    }

    // Exibir Ranking Mensal
    echo '<h2>Ranking Mensal</h2>';
    if (empty($monthlyRanking)) {
        echo '<p>Nenhum jogador no ranking mensal ainda.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Jogador</th><th>Pontos</th></tr></thead>';
        echo '<tbody>';
        foreach ($monthlyRanking as $entry) {
            echo "<tr><td>Jogador ID: {$entry['player_id']}</td><td>{$entry['score']}</td></tr>";
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

    // Formulário para banir jogadores
    echo '<h2>Proibir Jogadores</h2>';
    echo '<form method="post" action="admin-post.php">';
    echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
    wp_nonce_field('game_system_admin_actions', 'admin_nonce');
    echo '<label for="ban_user_id">ID do Jogador:</label>';
    echo '<input type="number" name="ban_user_id" id="ban_user_id" required>';
    echo '<button type="submit" class="button button-primary">Proibir</button>';
    echo '</form>';

    // Lista de jogadores banidos
    echo '<h2>Jogadores Proibidos</h2>';
    if (empty($bannedUsers)) {
        echo '<p>Nenhum jogador proibido.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID do Jogador</th><th>Ações</th></tr></thead>';
        echo '<tbody>';
        foreach ($bannedUsers as $userId) {
            echo '<tr>';
            echo '<td>' . esc_html($userId) . '</td>';
            echo '<td>';
            echo '<form method="post" action="admin-post.php" style="display: inline;">';
            echo '<input type="hidden" name="action" value="game_system_process_admin_actions">';
            wp_nonce_field('game_system_admin_actions', 'admin_nonce');
            echo '<input type="hidden" name="unban_user_id" value="' . esc_attr($userId) . '">';
            echo '<button type="submit" class="button button-secondary">Remover Proibição</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Página de Estatísticas Gerais
function game_system_general_stats_page() {
    $globalStatsManager = new GlobalStatsManager();

    // Obtém as estatísticas globais
    $globalStats = $globalStatsManager->getGlobalStats();

    echo '<div class="wrap">';
    echo '<h1>Estatísticas Gerais</h1>';

    echo '<h2>Dados Globais</h2>';
    echo '<p><strong>Total de Partidas Jogadas:</strong> ' . esc_html($globalStats['total_matches']) . '</p>';
    echo '<p><strong>Total de Jogadores Registrados:</strong> ' . esc_html($globalStats['total_players']) . '</p>';
    echo '<p><strong>Total de Reclamações:</strong> ' . esc_html($globalStats['total_complaints']) . '</p>';

    echo '</div>';
}
?>