<?php
if (!defined('ABSPATH')) {
    exit; // Sai se o arquivo for acessado diretamente.
    define('ABSPATH', dirname(__FILE__) . '/../../../');
    require_once(ABSPATH . 'wp-load.php');
    require_once(ABSPATH . 'wp-includes/formatting.php');
}

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
?>