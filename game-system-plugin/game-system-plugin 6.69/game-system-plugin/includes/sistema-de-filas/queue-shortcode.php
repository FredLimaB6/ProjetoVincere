<?php
// filepath: c:\Users\Fred\Documents\Plug-in - Sistema de filas e ranking Vincere Club\Plug-in\game-system-plugin\includes\sistema-de-filas\queue-shortcode.php

if (!defined('ABSPATH')) {
    exit; // Sai se o arquivo for acessado diretamente.
}

// Exibe as filas disponíveis e os botões de ação
function display_queue() {
    if (!isset($GLOBALS['queueSystem'])) {
        return '<p>O sistema de filas não está inicializado.</p>';
    }

    $queueSystem = $GLOBALS['queueSystem'];
    $currentUserId = get_current_user_id();
    $queues = $queueSystem->getQueues();

    // Verifica se o usuário já está em uma fila
    $userQueueId = null;
    foreach ($queues as $queueId => $queue) {
        if (in_array($currentUserId, $queue)) {
            $userQueueId = $queueId;
            break;
        }
    }

    ob_start();
    ?>
    <div id="game-queue">
        <h3>Filas Disponíveis</h3>
        <?php if ($userQueueId !== null): ?>
            <!-- Exibe a fila em que o usuário está -->
            <div class="queue">
                <h4>Fila <?php echo esc_html($userQueueId); ?>:</h4>
                <p>
                    <?php 
                    $playerCount = count($queues[$userQueueId]);
                    echo $playerCount . ' ' . ($playerCount === 1 ? 'jogador' : 'jogadores'); 
                    ?>
                </p>
                <button class="leave-queue" data-queue-id="<?php echo esc_attr($userQueueId); ?>">Sair da Fila</button>
            </div>
        <?php else: ?>
            <!-- Exibe a primeira fila disponível para o usuário entrar -->
            <?php
            $availableQueueId = null;
            foreach ($queues as $queueId => $queue) {
                if (count($queue) < 10) {
                    $availableQueueId = $queueId;
                    break;
                }
            }
            ?>
            <?php if ($availableQueueId !== null): ?>
                <div class="queue">
                    <h4>Fila <?php echo esc_html($availableQueueId); ?>:</h4>
                    <p>
                        <?php 
                        $playerCount = count($queues[$availableQueueId]);
                        echo $playerCount . ' ' . ($playerCount === 1 ? 'jogador' : 'jogadores'); 
                        ?>
                    </p>
                    <button class="join-queue" data-queue-id="<?php echo esc_attr($availableQueueId); ?>">Entrar na Fila</button>
                </div>
            <?php else: ?>
                <p>Não há filas disponíveis no momento.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Processa as ações de entrar e sair da fila via AJAX
function game_system_process_queue() {
    check_ajax_referer('game_system_nonce', 'nonce');

    if (!isset($GLOBALS['queueSystem'])) {
        wp_send_json_error(['message' => 'O sistema de filas não está inicializado.']);
    }

    $queueSystem = $GLOBALS['queueSystem'];
    $currentUserId = get_current_user_id();

    if (!isset($_POST['queue_id']) || !is_numeric($_POST['queue_id']) || intval($_POST['queue_id']) <= 0) {
        wp_send_json_error(['message' => 'ID da fila inválido.']);
    }
    $queueId = intval($_POST['queue_id']);

    if (!isset($_POST['action_type']) || !in_array($_POST['action_type'], ['join', 'leave'], true)) {
        wp_send_json_error(['message' => 'Ação inválida.']);
    }
    $action = sanitize_text_field($_POST['action_type']);

    if ($action === 'join') {
        $message = $queueSystem->joinQueue($currentUserId);
        error_log("Jogador {$currentUserId} entrou na fila {$queueId}."); // Log para depuração
    } elseif ($action === 'leave') {
        $message = $queueSystem->leaveQueue($currentUserId, $queueId);
        error_log("Jogador {$currentUserId} saiu da fila {$queueId}."); // Log para depuração
    } else {
        wp_send_json_error(['message' => 'Ação não reconhecida.']);
    }

    // Verifica se o usuário ainda está na fila
    $isInQueue = false;
    $queues = $queueSystem->getQueues();
    if (isset($queues[$queueId]) && in_array($currentUserId, $queues[$queueId])) {
        $isInQueue = true;
    }

    // Gera o HTML atualizado
    ob_start();
    echo display_queue();
    $html = ob_get_clean();

    wp_send_json_success([
        'message' => $message,
        'html' => $html, // Retorna o HTML atualizado da fila
        'is_in_queue' => $isInQueue, // Retorna o estado atualizado do usuário
    ]);
}
add_action('wp_ajax_game_system_process_queue', 'game_system_process_queue');
add_action('wp_ajax_nopriv_game_system_process_queue', 'game_system_process_queue');

// Registra o shortcode [game_queue]
add_shortcode('game_queue', 'display_queue');

// Função para obter o estado atualizado das filas (usada como fallback)
function game_system_get_queue_state() {
    check_ajax_referer('game_system_nonce', 'nonce');

    if (!isset($GLOBALS['queueSystem'])) {
        wp_send_json_error(['message' => 'O sistema de filas não está inicializado.']);
    }

    $queueSystem = $GLOBALS['queueSystem'];

    // Gera o HTML atualizado
    ob_start();
    echo display_queue();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_game_system_get_queue_state', 'game_system_get_queue_state');
add_action('wp_ajax_nopriv_game_system_get_queue_state', 'game_system_get_queue_state');
?>