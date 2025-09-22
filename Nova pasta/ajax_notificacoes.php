<?php
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Notificacao.php';

// Verificar autenticação
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

// Instanciar classe de notificações
$notificacao = new Notificacao($conn);
$notificacao->grupo_id = $grupo_id;

// Processar ações AJAX
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_count':
            $count = $notificacao->contarNaoLidas($current_user['id']);
            echo json_encode(['count' => $count]);
            break;
            
        case 'get_notifications':
            $notificacoes = $notificacao->getNaoLidas($current_user['id']);
            echo json_encode(['notifications' => $notificacoes]);
            break;
            
        case 'mark_read':
            $notificacao_id = $_POST['notificacao_id'] ?? 0;
            $result = $notificacao->marcarComoLida($notificacao_id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_read':
            $result = $notificacao->marcarTodasComoLidas($current_user['id']);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Método não permitido']);
}
?>
