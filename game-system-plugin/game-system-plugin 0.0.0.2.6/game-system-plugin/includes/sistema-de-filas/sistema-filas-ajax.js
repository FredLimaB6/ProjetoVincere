//sistema-filas-ajax.js
jQuery(document).ready(function ($) {
    // Carrega o som de entrar na fila
    const queueJoinSound = new Audio(queueSystemAjax.pluginDirUrl + 'assets/sounds/join-queue.mp3');

    // Função para registrar logs no console
    function logMessage(message) {
        console.log(`[Sistema de Filas]: ${message}`);
    }

    // Função para atualizar os eventos após a atualização do HTML
    function rebindQueueEvents() {
        logMessage('Reassociando eventos aos botões.');

        $('#game-queue').off('click', '.join-queue, .leave-queue').on('click', '.join-queue, .leave-queue', function () {
            const button = $(this);
            const queueId = button.data('queue-id');
            const actionType = button.hasClass('join-queue') ? 'join' : 'leave';

            if (!queueId || !['join', 'leave'].includes(actionType)) {
                alert('Ação inválida.');
                logMessage('Ação inválida detectada.');
                return;
            }

            // Desabilita o botão enquanto a solicitação é processada
            button.prop('disabled', true).text('Processando...');
            logMessage(`Botão desabilitado. Ação: ${actionType}, Fila ID: ${queueId}`);

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
                        logMessage(`Resposta recebida com sucesso. Estado do usuário: ${response.is_in_queue ? 'Na fila' : 'Fora da fila'}`);

                        // Atualiza dinamicamente o conteúdo da fila
                        $('#game-queue').html(response.html);
                        logMessage('HTML da fila atualizado.');

                        // Reassocia os eventos após a atualização do HTML
                        rebindQueueEvents();

                        // Atualiza o botão com base no estado do usuário
                        if (response.is_in_queue) {
                            button.removeClass('join-queue').addClass('leave-queue').text('Sair da Fila');
                            logMessage('Botão atualizado para "Sair da Fila".');

                            // Reproduz o som ao entrar na fila
                            if (queueJoinSound.canPlayType('audio/mpeg')) {
                                queueJoinSound.play().then(() => {
                                    logMessage('Som reproduzido com sucesso.');
                                }).catch((error) => {
                                    console.error('Erro ao reproduzir o som:', error);
                                    alert('O som não pôde ser reproduzido. Verifique as configurações do navegador.');
                                });
                            } else {
                                console.error('Formato de áudio não suportado pelo navegador.');
                            }
                        } else {
                            button.removeClass('leave-queue').addClass('join-queue').text('Entrar na Fila');
                            logMessage('Botão atualizado para "Entrar na Fila".');
                        }
                    } else {
                        alert(response.message || 'Erro ao processar a solicitação.');
                        logMessage('Erro na resposta do backend: ' + (response.message || 'Mensagem não especificada.'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Erro AJAX:', error);
                    alert('Ocorreu um erro ao processar sua solicitação.');
                    logMessage('Erro AJAX detectado: ' + error);
                },
                complete: function () {
                    // Reabilita o botão após a solicitação
                    button.prop('disabled', false);
                    logMessage('Botão reabilitado.');
                },
            });
        });
    }

    // Função para forçar a atualização do estado da fila
    function forceQueueUpdate() {
        logMessage('Forçando atualização do estado da fila.');

        $.ajax({
            url: queueSystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'game_system_get_queue_state',
                nonce: queueSystemAjax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $('#game-queue').html(response.html);
                    rebindQueueEvents();
                    logMessage('Estado da fila atualizado com sucesso.');
                } else {
                    logMessage('Erro ao atualizar o estado da fila: ' + (response.message || 'Mensagem não especificada.'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro ao atualizar o estado da fila:', error);
                logMessage('Erro AJAX ao atualizar o estado da fila: ' + error);
            },
        });
    }

    // Inicializa os eventos ao carregar a página
    rebindQueueEvents();

    // Força a atualização do estado da fila a cada 2 segundos
    setInterval(forceQueueUpdate, 2000);
});
