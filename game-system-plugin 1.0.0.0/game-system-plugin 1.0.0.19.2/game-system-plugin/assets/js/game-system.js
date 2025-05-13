//game-system.js
jQuery(document).ready(function ($) {
    $('#game-queue').on('click', '.join-queue, .leave-queue', function () {
        const button = $(this);
        const queueId = button.data('queue-id');
        const actionType = button.hasClass('join-queue') ? 'join' : 'leave';

        $.ajax({
            url: gameSystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'game_system_process_queue',
                nonce: gameSystemAjax.nonce,
                queue_id: queueId,
                action_type: actionType,
            },
            success: function (response) {
                if (response.success) {
                    $('#game-queue').html(response.data.message);
                    location.reload(); // Atualiza a página para refletir as mudanças
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('Ocorreu um erro ao processar sua solicitação.');
            },
        });
    });
});