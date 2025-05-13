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

    ob_start();
    ?>
    <div id="game-queue">
        <h3>Filas Disponíveis</h3>
        <?php if (empty($queues)): ?>
            <p>Nenhuma fila disponível no momento.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($queues as $queueId => $queue): ?>
                    <li>
                        Fila <?php echo esc_html($queueId); ?>: <?php echo count($queue); ?> jogadores
                        <?php if (in_array($currentUserId, $queue)): ?>
                            <button class="leave-queue" data-queue-id="<?php echo esc_attr($queueId); ?>">Sair da Fila</button>
                        <?php else: ?>
                            <button class="join-queue" data-queue-id="<?php echo esc_attr($queueId); ?>">Entrar na Fila</button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
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
    } elseif ($action === 'leave') {
        $message = $queueSystem->leaveQueue($currentUserId, $queueId);
    } else {
        wp_send_json_error(['message' => 'Ação não reconhecida.']);
    }

    // Gera o HTML atualizado
    ob_start();
    echo display_queue();
    $html = ob_get_clean();

    wp_send_json_success([
        'message' => $message,
        'html' => $html,
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