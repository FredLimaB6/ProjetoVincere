<?php
// Sistema de Feedback e Suporte

// Cria a página de feedback automaticamente
function create_feedback_page() {
    if (!get_page_by_path('feedbacks')) {
        wp_insert_post([
            'post_title' => 'Feedbacks',
            'post_name' => 'feedbacks',
            'post_content' => '[game_feedback_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'create_feedback_page');

// Shortcode para exibir o formulário de feedback
function display_feedback_form() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para enviar feedbacks.</p>';
    }

    ob_start();
    ?>
    <form id="feedback-form" method="post">
        <label for="feedback-category">Categoria:</label>
        <select id="feedback-category" name="feedback_category" required>
            <option value="Sugestões">Sugestões</option>
            <option value="Problemas técnicos">Problemas técnicos</option>
            <option value="Reclamações">Reclamações</option>
        </select>

        <label for="feedback-message">Deixe seu feedback:</label>
        <textarea id="feedback-message" name="feedback_message" rows="5" required></textarea>

        <button type="submit">Enviar</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('game_feedback_form', 'display_feedback_form');

// Processa o envio de feedback
function process_feedback_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_message']) && is_user_logged_in()) {
        $feedback = sanitize_textarea_field($_POST['feedback_message']);
        $category = sanitize_text_field($_POST['feedback_category']);
        $currentUserId = get_current_user_id();

        $feedbacks = get_option('game_system_feedbacks', []);
        $feedbacks[] = [
            'user_id' => $currentUserId,
            'category' => $category,
            'message' => $feedback,
            'date' => current_time('mysql'),
            'response' => null, // Resposta do administrador
        ];
        update_option('game_system_feedbacks', $feedbacks);

        wp_redirect(home_url('/feedbacks?success=1'));
        exit;
    }
}
add_action('init', 'process_feedback_submission');

// Renderiza a página de feedbacks no painel administrativo
function game_system_feedback_page() {
    $feedbacks = get_option('game_system_feedbacks', []);

    echo '<div class="wrap">';
    echo '<h1>Feedbacks dos Usuários</h1>';

    if (empty($feedbacks)) {
        echo '<p>Nenhum feedback enviado ainda.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Usuário</th><th>Categoria</th><th>Mensagem</th><th>Data</th><th>Resposta</th><th>Ações</th></tr></thead>';
        echo '<tbody>';
        foreach ($feedbacks as $index => $feedback) {
            $user = get_userdata($feedback['user_id']);
            $username = $user ? $user->user_login : 'Usuário Anônimo';
            $response = $feedback['response'] ? esc_html($feedback['response']) : 'Não respondido';

            echo "<tr>
                <td>{$username}</td>
                <td>{$feedback['category']}</td>
                <td>{$feedback['message']}</td>
                <td>{$feedback['date']}</td>
                <td>{$response}</td>
                <td>
                    <form method='post'>
                        <input type='hidden' name='feedback_index' value='{$index}'>
                        <textarea name='feedback_response' rows='2' placeholder='Responder...'></textarea>
                        <button type='submit' class='button button-primary'>Enviar Resposta</button>
                    </form>
                </td>
            </tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Processa a resposta do administrador
function process_feedback_response() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_index']) && isset($_POST['feedback_response'])) {
        $feedbackIndex = intval($_POST['feedback_index']);
        $response = sanitize_textarea_field($_POST['feedback_response']);

        $feedbacks = get_option('game_system_feedbacks', []);
        if (isset($feedbacks[$feedbackIndex])) {
            $feedbacks[$feedbackIndex]['response'] = $response;

            // Envia um e-mail para o usuário
            $user = get_userdata($feedbacks[$feedbackIndex]['user_id']);
            if ($user) {
                wp_mail(
                    $user->user_email,
                    'Resposta ao seu Feedback',
                    "Sua mensagem: {$feedbacks[$feedbackIndex]['message']}\n\nResposta: {$response}"
                );
            }

            update_option('game_system_feedbacks', $feedbacks);
            echo '<div class="updated"><p>Resposta enviada com sucesso!</p></div>';
        }
    }
}
add_action('admin_post_process_feedback_response', 'process_feedback_response');
?>