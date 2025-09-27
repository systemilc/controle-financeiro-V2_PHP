<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/SugestaoCompra.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$sugestao = new SugestaoCompra($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            $id = $_GET['id'] ?? 0;
            $sugestao_data = $sugestao->getById($id);
            
            if ($sugestao_data && $sugestao_data['grupo_id'] == $grupo_id) {
                echo json_encode([
                    'success' => true,
                    'sugestao' => $sugestao_data
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sugestão não encontrada'
                ]);
            }
            break;

        case 'marcar_produto_acabado':
            $produto_id = $_POST['produto_id'] ?? 0;
            
            if ($produto_id) {
                if ($sugestao->marcarProdutoAcabado($produto_id, $grupo_id)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Produto marcado como acabado e sugestão gerada com sucesso!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao marcar produto como acabado'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do produto não informado'
                ]);
            }
            break;

        case 'marcar_comprada':
            $id = $_POST['sugestao_id'] ?? 0;
            $sugestao->id = $id;
            
            if ($sugestao->marcarComoComprada()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sugestão marcada como comprada com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao marcar sugestão como comprada'
                ]);
            }
            break;

        case 'cancelar':
            $id = $_POST['sugestao_id'] ?? 0;
            $sugestao->id = $id;
            $sugestao->status = 'cancelada';
            
            if ($sugestao->update()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sugestão cancelada com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao cancelar sugestão'
                ]);
            }
            break;

        case 'atualizar':
            $id = $_POST['sugestao_id'] ?? 0;
            $sugestao->id = $id;
            $sugestao->quantidade_sugerida = $_POST['quantidade_sugerida'] ?? 0;
            $sugestao->prioridade = $_POST['prioridade'] ?? 'media';
            $sugestao->status = $_POST['status'] ?? 'ativa';
            $sugestao->observacoes = $_POST['observacoes'] ?? '';
            
            if ($sugestao->update()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sugestão atualizada com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar sugestão'
                ]);
            }
            break;

        case 'get_estatisticas':
            $estatisticas = $sugestao->getEstatisticas($grupo_id);
            echo json_encode([
                'success' => true,
                'estatisticas' => $estatisticas
            ]);
            break;

        case 'get_by_prioridade':
            $prioridade = $_GET['prioridade'] ?? '';
            $sugestoes = $sugestao->getByPrioridade($grupo_id, $prioridade);
            echo json_encode([
                'success' => true,
                'sugestoes' => $sugestoes
            ]);
            break;

        case 'search':
            $termo = $_GET['termo'] ?? '';
            $sugestoes = $sugestao->searchByProduto($grupo_id, $termo);
            echo json_encode([
                'success' => true,
                'sugestoes' => $sugestoes
            ]);
            break;

        case 'get_fornecedores':
            $query = "SELECT id, nome FROM fornecedores WHERE grupo_id = :grupo_id ORDER BY nome";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":grupo_id", $grupo_id);
            $stmt->execute();
            $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'fornecedores' => $fornecedores
            ]);
            break;

        case 'get_tipos_pagamento':
            $query = "SELECT id, nome FROM tipos_pagamento WHERE grupo_id = :grupo_id ORDER BY nome";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":grupo_id", $grupo_id);
            $stmt->execute();
            $tipos_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'tipos_pagamento' => $tipos_pagamento
            ]);
            break;

        case 'get_contas':
            $query = "SELECT id, nome FROM contas WHERE grupo_id = :grupo_id ORDER BY nome";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":grupo_id", $grupo_id);
            $stmt->execute();
            $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'contas' => $contas
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
