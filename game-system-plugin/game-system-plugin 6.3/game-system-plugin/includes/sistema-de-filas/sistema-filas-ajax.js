//sistema-filas-ajax.js
jQuery(document).ready(function ($) {
    // Verifica se o objeto queueSystemAjax está definido
    if (typeof queueSystemAjax === 'undefined') {
        console.error('Erro: O objeto queueSystemAjax não está definido.');
        alert('Erro crítico: Configuração do sistema de filas está ausente. Contate o administrador.');
        return;
    }

    // Carrega o som de entrar na fila
    const queueJoinSound = new Audio(queueSystemAjax.pluginDirUrl + 'assets/sounds/join-queue.mp3');

    // Função para atualizar os eventos após a atualização do HTML
    function rebindQueueEvents() {
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

                        // Redireciona para a mesma página para forçar a atualização
                        location.reload();
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
});
