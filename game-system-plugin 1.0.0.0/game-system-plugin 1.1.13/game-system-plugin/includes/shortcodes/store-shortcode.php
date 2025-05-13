<?php
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
        $output .= "<li>{$item['name']} - {$item['cost']} créditos</li>";
    }
    $output .= '</ul>';

    return $output;
}
?>