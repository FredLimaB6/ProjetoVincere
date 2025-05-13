<?php
function display_lobby() {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para acessar o lobby de desafios.</p>';
    }

    $challengeManager = new ChallengeManager();
    $teams = $challengeManager->getTeams();
    $currentUserId = get_current_user_id();

    // Organiza os times em disponíveis e completos
    $availableTeams = [];
    $completeTeams = [];
    foreach ($teams as $teamId => $team) {
        if ($team['status'] === 'complete') {
            $completeTeams[$teamId] = $team;
        } else {
            $availableTeams[$teamId] = $team;
        }
    }

    ob_start();
    ?>
    <div id="lobby">
        <h3>Lobby de Desafios</h3>
        <button id="create-team">Criar Time</button>

        <!-- Abas para Times Disponíveis e Completos -->
        <nav class="nav-tab-wrapper">
            <a href="#available-teams" class="nav-tab nav-tab-active">Times Disponíveis</a>
            <a href="#complete-teams" class="nav-tab">Times Completos</a>
        </nav>

        <!-- Times Disponíveis -->
        <div id="available-teams" class="tab-content active">
            <?php if (empty($availableTeams)): ?>
                <p>Nenhum time disponível no momento. Crie um novo time para começar!</p>
            <?php else: ?>
                <?php foreach ($availableTeams as $teamId => $team): ?>
                    <div class="team">
                        <h4><?php echo esc_html($team['name']); ?></h4>
                        <p>Jogadores: <?php echo count($team['players']); ?>/5</p>
                        <ul>
                            <?php foreach ($team['players'] as $playerId): ?>
                                <?php
                                $user = get_userdata($playerId);
                                $nickname = $user ? $user->user_login : 'Jogador Anônimo';
                                $elo = $challengeManager->getPlayerElo($playerId); // Função para obter o ELO
                                ?>
                                <li><?php echo esc_html($nickname); ?> - ELO: <?php echo esc_html($elo); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!in_array($currentUserId, $team['players'])): ?>
                            <button class="join-team" data-team-id="<?php echo esc_attr($teamId); ?>">Entrar no Time</button>
                        <?php else: ?>
                            <p>Você já está neste time.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Times Completos -->
        <div id="complete-teams" class="tab-content">
            <?php if (empty($completeTeams)): ?>
                <p>Nenhum time completo no momento.</p>
            <?php else: ?>
                <?php foreach ($completeTeams as $teamId => $team): ?>
                    <div class="team">
                        <h4><?php echo esc_html($team['name']); ?></h4>
                        <p>Jogadores: <?php echo count($team['players']); ?>/5</p>
                        <ul>
                            <?php foreach ($team['players'] as $playerId): ?>
                                <?php
                                $user = get_userdata($playerId);
                                $nickname = $user ? $user->user_login : 'Jogador Anônimo';
                                $elo = $challengeManager->getPlayerElo($playerId); // Função para obter o ELO
                                ?>
                                <li><?php echo esc_html($nickname); ?> - ELO: <?php echo esc_html($elo); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Alterna entre as abas
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.nav-tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Remove a classe ativa de todas as abas e conteúdos
                    tabs.forEach(t => t.classList.remove('nav-tab-active'));
                    contents.forEach(c => c.classList.remove('active'));

                    // Adiciona a classe ativa à aba e conteúdo clicados
                    tab.classList.add('nav-tab-active');
                    const target = document.querySelector(tab.getAttribute('href'));
                    target.classList.add('active');
                });
            });
        });
    </script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .team { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        .team ul { list-style: none; padding: 0; }
        .team ul li { margin: 5px 0; }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('challenge_lobby', 'display_lobby');

function display_challenge_match() {
    return '<h3>Partida de Desafio</h3><p>Detalhes da partida serão exibidos aqui.</p>';
}
add_shortcode('challenge_match', 'display_challenge_match');

add_action('wp_ajax_create_team', 'create_team');
add_action('wp_ajax_join_team', 'join_team');

function create_team() {
    check_ajax_referer('game_system_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado para criar um time.']);
    }

    $teamName = sanitize_text_field($_POST['team_name']);
    $currentUserId = get_current_user_id();

    $challengeManager = new ChallengeManager();
    $teamId = $challengeManager->createTeam($teamName, $currentUserId);

    if ($teamId) {
        wp_send_json_success(['message' => 'Time criado com sucesso!', 'team_id' => $teamId]);
    } else {
        wp_send_json_error(['message' => 'Erro ao criar o time.']);
    }
}

function join_team() {
    check_ajax_referer('game_system_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado para entrar em um time.']);
    }

    $teamId = sanitize_text_field($_POST['team_id']);
    $currentUserId = get_current_user_id();

    $challengeManager = new ChallengeManager();
    $success = $challengeManager->joinTeam($teamId, $currentUserId);

    if ($success) {
        wp_send_json_success(['message' => 'Você entrou no time!']);
    } else {
        wp_send_json_error(['message' => 'Erro ao entrar no time.']);
    }
}
?>