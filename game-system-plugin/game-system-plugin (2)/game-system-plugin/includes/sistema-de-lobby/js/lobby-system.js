jQuery(document).ready(function ($) {
    // Evento para criar um time
    $('#create-team').on('click', function () {
        const button = $(this);
        button.prop('disabled', true).text('Criando...');
        $.post(lobbySystemAjax.ajax_url, {
            action: 'create_lobby_team',
            nonce: lobbySystemAjax.nonce,
        }, function (response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert(response.message || 'Erro ao criar o time.');
            }
        }).always(function () {
            button.prop('disabled', false).text('Criar Time');
        });
    });

    // Evento para entrar no time
    $('#teams').on('click', '.join-team', function () {
        const button = $(this);
        const teamId = button.data('team-id');

        if (!teamId) {
            alert('ID do time inválido.');
            return;
        }

        button.prop('disabled', true).text('Entrando...');
        $.ajax({
            url: lobbySystemAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'join_team',
                nonce: lobbySystemAjax.nonce,
                team_id: teamId,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    location.reload(); // Redireciona para atualizar a página
                } else {
                    alert(response.message || 'Erro ao entrar no time.');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro ao entrar no time:', error);
                alert('Ocorreu um erro ao processar sua solicitação.');
            },
            complete: function () {
                button.prop('disabled', false).text('Entrar no Time');
            },
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
                    location.reload(); // Redireciona para atualizar a página
                } else {
                    alert(response.message || 'Erro ao sair do time.');
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

    // Evento para sair do time no lobby
    $('#lobby').on('click', '.leave-team', function () {
        const button = $(this);
        const teamId = button.data('team-id'); // Obtém o ID do time do atributo data-team-id

        if (!teamId) {
            alert('ID do time inválido.'); // Mensagem de erro se o ID não for encontrado
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
                team_id: teamId, // Envia o ID do time para o backend
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    location.reload(); // Atualiza a página
                } else {
                    alert(response.message || 'Erro ao sair do time.');
                }
            },
            error: function () {
                alert('Ocorreu um erro ao processar sua solicitação.');
            },
            complete: function () {
                button.prop('disabled', false).text('Sair do Time');
            },
        });
    });
});