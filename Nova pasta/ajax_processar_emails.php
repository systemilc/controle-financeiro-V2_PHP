<?php
/**
 * AJAX endpoint para processar fila de emails
 */
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/EmailQueue.php';

// Verificar autenticação
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Verificar se é admin
if ($auth->getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Processar fila de emails
$emailQueue = new EmailQueue();
$resultado = $emailQueue->processarFila(10); // Processar até 10 emails

// Obter estatísticas
$stats = $emailQueue->getEstatisticas();

// Retornar resultado
echo json_encode([
    'success' => true,
    'resultado' => $resultado,
    'estatisticas' => $stats,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
