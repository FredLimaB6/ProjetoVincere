<?php
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
?>