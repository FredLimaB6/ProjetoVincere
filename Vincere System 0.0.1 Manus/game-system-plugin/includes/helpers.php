<?php
// Funções auxiliares para o plugin

/**
 * Formata uma data para exibição.
 *
 * @param string $date Data no formato Y-m-d H:i:s.
 * @return string Data formatada.
 */
function format_date($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

/**
 * Verifica se o usuário atual tem acesso a uma determinada funcionalidade 
 * com base no seu plano de assinatura (Básico ou Premium).
 *
 * Esta é uma função placeholder e precisará ser integrada com
 * WooCommerce/JetEngine para verificar os planos reais do usuário.
 *
 * @param string $feature A funcionalidade a ser verificada (e.g., 'pugs', 'lobbys', 'academy_content').
 * @param int|null $user_id O ID do usuário. Se null, usa o usuário atual.
 * @return bool True se o usuário tiver acesso, false caso contrário.
 */
function vincere_user_has_access($feature, $user_id = null) {
    if (is_null($user_id)) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false; // Usuário não logado não tem acesso
    }

    // Lógica Placeholder - Substituir pela integração real com WooCommerce/JetEngine
    // Exemplo: verificar se o usuário tem uma assinatura ativa que concede acesso à feature

    // Por enquanto, vamos simular alguns cenários:
    // Suponha que o usuário com ID 1 é Premium e tem acesso a tudo.
    // Suponha que o usuário com ID 2 é Basic e tem acesso a 'pugs' e 'lobbys'.
    // Outros usuários não têm acesso.

    $user_plan = ''; // Obter o plano do usuário (ex: 'premium', 'basic') - LÓGICA REAL AQUI

    // Exemplo de como você poderia obter o plano usando WooCommerce Subscriptions:
    /*
    if (class_exists('WC_Subscriptions_Manager')) {
        $subscriptions = WC_Subscriptions_Manager::get_users_subscriptions($user_id);
        foreach ($subscriptions as $subscription) {
            if ($subscription['status'] === 'active') {
                // Verificar os produtos da assinatura para determinar o plano
                // Exemplo: se o produto ID 123 é Premium, ID 456 é Basic
                foreach ($subscription['items'] as $item) {
                    if ($item['product_id'] == 123) { // ID do produto Premium
                        $user_plan = 'premium';
                        break;
                    } elseif ($item['product_id'] == 456) { // ID do produto Basic
                        $user_plan = 'basic';
                        break;
                    }
                }
                if ($user_plan) break;
            }
        }
    }
    */
    
    // Simulação para desenvolvimento:
    if ($user_id == 1) {
        $user_plan = 'premium';
    } elseif ($user_id == 2) {
        $user_plan = 'basic';
    }

    switch ($feature) {
        case 'pugs':
        case 'lobbys':
            return ($user_plan === 'basic' || $user_plan === 'premium');
        case 'academy_content': // Exemplo de conteúdo premium
            return ($user_plan === 'premium');
        default:
            return false;
    }
}

?>
