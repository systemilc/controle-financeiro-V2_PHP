<?php
/**
 * Configuração de Timezone do Sistema
 */

// Definir timezone para São Paulo (Brasil)
date_default_timezone_set('America/Sao_Paulo');

// Função para obter data atual formatada
function getCurrentDate() {
    return date('Y-m-d');
}

// Função para obter data atual formatada para exibição
function getCurrentDateFormatted() {
    return date('d/m/Y');
}

// Função para obter data e hora atual
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

// Função para obter data e hora atual formatada
function getCurrentDateTimeFormatted() {
    return date('d/m/Y H:i:s');
}
?>
