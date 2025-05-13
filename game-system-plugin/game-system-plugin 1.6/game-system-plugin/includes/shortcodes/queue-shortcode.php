<?php
if (!defined('ABSPATH')) {
    exit; // Sai se o arquivo for acessado diretamente.
}

function display_queue() {
    if (!isset($GLOBALS['queueSystem'])) {
        return '<p>O sistema não está inicializado.</p>';
    }

    $queueSystem = $GLOBALS['queueSystem'];
    $queues = $queueSystem->getQueues();
    $currentUserId = get_current_user_id();

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
        <h3>Fila Ativa</h3>
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
?>