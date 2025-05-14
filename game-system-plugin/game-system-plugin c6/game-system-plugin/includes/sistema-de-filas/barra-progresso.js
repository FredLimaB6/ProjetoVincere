jQuery(document).ready(function ($) {
    function updateQueueProgress(currentPlayers, maxPlayers) {
        const progressPercentage = (currentPlayers / maxPlayers) * 100;
        $('.queue-progress-bar').css('width', progressPercentage + '%');
        $('.queue-progress-text').text(`${currentPlayers}/${maxPlayers} jogadores`);
    }

    $('.queue-button').on('click', function () {
        const action = $(this).data('action');
        const button = $(this);

        button.prop('disabled', true).text('Processando...');

        $.ajax({
            url: queueSystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'game_system_process_queue',
                nonce: queueSystemAjax.nonce,
                action_type: action,
            },
            success: function (response) {
                if (response.success) {
                    updateQueueProgress(response.current_players, response.max_players);

                    if (action === 'join') {
                        $('.join-queue').hide();
                        $('.leave-queue').show();
                    } else {
                        $('.join-queue').show();
                        $('.leave-queue').hide();
                    }
                } else {
                    alert(response.message || 'Erro ao processar a solicitação.');
                }
            },
            error: function () {
                alert('Erro ao processar a solicitação.');
            },
            complete: function () {
                button.prop('disabled', false).text(action === 'join' ? 'Entrar na Fila' : 'Sair da Fila');
            },
        });
    });
});