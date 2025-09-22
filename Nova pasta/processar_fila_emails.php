<?php
/**
 * Script para processar fila de emails em background
 * Execute via cron job ou chamada manual
 */

// Configurar timeout para processamento longo
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '256M');

require_once 'config/database.php';
require_once 'classes/EmailQueue.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando processamento da fila de emails...\n";

$emailQueue = new EmailQueue();

// Processar até 50 emails por execução
$resultado = $emailQueue->processarFila(50);

echo "[" . date('Y-m-d H:i:s') . "] Processamento concluído:\n";
echo "- Emails processados: " . $resultado['processados'] . "\n";
echo "- Falhas: " . $resultado['falhas'] . "\n";
echo "- Total: " . $resultado['total'] . "\n";

// Obter estatísticas
$stats = $emailQueue->getEstatisticas();
echo "\nEstatísticas da fila:\n";
echo "- Total: " . $stats['total'] . "\n";
echo "- Pendentes: " . $stats['pendentes'] . "\n";
echo "- Processando: " . $stats['processando'] . "\n";
echo "- Enviados: " . $stats['enviados'] . "\n";
echo "- Falhas: " . $stats['falhas'] . "\n";

// Limpar emails antigos (mais de 30 dias)
$limpeza = $emailQueue->limparEmailsAntigos(30);
if ($limpeza) {
    echo "\nEmails antigos limpos com sucesso.\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Script finalizado.\n";
?>
