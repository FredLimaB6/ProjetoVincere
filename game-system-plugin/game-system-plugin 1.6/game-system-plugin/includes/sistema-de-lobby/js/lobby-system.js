jQuery(document).ready(function ($) {
    // Evento para criar um time
    $('#create-team').on('click', function () {
        const teamName = prompt('Digite o nome do seu time:');
        if (teamName) {
            const button = $(this);
            button.prop('disabled', true).text('Criando...');
            $.post(lobbySystemAjax.ajax_url, {
                action: 'create_lobby_team',
                nonce: lobbySystemAjax.nonce,
                team_name: teamName,
            }, function (response) {
                if (response.success) {
                    alert('Time criado com sucesso!');
                    location.reload();
                } else {
                    alert(response.message || 'Erro ao criar o time.');
                }
            }).always(function () {
                button.prop('disabled', false).text('Criar Time');
            });
        }
    });

    // Evento para entrar em um time
    $('.join-team').on('click', function () {
        const teamId = $(this).data('team-id');
        $.post(lobbySystemAjax.ajax_url, {
            action: 'join_team',
            nonce: lobbySystemAjax.nonce,
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

    // Evento para sair do time
    $('#teams').on('click', '.leave-team', function () {
        const button = $(this);
        const teamId = button.data('team-id');

        if (!teamId) {
            alert('ID do time inválido.');
            return;
        }

        if (!confirm('Tem certeza de que deseja sair do time?')) {
            return;
        }

        button.prop('disabled', true).text('Saindo...');

        $.ajax({
            url: lobbySystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'leave_team',
                nonce: lobbySystemAjax.nonce,
                team_id: teamId,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro ao sair do time:', error);
                alert('Ocorreu um erro ao processar sua solicitação.');
            },
            complete: function () {
                button.prop('disabled', false).text('Sair do Time');
            },
        });
    });
});