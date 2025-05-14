<?php
function display_match() {
    error_log('[Display] Exibindo informações da partida.');

    // Verifica se a variável global $gameSystem está definida
    if (!isset($GLOBALS['gameSystem'])) {
        error_log('[Display] Erro: O sistema de partidas não está inicializado.');
        return "<p>O sistema de partidas não está inicializado.</p>";
    }

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
    $output .= '<p><strong>Mapa:</strong> ' . esc_html($currentMatch['map']) . '</p>';

    $output .= '<h4>Time GR:</h4><ul>';
    foreach ($currentMatch['teams']['GR'] as $playerId) {
        $output .= "<li>Jogador ID: " . esc_html($playerId) . "</li>";
    }
    $output .= '</ul>';

    $output .= '<h4>Time BL:</h4><ul>';
    foreach ($currentMatch['teams']['BL'] as $playerId) {
        $output .= "<li>Jogador ID: " . esc_html($playerId) . "</li>";
    }
    $output .= '</ul>';

    return $output;
}