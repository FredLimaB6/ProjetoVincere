<?php
/*
Plugin Name: Sistema de Filas e Ranking
Description: Um plugin para gerenciar filas, partidas e rankings.
Version: 1.0
Author: Frederico Lima Baptista Duarte
*/

// Inclui a classe principal do sistema
require_once plugin_dir_path(__FILE__) . 'includes/class-game-system.php';

// Código de inicialização do plugin
function game_system_init() {
    $GLOBALS['gameSystem'] = new GameSystem();
}
add_action('plugins_loaded', 'game_system_init');

// Função para criar páginas automaticamente
function create_plugin_pages() {
    // Página da Fila
    if (!get_page_by_path('fila')) {
        wp_insert_post([
            'post_title' => sanitize_text_field('Fila'),
            'post_name' => sanitize_title('fila'),
            'post_content' => '[game_queue]', // Shortcode para exibir a fila
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }

    // Página da Partida
    if (!get_page_by_path('partida')) {
        wp_insert_post([
            'post_title' => sanitize_text_field('Partida'),
            'post_name' => sanitize_title('partida'),
            'post_content' => '[game_match]', // Shortcode para exibir a partida
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }

    // Página do Ranking
    if (!get_page_by_path('ranking')) {
        wp_insert_post([
            'post_title' => sanitize_text_field('Ranking'),
            'post_name' => sanitize_title('ranking'),
            'post_content' => '[game_ranking]', // Shortcode para exibir o ranking
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'create_plugin_pages');

// Shortcode para exibir as filas
function display_queue() {
    $gameSystem = $GLOBALS['gameSystem'];
    $queues = $gameSystem->getQueues(); // Obtém as filas usando o método público
    $currentUserId = get_current_user_id();

    $output = '<h3>Filas Ativas</h3>';

    // Verifica se há filas ativas
    if (empty($queues)) {
        // Cria automaticamente uma nova fila vazia
        $newQueueId = count($queues) + 1;
        $queues[$newQueueId] = []; // Cria uma fila vazia
        update_option('game_system_queues', $queues); // Salva a fila no banco de dados
        $gameSystem->setQueues($queues); // Atualiza a propriedade $queues no objeto
        $queues = $gameSystem->getQueues(); // Atualiza as filas após a criação

        $output .= "<p>Uma nova fila foi criada automaticamente.</p>";
    }

    foreach ($queues as $queueId => $queue) {
        $output .= "<h4>Fila {$queueId}:</h4>";

        // Exibe o progresso da fila com quadrados
        $output .= '<div style="display: flex; gap: 5px; margin-bottom: 10px;">';
        for ($i = 0; $i < 10; $i++) {
            $color = isset($queue[$i]) ? 'green' : 'gray';
            $output .= "<div style='width: 20px; height: 20px; background-color: {$color};'></div>";
        }
        $output .= '</div>';

        // Exibe o número de jogadores na fila
        $output .= '<p>' . count($queue) . '/10 jogadores na fila</p>';

        // Verifica se o jogador já está na fila
        if (in_array($currentUserId, $queue)) {
            $output .= '<p>Você já está nesta fila.</p>';
        } else {
            // Botão para entrar na fila
            $output .= '<form method="post">';
            $output .= '<input type="hidden" name="join_queue" value="' . $queueId . '">';
            $output .= '<button type="submit">Entrar na Fila</button>';
            $output .= '</form>';
        }
    }

    // Processa a entrada na fila
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_queue'])) {
        $queueId = sanitize_text_field($_POST['join_queue']);
        $message = $gameSystem->joinQueue($currentUserId);
        $output .= "<p>{$message}</p>";
    }

    return $output;
}
add_shortcode('game_queue', 'display_queue');

// Shortcode para exibir a partida
function display_match() {
    $gameSystem = $GLOBALS['gameSystem'];

    // Verifica se há uma partida ativa
    if (!$gameSystem->isGameActive()) {
        return "Nenhuma partida ativa no momento.";
    }

    // Verifica se a partida foi inicializada
    $currentMatch = $gameSystem->getCurrentMatch();
    if (empty($currentMatch)) {
        return "Nenhuma partida ativa no momento.";
    }

    // Exibe informações da partida
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

    // Formulário para votação
    $output .= '<h4>Vote no Time Vencedor:</h4>';
    $output .= '<form method="post">';
    $output .= '<button type="submit" name="vote_team" value="GR">Votar no Time GR</button>';
    $output .= '<button type="submit" name="vote_team" value="BL">Votar no Time BL</button>';
    $output .= '</form>';

    // Processa a votação
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_team'])) {
        $currentUserId = get_current_user_id();
        $team = sanitize_text_field($_POST['vote_team']);
        $message = $gameSystem->voteForWinner($currentUserId, $team);
        $output .= "<p>{$message}</p>";
    }

    return $output;
}
add_shortcode('game_match', 'display_match');

// Shortcode para exibir o ranking
function display_ranking() {
    $gameSystem = $GLOBALS['gameSystem'];
    $ranking = $gameSystem->getScores(); // Obtém as pontuações gerais dos jogadores
    $monthlyScores = $gameSystem->getMonthlyScores(); // Obtém as pontuações mensais

    if (empty($ranking)) {
        return "Nenhum jogador no ranking ainda.";
    }

    $output = '<h3>Ranking de Jogadores</h3>';
    $output .= '<table border="1" cellpadding="5" cellspacing="0">';
    $output .= '<tr><th>Jogador</th><th>Pontos Gerais</th><th>Pontuação Mensal</th><th>ELO</th><th>Nível</th></tr>';
    foreach ($ranking as $playerId => $score) {
        $elo = $gameSystem->playerElo[$playerId] ?? 1000; // ELO padrão é 1000
        $level = $gameSystem->getPlayerLevel($elo);
        $monthlyScore = $monthlyScores[$playerId] ?? 0; // Pontuação mensal padrão é 0
        $output .= "<tr><td>Jogador ID: {$playerId}</td><td>{$score}</td><td>{$monthlyScore}</td><td>{$elo}</td><td>Nível {$level}</td></tr>";
    }
    $output .= '</table>';

    return $output;
}
add_shortcode('game_ranking', 'display_ranking');
?>