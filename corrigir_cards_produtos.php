<?php
// Arquivo para corrigir problemas nos cards dos produtos
require_once 'config/database.php';
require_once 'classes/Produto.php';

$database = new Database();
$db = $database->getConnection();

$produto = new Produto($db);

// Obter produtos
$stmt_produtos = $produto->read(1, '', '');
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #17a2b8;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-badge {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
            border-radius: 20px;
            padding: 6px 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .price-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .product-code {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .price-value {
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .price-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 2px;
        }
        
        .last-purchase {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .dropdown-toggle::after {
            display: none;
        }
        
        .btn-outline-secondary {
            border: 1px solid #dee2e6;
            color: #6c757d;
        }
        
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }
        
        /* Responsividade melhorada */
        @media (max-width: 768px) {
            .product-card {
                margin-bottom: 15px;
            }
            
            .price-info .row > div {
                margin-bottom: 10px;
            }
        }
        
        /* Animações suaves */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="main-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-box me-2 text-primary"></i>
                                Produtos
                            </h2>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" onclick="criarProduto()">
                                    <i class="fas fa-plus me-1"></i>Novo Produto
                                </button>
                                <a href="analise_produtos.php" class="btn btn-outline-info">
                                    <i class="fas fa-chart-line me-1"></i>Análise
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="container-fluid p-4">
                        <!-- Lista de Produtos -->
                        <div class="row">
                            <?php if(empty($produtos)): ?>
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nenhum produto encontrado</h5>
                                            <p class="text-muted">Comece criando seus primeiros produtos.</p>
                                            <button type="button" class="btn btn-primary" onclick="criarProduto()">
                                                <i class="fas fa-plus me-1"></i>Criar Primeiro Produto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($produtos as $index => $produto_item): ?>
                                    <div class="col-md-6 col-lg-4 mb-3 fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                                        <div class="product-card">
                                            <!-- Cabeçalho do Card -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h5 class="product-title"><?= htmlspecialchars($produto_item['nome']) ?></h5>
                                                    <?php if($produto_item['codigo']): ?>
                                                        <div class="product-code">
                                                            <i class="fas fa-barcode me-1"></i>
                                                            Código: <?= htmlspecialchars($produto_item['codigo']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="editarProduto(<?= htmlspecialchars(json_encode($produto_item)) ?>)">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item" onclick="verHistorico(<?= $produto_item['id'] ?>)">
                                                                <i class="fas fa-history me-2"></i>Ver Histórico
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-warning" onclick="marcarProdutoAcabado(<?= $produto_item['id'] ?>)">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Produto Acabou
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="excluirProduto(<?= $produto_item['id'] ?>)">
                                                                <i class="fas fa-trash me-2"></i>Excluir
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <!-- Informações de Preço (se houver dados) -->
                                            <?php if($produto_item['quantidade'] > 0 || $produto_item['valor_total'] > 0): ?>
                                                <div class="price-info">
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <div class="price-value text-primary"><?= number_format($produto_item['quantidade'], 0, ',', '.') ?></div>
                                                            <div class="price-label">Qtd Total</div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="price-value text-success">R$ <?= number_format($produto_item['valor_total'], 2, ',', '.') ?></div>
                                                            <div class="price-label">Valor Total</div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="price-value text-info">R$ <?= number_format($produto_item['preco_medio'], 2, ',', '.') ?></div>
                                                            <div class="price-label">Preço Médio</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Rodapé do Card -->
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div class="last-purchase">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php if($produto_item['data_ultima_compra']): ?>
                                                        Última compra: <?= date('d/m/Y', strtotime($produto_item['data_ultima_compra'])) ?>
                                                    <?php else: ?>
                                                        Nunca comprado
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="stats-badge">
                                                        <i class="fas fa-chart-line me-1"></i>
                                                        Análise
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funções para os cards
        function criarProduto() {
            alert('Função de criar produto - implementar modal');
        }
        
        function editarProduto(produto) {
            alert('Editar produto: ' + produto.nome);
        }
        
        function verHistorico(produtoId) {
            alert('Ver histórico do produto ID: ' + produtoId);
        }
        
        function marcarProdutoAcabado(produtoId) {
            if (confirm('Deseja marcar este produto como acabado? Isso gerará uma sugestão de compra baseada no consumo histórico.')) {
                // Mostrar loading
                const loadingToast = document.createElement('div');
                loadingToast.className = 'toast position-fixed top-0 end-0 m-3';
                loadingToast.innerHTML = `
                    <div class="toast-body">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Gerando sugestão de compra...
                    </div>
                `;
                document.body.appendChild(loadingToast);
                const toast = new bootstrap.Toast(loadingToast);
                toast.show();

                // Fazer requisição AJAX
                fetch('ajax_sugestoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=marcar_produto_acabado&produto_id=${produtoId}`
                })
                .then(response => response.json())
                .then(data => {
                    // Remover loading
                    toast.hide();
                    loadingToast.remove();

                    if (data.success) {
                        // Mostrar sucesso
                        const successToast = document.createElement('div');
                        successToast.className = 'toast position-fixed top-0 end-0 m-3';
                        successToast.innerHTML = `
                            <div class="toast-body bg-success text-white">
                                <i class="fas fa-check-circle me-2"></i>
                                ${data.message}
                            </div>
                        `;
                        document.body.appendChild(successToast);
                        const successToastInstance = new bootstrap.Toast(successToast);
                        successToastInstance.show();

                        // Redirecionar para sugestões após 2 segundos
                        setTimeout(() => {
                            window.location.href = 'sugestoes_compra.php';
                        }, 2000);
                    } else {
                        // Mostrar erro
                        const errorToast = document.createElement('div');
                        errorToast.className = 'toast position-fixed top-0 end-0 m-3';
                        errorToast.innerHTML = `
                            <div class="toast-body bg-danger text-white">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${data.message}
                            </div>
                        `;
                        document.body.appendChild(errorToast);
                        const errorToastInstance = new bootstrap.Toast(errorToast);
                        errorToastInstance.show();
                    }
                })
                .catch(error => {
                    // Remover loading
                    toast.hide();
                    loadingToast.remove();

                    // Mostrar erro
                    const errorToast = document.createElement('div');
                    errorToast.className = 'toast position-fixed top-0 end-0 m-3';
                    errorToast.innerHTML = `
                        <div class="toast-body bg-danger text-white">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erro ao processar solicitação
                        </div>
                    `;
                    document.body.appendChild(errorToast);
                    const errorToastInstance = new bootstrap.Toast(errorToast);
                    errorToastInstance.show();
                });
            }
        }
        
        function excluirProduto(produtoId) {
            if (confirm('Tem certeza que deseja excluir este produto?')) {
                alert('Excluir produto ID: ' + produtoId);
            }
        }
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Cards dos produtos carregados com sucesso');
            console.log('Total de produtos:', <?= count($produtos) ?>);
        });
    </script>
</body>
</html>
