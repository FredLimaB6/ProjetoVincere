<?php

// Processa ações administrativas
function game_system_process_admin_actions() {
    if (!isset($GLOBALS['queueSystem'])) {
        wp_die('O sistema de filas não está inicializado.', 'Erro', ['response' => 500]);
    }

    $queueSystem = $GLOBALS['queueSystem'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifique o nonce na função de processamento
        if (!isset($_POST['admin_nonce']) || !wp_verify_nonce($_POST['admin_nonce'], 'game_system_admin_actions')) {
            wp_die('Falha na verificação de segurança.', 'Erro', ['response' => 403]);
        }

        // Resetar rankings
        if (isset($_POST['reset_general_ranking'])) {
            $queueSystem->updateGeneralScore([], 0);
            add_settings_error('game_system_messages', 'general_ranking_reset', 'Ranking geral resetado com sucesso!', 'updated');
        }

        if (isset($_POST['reset_monthly_ranking'])) {
            $queueSystem->updateMonthlyScore([], 0);
            add_settings_error('game_system_messages', 'monthly_ranking_reset', 'Ranking mensal resetado com sucesso!', 'updated');
        }

        // Gerenciar jogadores proibidos
        if (isset($_POST['ban_user_id'])) {
            $bannedUsers = get_option('game_system_banned_users', []);
            $userId = intval($_POST['ban_user_id']);
            if (!in_array($userId, $bannedUsers)) {
                $bannedUsers[] = $userId;
                update_option('game_system_banned_users', $bannedUsers);
                add_settings_error('game_system_messages', 'user_banned', "Usuário ID {$userId} foi proibido com sucesso!", 'updated');
            }
        }

        if (isset($_POST['unban_user_id'])) {
            $bannedUsers = get_option('game_system_banned_users', []);
            $userId = intval($_POST['unban_user_id']);
            if (($key = array_search($userId, $bannedUsers)) !== false) {
                unset($bannedUsers[$key]);
                update_option('game_system_banned_users', $bannedUsers);
                add_settings_error('game_system_messages', 'user_unbanned', "Usuário ID {$userId} foi removido da lista de proibição!", 'updated');
            }
        }

        // Configurações avançadas
        if (isset($_POST['max_players_per_queue'])) {
            update_option('game_system_max_players_per_queue', intval($_POST['max_players_per_queue']));
            add_settings_error('game_system_messages', 'settings_saved', 'Configurações salvas com sucesso!', 'updated');
        }

        if (isset($_POST['available_maps'])) {
            update_option('game_system_available_maps', array_map('sanitize_text_field', explode(',', $_POST['available_maps'])));
            add_settings_error('game_system_messages', 'maps_updated', 'Mapas atualizados com sucesso!', 'updated');
        }

        if (isset($_POST['custom_messages'])) {
            update_option('game_system_custom_messages', sanitize_textarea_field($_POST['custom_messages']));
            add_settings_error('game_system_messages', 'messages_updated', 'Mensagens personalizadas salvas com sucesso!', 'updated');
        }

        // Adicionar nova categoria de feedback
        if (isset($_POST['new_feedback_category'])) {
            $categories = get_option('game_system_feedback_categories', ['Sugestões', 'Reclamações', 'Problemas técnicos']);
            $newCategory = sanitize_text_field($_POST['new_feedback_category']);
            if (!in_array($newCategory, $categories)) {
                $categories[] = $newCategory;
                update_option('game_system_feedback_categories', $categories);
                add_settings_error('game_system_messages', 'category_added', 'Categoria adicionada com sucesso!', 'updated');
            }

            // Redireciona para evitar reenvio do formulário
            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=feedbacks'));
            exit;
        }

        // Excluir categoria de feedback
        if (isset($_POST['delete_feedback_category'])) {
            $categories = get_option('game_system_feedback_categories', ['Sugestões', 'Reclamações', 'Problemas técnicos']);
            $categoryToDelete = sanitize_text_field($_POST['delete_feedback_category']);
            if (($key = array_search($categoryToDelete, $categories)) !== false) {
                unset($categories[$key]);
                update_option('game_system_feedback_categories', $categories);
                add_settings_error('game_system_messages', 'category_deleted', 'Categoria excluída com sucesso!', 'updated');
            }

            // Redireciona para evitar reenvio do formulário
            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=feedbacks'));
            exit;
        }

        // Adicionar novo mapa
        if (isset($_POST['new_map'])) {
            $maps = get_option('game_system_available_maps', []);
            $newMap = sanitize_text_field($_POST['new_map']);
            if (!in_array($newMap, $maps)) {
                $maps[] = $newMap;
                update_option('game_system_available_maps', $maps);
                add_settings_error('game_system_messages', 'map_added', 'Mapa adicionado com sucesso!', 'updated');
            }

            // Redireciona para evitar reenvio do formulário
            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=configuracoes'));
            exit;
        }

        // Excluir mapa
        if (isset($_POST['delete_map'])) {
            $maps = get_option('game_system_available_maps', []);
            $mapToDelete = sanitize_text_field($_POST['delete_map']);
            if (($key = array_search($mapToDelete, $maps)) !== false) {
                unset($maps[$key]);
                update_option('game_system_available_maps', $maps);
                add_settings_error('game_system_messages', 'map_deleted', 'Mapa excluído com sucesso!', 'updated');
            }

            // Redireciona para evitar reenvio do formulário
            wp_redirect(admin_url('admin.php?page=game-system-settings&tab=configuracoes'));
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

// Renderiza a página de configurações
function game_system_settings_page() {
    // Determina a aba ativa
    $activeTab = $_GET['tab'] ?? 'jogadores';

    echo '<div class="wrap">';
    echo '<h1>Configurações do Sistema</h1>';

    // Menu de navegação entre as abas
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=game-system-settings&tab=jogadores" class="nav-tab ' . ($activeTab === 'jogadores' ? 'nav-tab-active' : '') . '">Jogadores</a>';
    echo '<a href="?page=game-system-settings&tab=rankings" class="nav-tab ' . ($activeTab === 'rankings' ? 'nav-tab-active' : '') . '">Rankings</a>';
    echo '<a href="?page=game-system-settings&tab=feedbacks" class="nav-tab ' . ($activeTab === 'feedbacks' ? 'nav-tab-active' : '') . '">Feedbacks</a>';
    echo '<a href="?page=game-system-settings&tab=configuracoes" class="nav-tab ' . ($activeTab === 'configuracoes' ? 'nav-tab-active' : '') . '">Configurações do Sistema</a>';
    echo '</nav>';

    // Carrega o conteúdo da aba ativa
    switch ($activeTab) {
        case 'jogadores':
            game_system_settings_tab_jogadores();
            break;
        case 'rankings':
            game_system_settings_tab_rankings();
            break;
        case 'feedbacks':
            game_system_settings_tab_feedbacks();
            break;
        case 'configuracoes':
            game_system_settings_tab_configuracoes();
            break;
        default:
            echo '<p>Aba inválida.</p>';
            break;
    }

    echo '</div>';
}

function game_system_settings_tab_jogadores() {
    $bannedUsers = get_option('game_system_banned_users', []);
    $eloManager = new EloManager();
    $rankingManager = new RankingManager();

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
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID do Usuário</th><th>Ações</th></tr></thead>';
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

function game_system_settings_tab_feedbacks() {
    $categories = get_option('game_system_feedback_categories', []);

    echo '<h2>Gerenciamento de Feedbacks</h2>';

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

function game_system_settings_tab_configuracoes() {
    $maps = get_option('game_system_available_maps', []);
    $maxPlayers = get_option('game_system_max_players_per_queue', 10);

    echo '<h2>Configurações do Sistema</h2>';

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
?>