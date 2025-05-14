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
?>