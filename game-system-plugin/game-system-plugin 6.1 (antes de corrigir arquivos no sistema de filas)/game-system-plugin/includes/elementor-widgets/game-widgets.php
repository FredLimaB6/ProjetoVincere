<?php
if (!defined('ABSPATH')) {
    exit; // Sai se o arquivo for acessado diretamente.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

// Widget para o shortcode [game_match]
class Game_Match_Widget extends Widget_Base {
    public function get_name() {
        return 'game_match_widget';
    }

    public function get_title() {
        return 'Game Match';
    }

    public function get_icon() {
        return 'eicon-post';
    }

    public function get_categories() {
        return ['vincere']; // Atualizado para a nova categoria
    }

    protected function render() {
        echo '<div class="custom-widget game-match-widget fade-in">';
        echo do_shortcode('[game_match]');
        echo '</div>';
    }
}

// Widget para o shortcode [game_ranking]
class Game_Ranking_Widget extends Widget_Base {
    public function get_name() {
        return 'game_ranking_widget';
    }

    public function get_title() {
        return 'Game Ranking';
    }

    public function get_icon() {
        return 'eicon-number-field';
    }

    public function get_categories() {
        return ['vincere']; // Atualizado para a nova categoria
    }

    protected function render() {
        echo do_shortcode('[game_ranking]'); // Reutiliza o shortcode existente
    }
}

// Widget para o shortcode [game_achievements]
class Game_Achievements_Widget extends Widget_Base {
    public function get_name() {
        return 'game_achievements_widget';
    }

    public function get_title() {
        return 'Game Achievements';
    }

    public function get_icon() {
        return 'eicon-star';
    }

    public function get_categories() {
        return ['vincere']; // Atualizado para a nova categoria
    }

    protected function render() {
        echo do_shortcode('[game_achievements]'); // Reutiliza o shortcode existente
    }
}

// Widget para o shortcode [game_store]
class Game_Store_Widget extends Widget_Base {
    public function get_name() {
        return 'game_store_widget';
    }

    public function get_title() {
        return 'Game Store';
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['vincere']; // Atualizado para a nova categoria
    }

    protected function render() {
        echo do_shortcode('[game_store]'); // Reutiliza o shortcode existente
    }
}

// Widget para o shortcode [player_stats]
class Player_Stats_Widget extends Widget_Base {
    public function get_name() {
        return 'player_stats_widget';
    }

    public function get_title() {
        return 'Player Stats';
    }

    public function get_icon() {
        return 'eicon-user';
    }

    public function get_categories() {
        return ['vincere']; // Atualizado para a nova categoria
    }

    protected function render() {
        echo do_shortcode('[player_stats]'); // Reutiliza o shortcode existente
    }
}
?>