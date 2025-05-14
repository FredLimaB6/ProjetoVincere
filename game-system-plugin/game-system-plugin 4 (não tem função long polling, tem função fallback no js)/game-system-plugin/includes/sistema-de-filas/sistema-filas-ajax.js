//sistema-filas-ajax.js
jQuery(document).ready(function ($) {
    // Carrega o som de entrar na fila
    const queueJoinSound = new Audio(queueSystemAjax.pluginDirUrl + 'assets/sounds/join-queue.mp3'); // Certifique-se de que o caminho está correto

    // Evento de clique para entrar ou sair da fila
    $('#game-queue').on('click', '.join-queue, .leave-queue', function () {
        const button = $(this);
        const queueId = button.data('queue-id');
        const actionType = button.hasClass('join-queue') ? 'join' : 'leave';

        if (!queueId || !['join', 'leave'].includes(actionType)) {
            alert('Ação inválida.');
            return;
        }

        // Desabilita o botão enquanto a solicitação é processada
        button.prop('disabled', true).text('Processando...');

        // Envia a solicitação AJAX
        $.ajax({
            url: queueSystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'game_system_process_queue',
                nonce: queueSystemAjax.nonce,
                queue_id: queueId,
                action_type: actionType,
            },
            success: function (response) {
                if (response.success) {
                    // Atualiza dinamicamente o conteúdo da fila
                    $('#game-queue').html(response.html);

                    // Reproduz o som ao entrar na fila
                    if (actionType === 'join') {
                        if (queueJoinSound.canPlayType('audio/mpeg')) {
                            queueJoinSound.play().catch((error) => {
                                console.error('Erro ao reproduzir o som:', error);
                                alert('O som não pôde ser reproduzido. Verifique as configurações do navegador.');
                            });
                        } else {
                            console.error('Formato de áudio não suportado pelo navegador.');
                        }
                    }

                    // Chama o fallback para garantir que o estado seja atualizado
                    setTimeout(updateQueueStateFallback, 5000);
                } else {
                    alert(response.message || 'Erro ao processar a solicitação.');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro AJAX:', error);
                alert('Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.');
            },
            complete: function () {
                // Reabilita o botão após a solicitação
                button.prop('disabled', false).text(actionType === 'join' ? 'Entrar na Fila' : 'Sair da Fila');
            },
        });
    });

    // Fallback para atualizar o estado da fila
    function updateQueueStateFallback() {
        $.ajax({
            url: queueSystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'game_system_get_queue_state',
                nonce: queueSystemAjax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    // Atualiza dinamicamente o conteúdo da fila
                    $('#game-queue').html(response.html);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro ao atualizar o estado da fila:', error);
            },
        });
    }
});
