<?php
/**
 * Script para executar via cron job
 * Exemplo de cron: */5 * * * * /usr/bin/php /caminho/para/cron_processar_emails.php
 */

// Configurar para não exibir erros no cron
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log de execução
$log_file = 'logs/email_queue.log';
$log_dir = dirname($log_file);

// Criar diretório de logs se não existir
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

try {
    logMessage("Iniciando processamento da fila de emails...");
    
    require_once 'config/database.php';
    require_once 'classes/EmailQueue.php';
    
    $emailQueue = new EmailQueue();
    
    // Processar até 100 emails por execução do cron
    $resultado = $emailQueue->processarFila(100);
    
    logMessage("Processamento concluído - Processados: {$resultado['processados']}, Falhas: {$resultado['falhas']}, Total: {$resultado['total']}");
    
    // Obter estatísticas
    $stats = $emailQueue->getEstatisticas();
    logMessage("Estatísticas - Pendentes: {$stats['pendentes']}, Enviados: {$stats['enviados']}, Falhas: {$stats['falhas']}");
    
    // Limpar emails antigos a cada execução
    $limpeza = $emailQueue->limparEmailsAntigos(30);
    if ($limpeza) {
        logMessage("Emails antigos limpos com sucesso.");
    }
    
} catch (Exception $e) {
    logMessage("ERRO: " . $e->getMessage());
    exit(1);
}

logMessage("Script finalizado com sucesso.");
exit(0);
?>
