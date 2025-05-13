jQuery(document).ready(function ($) {
    // Evento para criar um time
    $('#create-team').on('click', function () {
        const teamName = prompt('Digite o nome do seu time:');
        if (teamName) {
            const button = $(this);
            button.prop('disabled', true).text('Criando...');
            $.post(gameSystemAjax.ajax_url, {
                action: 'create_team',
                nonce: gameSystemAjax.nonce,
                team_name: teamName,
            }, function (response) {
                if (response.success) {
                    alert('Time criado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao criar o time.');
                }
            }).always(function () {
                button.prop('disabled', false).text('Criar Time');
            });
        }
    });

    // Evento para entrar em um time
    $('.join-team').on('click', function () {
        const teamId = $(this).data('team-id');
        $.post(gameSystemAjax.ajax_url, {
            action: 'join_team',
            nonce: gameSystemAjax.nonce,
            team_id: teamId,
        }, function (response) {
            if (response.success) {
                alert('Você entrou no time!');
                location.reload();
            } else {
                alert('Erro ao entrar no time.');
            }
        });
    });
});