<?php

// Processa ações administrativas
function game_system_process_admin_actions() {
    $gameSystem = $GLOBALS['gameSystem'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Resetar rankings
        if (isset($_POST['reset_general_ranking'])) {
            $gameSystem->setPlayerScores([]);
            add_settings_error('game_system_messages', 'general_ranking_reset', 'Ranking geral resetado com sucesso!', 'updated');
        }

        if (isset($_POST['reset_monthly_ranking'])) {
            $gameSystem->setMonthlyScores([]);
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
    $bannedUsers = get_option('game_system_banned_users', []);
    ?>
    <div class="wrap">
        <h1>Configurações do Sistema</h1>

        <h2>Ranking</h2>
        <form method="post" action="admin-post.php">
            <input type="hidden" name="action" value="game_system_process_admin_actions">
            <button type="submit" name="reset_general_ranking" class="button button-primary">Resetar Ranking Geral</button>
            <button type="submit" name="reset_monthly_ranking" class="button button-primary">Resetar Ranking Mensal</button>
        </form>

        <h2>Gerenciamento de Jogadores</h2>
        <form method="post" action="admin-post.php">
            <input type="hidden" name="action" value="game_system_process_admin_actions">
            <label for="ban_user_id">Proibir Usuário (ID):</label>
            <input type="number" name="ban_user_id" id="ban_user_id" required>
            <button type="submit" class="button button-primary">Proibir</button>
        </form>

        <h3>Jogadores Proibidos</h3>
        <?php if (empty($bannedUsers)): ?>
            <p>Nenhum jogador proibido.</p>
        <?php else: ?>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>ID do Usuário</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bannedUsers as $userId): ?>
                        <tr>
                            <td><?php echo esc_html($userId); ?></td>
                            <td>
                                <form method="post" action="admin-post.php" style="display: inline;">
                                    <input type="hidden" name="action" value="game_system_process_admin_actions">
                                    <input type="hidden" name="unban_user_id" value="<?php echo esc_attr($userId); ?>">
                                    <button type="submit" class="button button-secondary">Remover Proibição</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
?>