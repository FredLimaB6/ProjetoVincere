<?php
function display_queue() {
    if (!isset($GLOBALS['gameSystem'])) {
        return '<p>O sistema não está inicializado.</p>';
    }

    $gameSystem = $GLOBALS['gameSystem'];
    $queues = $gameSystem->getQueues();
    $currentUserId = get_current_user_id();

    ob_start();
    ?>
    <div id="game-queue">
        <h3>Filas Ativas</h3>
        <?php if (empty($queues)): ?>
            <p>Não há filas ativas no momento.</p>
        <?php else: ?>
            <?php foreach ($queues as $queueId => $queue): ?>
                <div class="queue">
                    <h4>Fila <?php echo esc_html($queueId); ?>:</h4>
                    <div style="display: flex; gap: 5px;">
                        <?php for ($i = 0; $i < 10; $i++): ?>
                            <div style="width: 20px; height: 20px; background-color: <?php echo isset($queue[$i]) ? 'green' : 'gray'; ?>;"></div>
                        <?php endfor; ?>
                    </div>
                    <p><?php echo count($queue); ?>/10 jogadores na fila</p>
                    <?php if (in_array($currentUserId, $queue)): ?>
                        <button class="leave-queue" data-queue-id="<?php echo esc_attr($queueId); ?>">Sair da Fila</button>
                    <?php else: ?>
                        <button class="join-queue" data-queue-id="<?php echo esc_attr($queueId); ?>">Entrar na Fila</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('game_queue', 'display_queue');

// Processa ações de entrada e saída da fila via AJAX
function game_system_process_queue() {
    check_ajax_referer('game_system_nonce', 'nonce');

    $gameSystem = $GLOBALS['gameSystem'];
    $currentUserId = get_current_user_id();
    $queueId = intval($_POST['queue_id']);
    $action = sanitize_text_field($_POST['action_type']);

    if ($action === 'join') {
        $message = $gameSystem->joinQueue($currentUserId);
    } elseif ($action === 'leave') {
        $message = $gameSystem->leaveQueue($currentUserId, $queueId);
    } else {
        wp_send_json_error(['message' => 'Ação inválida.']);
    }

    wp_send_json_success([
        'message' => $message,
        'queues' => $gameSystem->getQueues(),
    ]);
}
add_action('wp_ajax_game_system_process_queue', 'game_system_process_queue');
add_action('wp_ajax_nopriv_game_system_process_queue', 'game_system_process_queue');

// Shortcode para exibir a partida
function display_match() {
    $gameSystem = $GLOBALS['gameSystem'];

    // Verifica se há uma partida ativa
    if (!$gameSystem->isGameActive()) {
        return "<p>Nenhuma partida ativa no momento.</p>";
    }

    $currentMatch = $gameSystem->getCurrentMatch();
    if (empty($currentMatch)) {
        return "<p>Nenhuma partida ativa no momento.</p>";
    }

    $output = '<h3>Partida Atual</h3>';
    $output .= '<p><strong>Mapa:</strong> ' . $currentMatch['map'] . '</p>';

    // Exibe os times
    $output .= '<h4>Time GR:</h4><ul>';
    foreach ($currentMatch['teams']['GR'] as $playerId) {
        $output .= "<li>Jogador ID: {$playerId}</li>";
    }
    $output .= '</ul>';

    $output .= '<h4>Time BL:</h4><ul>';
    foreach ($currentMatch['teams']['BL'] as $playerId) {
        $output .= "<li>Jogador ID: {$playerId}</li>";
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('game_match', 'display_match');

// Shortcode para exibir o ranking
function display_ranking() {
    $gameSystem = $GLOBALS['gameSystem'];
    $ranking = $gameSystem->getScores();

    if (empty($ranking)) {
        return "<p>Nenhum jogador no ranking ainda.</p>";
    }

    $output = '<h3>Ranking de Jogadores</h3>';
    $output .= '<table border="1" cellpadding="5" cellspacing="0">';
    $output .= '<tr><th>Jogador</th><th>Pontos</th><th>ELO</th></tr>';
    foreach ($ranking as $playerId => $score) {
        $elo = $gameSystem->playerElo[$playerId] ?? 1000;
        $output .= "<tr><td>Jogador ID: {$playerId}</td><td>{$score}</td><td>{$elo}</td></tr>";
    }
    $output .= '</table>';

    return $output;
}
add_shortcode('game_ranking', 'display_ranking');

// Shortcode para exibir o ranking com filtros e busca
function display_ranking_with_filters() {
    $gameSystem = $GLOBALS['gameSystem'];
    $rankingManager = new RankingManager();

    // Determina o tipo de ranking
    $currentType = $_GET['ranking_type'] ?? 'general';
    $ranking = $rankingManager->getRankingByType($currentType);

    // Aplica o filtro de busca, se necessário
    if (isset($_GET['search_player'])) {
        $searchTerm = sanitize_text_field($_GET['search_player']);
        $ranking = $rankingManager->filterRanking($searchTerm, $ranking);
    }

    ob_start();
    ?>
    <form method="get">
        <label for="ranking_type">Tipo de Ranking:</label>
        <select name="ranking_type" id="ranking_type">
            <option value="general" <?php selected($currentType, 'general'); ?>>Geral</option>
            <option value="monthly" <?php selected($currentType, 'monthly'); ?>>Mensal</option>
        </select>
        <input type="text" name="search_player" placeholder="Buscar por ID ou Nome" value="<?php echo esc_attr($_GET['search_player'] ?? ''); ?>">
        <button type="submit">Filtrar</button>
    </form>
    <table>
        <thead>
            <tr><th>Jogador</th><th>Pontos</th></tr>
        </thead>
        <tbody>
            <?php if (empty($ranking)): ?>
                <tr><td colspan="2">Nenhum jogador encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($ranking as $playerId => $score): ?>
                    <tr><td><?php echo $playerId; ?></td><td><?php echo $score; ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
add_shortcode('game_ranking_with_filters', 'display_ranking_with_filters');

// Shortcode para exibir o ranking com histórico
function display_ranking_with_history() {
    $gameSystem = $GLOBALS['gameSystem'];
    $rankingManager = new RankingManager();

    ob_start();

    // Formulário de busca no histórico
    ?>
    <form method="get">
        <label for="search_month">Pesquisar Histórico (YYYY-MM):</label>
        <input type="text" name="search_month" id="search_month" placeholder="Ex: 2025-04" value="<?php echo esc_attr($_GET['search_month'] ?? ''); ?>">
        <button type="submit">Pesquisar</button>
    </form>
    <?php

    // Pesquisa no histórico de rankings
    if (isset($_GET['search_month'])) {
        $month = sanitize_text_field($_GET['search_month']);
        $history = $rankingManager->searchRankingHistory($month);

        echo "<h2>Histórico de Rankings - {$month}</h2>";
        if (empty($history)) {
            echo '<p>Nenhum histórico encontrado para este mês.</p>';
        } else {
            echo '<table>';
            echo '<thead><tr><th>Jogador</th><th>Pontos</th></tr></thead>';
            echo '<tbody>';
            foreach ($history as $playerId => $score) {
                echo "<tr><td>{$playerId}</td><td>{$score}</td></tr>";
            }
            echo '</tbody></table>';
        }
    }

    // Exibe o ranking geral
    echo '<h2>Ranking Geral</h2>';
    $ranking = $gameSystem->getScores();
    if (empty($ranking)) {
        echo '<p>Nenhum jogador no ranking ainda.</p>';
    } else {
        echo '<table>';
        echo '<thead><tr><th>Jogador</th><th>Pontos</th></tr></thead>';
        echo '<tbody>';
        foreach ($ranking as $playerId => $score) {
            echo "<tr><td>{$playerId}</td><td>{$score}</td></tr>";
        }
        echo '</tbody></table>';
    }

    return ob_get_clean();
}
add_shortcode('game_ranking_with_history', 'display_ranking_with_history');

// Shortcode para exibir conquistas de usuário
function display_achievements() {
    $badgesManager = new BadgesManager();
    $currentUserId = get_current_user_id();
    $achievements = $badgesManager->getBadges($currentUserId);

    if (empty($achievements)) {
        return '<p>Você ainda não desbloqueou nenhuma conquista.</p>';
    }

    $output = '<h3>Suas Conquistas</h3><ul>';
    foreach ($achievements as $achievement) {
        $output .= "<li>{$achievement}</li>";
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('game_achievements', 'display_achievements');

// Shortcode para exibir a loja de créditos
function display_store() {
    $creditsManager = new CreditsManager();
    $currentUserId = get_current_user_id();
    $credits = $creditsManager->getCredits($currentUserId);

    $items = [
        ['name' => 'Skin Exclusiva', 'cost' => 100],
        ['name' => 'Avatar Personalizado', 'cost' => 50],
        ['name' => 'Boost de Pontuação', 'cost' => 200],
    ];

    $output = "<h3>Loja de Créditos</h3><p>Seus Créditos: {$credits}</p><ul>";
    foreach ($items as $item) {
        $output .= "<li>{$item['name']} - {$item['cost']} créditos 
            <form method='post'>
                <input type='hidden' name='item_name' value='{$item['name']}'>
                <input type='hidden' name='item_cost' value='{$item['cost']}'>
                <button type='submit'>Comprar</button>
            </form>
        </li>";
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('game_store', 'display_store');

function display_player_stats() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para ver suas estatísticas.</p>';
    }

    $playerStatsManager = new PlayerStatsManager();
    $currentUserId = get_current_user_id();
    $stats = $playerStatsManager->getPlayerStats($currentUserId);

    ob_start();
    ?>
    <div class="player-stats">
        <h3>Estatísticas do Jogador</h3>
        <p><strong>Partidas Jogadas:</strong> <?php echo esc_html($stats['total_matches']); ?></p>
        <p><strong>Vitórias:</strong> <?php echo esc_html($stats['wins']); ?></p>
        <p><strong>Derrotas:</strong> <?php echo esc_html($stats['losses']); ?></p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('player_stats', 'display_player_stats');
?>