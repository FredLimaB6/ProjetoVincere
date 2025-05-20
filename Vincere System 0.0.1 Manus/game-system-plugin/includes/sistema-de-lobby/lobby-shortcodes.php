<?php
// filepath: /home/ubuntu/vincere_plugin_analise/game-system-plugin/includes/sistema-de-lobby/lobby-shortcodes.php

if (!defined('ABSPATH')) {
    exit; // Sai se o arquivo for acessado diretamente.
}

function display_lobby() {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return 
            '<p>
                ' . esc_html__("Você precisa estar logado para acessar o lobby.", "game-system-plugin") . 
            '</p>
        ';
    }

    // Verifica se o usuário tem o plano Básico ou Premium para acessar Lobbys
    if (!vincere_user_has_access('lobbys')) {
        return 
            '<p>
                ' . esc_html__("Você não tem permissão para acessar o lobby. Verifique seu plano de assinatura.", "game-system-plugin") . 
            '</p>
        ';
    }

    // Verifica a capability específica para acessar Lobbys (genérica, pode ser refinada)
    if (!current_user_can(VINCERE_PREFIX . 'join_lobby') && !current_user_can(VINCERE_PREFIX . 'create_lobby')) {
        return 
            '<p>
                ' . esc_html__("Você não tem as permissões necessárias para acessar o lobby.", "game-system-plugin") . 
            '</p>
        ';
    }

    $lobbyManager = new LobbyManager();
    $teams = $lobbyManager->getTeams();
    $currentUserId = get_current_user_id();

    // Organiza os times em disponíveis e completos
    $availableTeams = [];
    $completeTeams = [];
    if (is_array($teams)) { // Adicionada verificação
        foreach ($teams as $teamId => $team) {
            if (isset($team[\'status\']) && $team[\'status\'] === \'complete\') {
                $completeTeams[$teamId] = $team;
            } else {
                $availableTeams[$teamId] = $team;
            }
        }
    }

    ob_start();
    ?>
    <div id=\"lobby\">
        <h3><?php esc_html_e("Lobby de Times", "game-system-plugin"); ?></h3>
        <?php if (current_user_can(VINCERE_PREFIX . \'create_lobby\')): ?>
            <button id=\"create-team\"><?php esc_html_e("Criar Time", "game-system-plugin"); ?></button>
        <?php endif; ?>

        <nav class=\"nav-tab-wrapper\">
            <a href=\"#available-teams\" class=\"nav-tab nav-tab-active\"><?php esc_html_e("Times Disponíveis", "game-system-plugin"); ?></a>
            <a href=\"#complete-teams\" class=\"nav-tab\"><?php esc_html_e("Times Completos", "game-system-plugin"); ?></a>
        </nav>

        <div id=\"available-teams\" class=\"tab-content active\">
            <?php if (empty($availableTeams)): ?>
                <p><?php esc_html_e("Nenhum time disponível no momento. Crie um novo time para começar!", "game-system-plugin"); ?></p>
            <?php else: ?>
                <?php foreach ($availableTeams as $teamId => $team): ?>
                    <?php if (is_array($team) && isset($team[\'name\']) && isset($team[\'players\']) && is_array($team[\'players\']) && isset($team[\'id\'])): ?>
                    <div class=\"team\">
                        <h4><?php echo esc_html($team[\'name\']); ?></h4>
                        <p><?php echo sprintf(esc_html__("Jogadores: %d/5", "game-system-plugin"), count($team[\'players\'])); ?></p>
                        <ul>
                            <?php foreach ($team[\'players\'] as $playerId): ?>
                                <?php
                                $user = get_userdata($playerId);
                                $nickname = $user ? $user->user_login : esc_html__("Jogador Anônimo", "game-system-plugin");
                                ?>
                                <li><?php echo esc_html($nickname); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (in_array($currentUserId, $team[\'players\'])): ?>
                            <button class=\"leave-team\" data-team-id=\"<?php echo esc_attr($team[\'id\']); ?>\"><?php esc_html_e("Sair do Time", "game-system-plugin"); ?></button>
                        <?php elseif (count($team[\'players\']) < 5 && current_user_can(VINCERE_PREFIX . \'join_lobby\')): ?>
                            <button class=\"join-team\" data-team-id=\"<?php echo esc_attr($team[\'id\']); ?>\"><?php esc_html_e("Entrar no Time", "game-system-plugin"); ?></button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id=\"complete-teams\" class=\"tab-content\">
            <?php if (empty($completeTeams)): ?>
                <p><?php esc_html_e("Nenhum time completo no momento.", "game-system-plugin"); ?></p>
            <?php else: ?>
                <?php foreach ($completeTeams as $teamId => $team): ?>
                     <?php if (is_array($team) && isset($team[\'name\']) && isset($team[\'players\']) && is_array($team[\'players\'])): ?>
                    <div class=\"team\">
                        <h4><?php echo esc_html($team[\'name\']); ?></h4>
                        <p><?php echo sprintf(esc_html__("Jogadores: %d/5", "game-system-plugin"), count($team[\'players\'])); ?></p>
                        <ul>
                            <?php foreach ($team[\'players\'] as $playerId): ?>
                                <?php
                                $user = get_userdata($playerId);
                                $nickname = $user ? $user->user_login : esc_html__("Jogador Anônimo", "game-system-plugin");
                                ?>
                                <li><?php echo esc_html($nickname); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php // Adicionar botão de desafiar time aqui se a funcionalidade for implementada ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode(\'lobby\', \'display_lobby\');

function display_lobby_match() {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__("Você precisa estar logado para ver uma partida de lobby.", "game-system-plugin") . '</p>';
    }
    if (!vincere_user_has_access(\'lobbys\')) {
        return '<p>' . esc_html__("Você não tem permissão para ver partidas de lobby. Verifique seu plano de assinatura.", "game-system-plugin") . '</p>';
    }
     // Adicionar verificação de capability se aplicável a visualização de partidas de lobby

    return '<h3>' . esc_html__("Partida de Lobby", "game-system-plugin") . '</h3><p>' . esc_html__("Detalhes da partida serão exibidos aqui.", "game-system-plugin") . '</p>';
}
add_shortcode(\'lobby_match\', \'display_lobby_match\');


function vincere_create_team_ajax() {
    check_ajax_referer(\'game_system_nonce\', \'nonce\');

    if (!is_user_logged_in()) {
        wp_send_json_error([\'message\' => esc_html__("Você precisa estar logado para criar um time.", "game-system-plugin")]);
        return;
    }

    if (!vincere_user_has_access(\'lobbys\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem permissão para criar times. Verifique seu plano de assinatura.", "game-system-plugin")]);
        return;
    }

    if (!current_user_can(VINCERE_PREFIX . \'create_lobby\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem as permissões necessárias para criar um time.", "game-system-plugin")]);
        return;
    }

    $currentUserId = get_current_user_id();
    $lobbyManager = new LobbyManager();

    $result = $lobbyManager->createTeam($currentUserId);

    if (isset($result[\'success\']) && $result[\'success\']) {
        wp_send_json_success([\'message\' => esc_html__("Seu time foi criado com sucesso!", "game-system-plugin"), \'team_id\' => $result[\'team_id\']]);
    } else {
        $error_message = isset($result[\'message\']) ? $result[\'message\'] : esc_html__("Erro ao criar o time, verifique se você já não está em um.", "game-system-plugin");
        wp_send_json_error([\'message\' => esc_html($error_message)]);
    }
}
add_action(\'wp_ajax_create_lobby_team\', \'vincere_create_team_ajax\');

function vincere_join_team_ajax() {
    check_ajax_referer(\'game_system_nonce\', \'nonce\');

    if (!is_user_logged_in()) {
        wp_send_json_error([\'message\' => esc_html__("Você precisa estar logado para entrar em um time.", "game-system-plugin")]);
        return;
    }

    if (!vincere_user_has_access(\'lobbys\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem permissão para entrar em times. Verifique seu plano de assinatura.", "game-system-plugin")]);
        return;
    }

    if (!current_user_can(VINCERE_PREFIX . \'join_lobby\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem as permissões necessárias para entrar em um time.", "game-system-plugin")]);
        return;
    }
    
    if (!isset($_POST[\'team_id\'])) {
        wp_send_json_error([\'message\' => esc_html__("ID do time não fornecido.", "game-system-plugin")]);
        return;
    }
    $teamId = absint($_POST[\'team_id\']);
    if ($teamId <= 0) {
        wp_send_json_error([\'message\' => esc_html__("ID do time inválido.", "game-system-plugin")]);
        return;
    }

    $currentUserId = get_current_user_id();
    $lobbyManager = new LobbyManager();
    $success = $lobbyManager->joinTeam($teamId, $currentUserId);

    if ($success) {
        wp_send_json_success([\'message\' => esc_html__("Você entrou no time!", "game-system-plugin")]);
    } else {
        wp_send_json_error([\'message\' => esc_html__("Erro ao entrar no time. O time pode estar cheio ou você já está em outro time.", "game-system-plugin")]);
    }
}
add_action(\'wp_ajax_join_team\', \'vincere_join_team_ajax\');

function vincere_leave_team_ajax() {
    check_ajax_referer(\'game_system_nonce\', \'nonce\');

    if (!is_user_logged_in()) {
        wp_send_json_error([\'message\' => esc_html__("Você precisa estar logado para sair de um time.", "game-system-plugin")]);
        return;
    }
    
    // Não é necessário verificar plano/capability para sair de um time que já entrou.

    if (!isset($_POST[\'team_id\'])) {
        wp_send_json_error([\'message\' => esc_html__("ID do time não fornecido.", "game-system-plugin")]);
        return;
    }
    $teamId = absint($_POST[\'team_id\']);
     if ($teamId <= 0) {
        wp_send_json_error([\'message\' => esc_html__("ID do time inválido.", "game-system-plugin")]);
        return;
    }

    $playerId = get_current_user_id();
    $lobbyManager = new LobbyManager();
    $result = $lobbyManager->leaveTeam($teamId, $playerId);

    if (isset($result[\'success\']) && $result[\'success\']) {
        wp_send_json_success([\'message\' => esc_html($result[\'message\'])]);
    } else {
        $error_message = isset($result[\'message\']) ? $result[\'message\'] : esc_html__("Erro ao sair do time.", "game-system-plugin");
        wp_send_json_error([\'message\' => esc_html($error_message)]);
    }
}
add_action(\'wp_ajax_leave_team\', \'vincere_leave_team_ajax\');
?>
