//sistema-filas-ajax.js
jQuery(document).ready(function ($) {
    // Carrega o som de entrar na fila
    const queueJoinSound = new Audio(queueSystemAjax.pluginDirUrl + 'assets/sounds/join-queue.mp3');

    // Função para atualizar os eventos após a atualização do HTML
    function rebindQueueEvents() {
        // Evento para entrar ou sair da fila
        $('#game-queue').off('click', '.join-queue, .leave-queue').on('click', '.join-queue, .leave-queue', function () {
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

                        // Reassocia os eventos após a atualização do HTML
                        rebindQueueEvents();

                        // Atualiza o botão com base no estado do usuário
                        if (response.is_in_queue) {
                            button.removeClass('join-queue').addClass('leave-queue').text('Sair da Fila');
                        } else {
                            button.removeClass('leave-queue').addClass('join-queue').text('Entrar na Fila');
                        }

                        // Reproduz o som ao entrar na fila
                        if (actionType === 'join' && response.is_in_queue) {
                            if (queueJoinSound.canPlayType('audio/mpeg')) {
                                queueJoinSound.play().catch((error) => {
                                    console.error('Erro ao reproduzir o som:', error);
                                    alert('O som não pôde ser reproduzido. Verifique as configurações do navegador.');
                                });
                            } else {
                                console.error('Formato de áudio não suportado pelo navegador.');
                            }
                        }
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
                    button.prop('disabled', false);
                },
            });
        });
    }

    // Inicializa os eventos ao carregar a página
    rebindQueueEvents();

    // Variável para armazenar o estado anterior da fila
    let lastQueueState = '';

    // Função para iniciar o Long Polling
    function startLongPolling() {
        $.ajax({
            url: queueSystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'game_system_get_queue_state',
                nonce: queueSystemAjax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    const newQueueState = JSON.stringify(response.html);
                    if (newQueueState !== lastQueueState) {
                        $('#game-queue').html(response.html);
                        lastQueueState = newQueueState;

                        // Reassocia os eventos após a atualização do HTML
                        rebindQueueEvents();
                    }
                }

                // Inicia outra solicitação de Long Polling
                startLongPolling();
            },
            error: function (xhr, status, error) {
                console.error('Erro no Long Polling:', error);

                // Tenta reconectar após 5 segundos
                setTimeout(startLongPolling, 5000);
            },
        });
    }

    // Inicia o Long Polling ao carregar a página
    startLongPolling();
});
