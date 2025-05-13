<?php
function display_ranking() {
    if (!isset($GLOBALS['gameSystem'])) {
        return '<p>O sistema não está inicializado.</p>';
    }

    $gameSystem = $GLOBALS['gameSystem'];
    $ranking = $gameSystem->getScores();

    if (empty($ranking)) {
        return '<p>Nenhum jogador no ranking ainda.</p>';
    }

    $output = '<h3>Ranking de Jogadores</h3>';
    $output .= '<table border="1" cellpadding="5" cellspacing="0">';
    $output .= '<tr><th>Jogador</th><th>Pontos</th><th>ELO</th></tr>';
    foreach ($ranking as $playerId => $score) {
        $elo = $gameSystem->eloManager->getElo($playerId) ?? 1000;
        $output .= "<tr><td>Jogador ID: {$playerId}</td><td>{$score}</td><td>{$elo}</td></tr>";
    }
    $output .= '</table>';

    return $output;
}

function display_ranking_with_filters() {
    $gameSystem = $GLOBALS['gameSystem'];
    $rankingManager = new RankingManager();

    // Obtém o tipo de ranking (geral ou mensal) e o termo de busca
    $currentType = $_GET['ranking_type'] ?? 'general';
    $searchTerm = $_GET['search_player'] ?? '';

    // Obtém o ranking com base no tipo selecionado
    $ranking = $rankingManager->getRankingByType($currentType);

    // Aplica o filtro de busca, se necessário
    if (!empty($searchTerm)) {
        $ranking = $rankingManager->filterRanking($searchTerm, $ranking);
    }

    ob_start();
    ?>
    <div class="ranking-filters">
        <form method="get">
            <label for="ranking_type">Tipo de Ranking:</label>
            <select name="ranking_type" id="ranking_type">
                <option value="general" <?php selected($currentType, 'general'); ?>>Geral</option>
                <option value="monthly" <?php selected($currentType, 'monthly'); ?>>Mensal</option>
            </select>
            <label for="search_player">Buscar Jogador:</label>
            <input type="text" name="search_player" id="search_player" value="<?php echo esc_attr($searchTerm); ?>">
            <button type="submit">Filtrar</button>
        </form>
    </div>
    <div class="ranking-results">
        <h3>Ranking <?php echo $currentType === 'monthly' ? 'Mensal' : 'Geral'; ?></h3>
        <?php if (empty($ranking)): ?>
            <p>Nenhum jogador encontrado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Jogador</th>
                        <th>Pontos</th>
                        <th>ELO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $playerId => $score): ?>
                        <tr>
                            <td><?php echo esc_html($playerId); ?></td>
                            <td><?php echo esc_html($score); ?></td>
                            <td><?php echo esc_html($gameSystem->eloManager->getElo($playerId) ?? 1000); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>