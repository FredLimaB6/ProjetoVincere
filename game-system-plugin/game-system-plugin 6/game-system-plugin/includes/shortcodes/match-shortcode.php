<?php
function display_match() {
    $gameSystem = $GLOBALS['gameSystem'];

    if (!$gameSystem->isGameActive()) {
        return "<p>Nenhuma partida ativa no momento.</p>";
    }

    $currentMatch = $gameSystem->getCurrentMatch();
    if (empty($currentMatch)) {
        return "<p>Nenhuma partida ativa no momento.</p>";
    }

    $output = '<h3>Partida Atual</h3>';
    $output .= '<p><strong>Mapa:</strong> ' . $currentMatch['map'] . '</p>';

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