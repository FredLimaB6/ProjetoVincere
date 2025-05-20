<?php
// filepath: /home/ubuntu/vincere_plugin_analise/game-system-plugin/includes/sistema-de-filas/queue-shortcode.php

if (!defined(\'ABSPATH\')) {
    exit; // Sai se o arquivo for acessado diretamente.
}

// Exibe as filas disponíveis e os botões de ação
function display_queue() {
    if (!is_user_logged_in()) {
        return 
            '<p>
                ' . esc_html__("Você precisa estar logado para acessar as filas.", "game-system-plugin") . 
            '</p>
        ';
    }

    // Verifica se o usuário tem o plano Básico ou Premium para acessar PUGs
    if (!vincere_user_has_access(\'pugs\')) {
        return 
            '<p>
                ' . esc_html__("Você não tem permissão para acessar as filas (PUGs). Verifique seu plano de assinatura.", "game-system-plugin") . 
            '</p>
        ';
    }
    
    // Verifica a capability específica para acessar PUGs
    if (!current_user_can(VINCERE_PREFIX . \'access_pugs\')) {
        return 
            '<p>
                ' . esc_html__("Você não tem as permissões necessárias para acessar as filas (PUGs).", "game-system-plugin") . 
            '</p>
        ';
    }

    if (!isset($GLOBALS[\'queueSystem\'])) {
        return 
            '<p>
                ' . esc_html__("O sistema de filas não está inicializado.", "game-system-plugin") . 
            '</p>
        ';
    }

    $queueSystem = $GLOBALS[\'queueSystem\'];
    $currentUserId = get_current_user_id();
    $queues = $queueSystem->getQueues();

    // Verifica se o usuário já está em uma fila
    $userQueueId = null;
    if (is_array($queues)) { // Adicionada verificação para garantir que $queues é um array
        foreach ($queues as $queueId => $queue) {
            if (is_array($queue) && in_array($currentUserId, $queue)) { // Adicionada verificação para garantir que $queue é um array
                $userQueueId = $queueId;
                break;
            }
        }
    }

    ob_start();
    ?>
    <div id=\"game-queue\">
        <h3><?php esc_html_e("Filas Disponíveis", "game-system-plugin"); ?></h3>
        <div class=\"queue-progress-container\">
            <div class=\"queue-progress-bar\"></div>
            <div class=\"queue-progress-text\">0/10 <?php esc_html_e("jogadores", "game-system-plugin"); ?></div>
        </div>
        <?php if ($userQueueId !== null && isset($queues[$userQueueId]) && is_array($queues[$userQueueId])): ?>
            <div class=\"queue\">
                <h4><?php echo sprintf(esc_html__("Fila %s", "game-system-plugin"), esc_html($userQueueId)); ?>:</h4>
                <p><?php echo sprintf(esc_html__("%d jogadores", "game-system-plugin"), count($queues[$userQueueId])); ?></p>
                <button class=\"leave-queue\" data-queue-id=\"<?php echo esc_attr($userQueueId); ?>\"><?php esc_html_e("Sair da Fila", "game-system-plugin"); ?></button>
            </div>
        <?php else: ?>
            <?php
            $availableQueueId = null;
            if (is_array($queues)) { // Adicionada verificação
                foreach ($queues as $queueId => $queue) {
                    if (is_array($queue) && count($queue) < 10) {
                        $availableQueueId = $queueId;
                        break;
                    }
                }
            }
            ?>
            <?php if ($availableQueueId !== null && isset($queues[$availableQueueId]) && is_array($queues[$availableQueueId])): ?>
                <div class=\"queue\">
                    <h4><?php echo sprintf(esc_html__("Fila %s", "game-system-plugin"), esc_html($availableQueueId)); ?>:</h4>
                    <p>
                        <?php 
                        $playerCount = count($queues[$availableQueueId]);
                        echo sprintf(esc_html(_n("%d jogador", "%d jogadores", $playerCount, "game-system-plugin")), $playerCount);
                        ?>
                    </p>
                    <button class=\"join-queue\" data-queue-id=\"<?php echo esc_attr($availableQueueId); ?>\"><?php esc_html_e("Entrar na Fila", "game-system-plugin"); ?></button>
                </div>
            <?php else: ?>
                <p><?php esc_html_e("Não há filas disponíveis no momento ou todas estão cheias.", "game-system-plugin"); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Processa as ações de entrar e sair da fila via AJAX
function game_system_process_queue() {
    // Verifica o nonce para segurança
    check_ajax_referer(\'queue_system_nonce\', \'nonce\');

    if (!is_user_logged_in()) {
        wp_send_json_error([\'message\' => esc_html__("Você precisa estar logado para realizar esta ação.", "game-system-plugin")]);
        return;
    }

    // Verifica se o usuário tem o plano Básico ou Premium para acessar PUGs
    if (!vincere_user_has_access(\'pugs\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem permissão para interagir com as filas (PUGs). Verifique seu plano de assinatura.", "game-system-plugin")]);
        return;
    }

    // Verifica a capability específica para acessar PUGs
    if (!current_user_can(VINCERE_PREFIX . \'access_pugs\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem as permissões necessárias para interagir com as filas (PUGs).", "game-system-plugin")]);
        return;
    }

    if (!isset($GLOBALS[\'queueSystem\'])) {
        error_log(\'[QueueSystem] Erro: O sistema de filas não está inicializado.\');
        wp_send_json_error([\'message\' => esc_html__("O sistema de filas não está inicializado.", "game-system-plugin")]);
        return;
    }

    $queueSystem = $GLOBALS[\'queueSystem\'];
    $currentUserId = get_current_user_id();

    if (!isset($_POST[\'queue_id\']) || !is_numeric($_POST[\'queue_id\'])) {
        error_log(\'[QueueSystem] Erro: ID da fila inválido.\');
        wp_send_json_error([\'message\' => esc_html__("ID da fila inválido.", "game-system-plugin")]);
        return;
    }
    // Sanitizar e validar o ID da fila
    $queueId = absint($_POST[\'queue_id\']);
    if ($queueId <= 0) {
        error_log(\'[QueueSystem] Erro: ID da fila inválido após sanitização.\');
        wp_send_json_error([\'message\' => esc_html__("ID da fila inválido.", "game-system-plugin")]);
        return;
    }

    if (!isset($_POST[\'action_type\'])) {
        error_log(\'[QueueSystem] Erro: Tipo de ação não fornecido.\');
        wp_send_json_error([\'message\' => esc_html__("Tipo de ação não fornecido.", "game-system-plugin")]);
        return;
    }
    // Sanitizar e validar o tipo de ação
    $action = sanitize_text_field($_POST[\'action_type\']);
    $allowed_actions = [\'join\', \'leave\'];
    if (!in_array($action, $allowed_actions, true)) {
        error_log(\'[QueueSystem] Erro: Ação inválida recebida: \' . $action);
        wp_send_json_error([\'message\' => esc_html__("Ação inválida.", "game-system-plugin")]);
        return;
    }

    $message = \'\';
    // Processa a ação de entrar ou sair da fila
    if ($action === \'join\') {
        $message = $queueSystem->joinQueue($currentUserId, $queueId); // Passar queueId para joinQueue
        error_log("[QueueSystem] Jogador {$currentUserId} tentou entrar na fila {$queueId}. Mensagem: " . $message);
    } elseif ($action === \'leave\') {
        $message = $queueSystem->leaveQueue($currentUserId, $queueId);
        error_log("[QueueSystem] Jogador {$currentUserId} tentou sair da fila {$queueId}. Mensagem: " . $message);
    } 
    // Não precisa de else aqui, já validamos a ação antes

    // Verifica se o usuário ainda está na fila
    $isInQueue = false;
    $queues = $queueSystem->getQueues();
    if (isset($queues[$queueId]) && is_array($queues[$queueId]) && in_array($currentUserId, $queues[$queueId])) {
        $isInQueue = true;
    }

    // Gera o HTML atualizado
    ob_start();
    echo display_queue(); // display_queue() já tem as verificações de acesso
    $html = ob_get_clean();

    // Log do estado atualizado
    error_log("[QueueSystem] Estado atualizado para jogador {$currentUserId}: " . ($isInQueue ? \'está na fila \' : \'não está na fila \') . $queueId . ". Mensagem da ação: " . $message);

    wp_send_json_success([
        \'message\' => esc_html($message), // Escapar a mensagem para segurança
        \'html\' => $html, // Retorna o HTML atualizado da fila
        \'is_in_queue\' => $isInQueue, // Retorna o estado atualizado do usuário
    ]);
}
add_action(\'wp_ajax_game_system_process_queue\', \'game_system_process_queue\');
// add_action(\'wp_ajax_nopriv_game_system_process_queue\', \'game_system_process_queue\'); // Remover nopriv, pois requer login

// Função para obter o estado atualizado das filas (usada como fallback)
function game_system_get_queue_state() {
    // Verifica o nonce para segurança
    check_ajax_referer(\'queue_system_nonce\', \'nonce\');

    if (!is_user_logged_in()) {
        wp_send_json_error([\'message\' => esc_html__("Você precisa estar logado para visualizar o estado da fila.", "game-system-plugin")]);
        return;
    }

    // Verifica se o usuário tem o plano Básico ou Premium para acessar PUGs
    if (!vincere_user_has_access(\'pugs\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem permissão para visualizar o estado da fila. Verifique seu plano de assinatura.", "game-system-plugin")]);
        return;
    }
    
    // Verifica a capability específica para acessar PUGs
    if (!current_user_can(VINCERE_PREFIX . \'access_pugs\')) {
        wp_send_json_error([\'message\' => esc_html__("Você não tem as permissões necessárias para visualizar o estado da fila.", "game-system-plugin")]);
        return;
    }

    if (!isset($GLOBALS[\'queueSystem\'])) {
        wp_send_json_error([\'message\' => esc_html__("O sistema de filas não está inicializado.", "game-system-plugin")]);
        return;
    }

    // Gera o HTML atualizado
    ob_start();
    echo display_queue(); // display_queue() já tem as verificações de acesso
    $html = ob_get_clean();

    wp_send_json_success([\'html\' => $html]);
}
add_action(\'wp_ajax_game_system_get_queue_state\', \'game_system_get_queue_state\');
// add_action(\'wp_ajax_nopriv_game_system_get_queue_state\', \'game_system_get_queue_state\'); // Remover nopriv

// Registra o shortcode [game_queue]
add_shortcode(\'game_queue\', \'display_queue\');
?>
