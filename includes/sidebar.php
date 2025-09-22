<?php
// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['username'] ?? '';
$grupoNome = $_SESSION['grupo_nome'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3 col-lg-2 px-0">
    <div class="sidebar">
        <div class="p-3">
            <h4 class="text-white mb-4">
                <i class="fas fa-wallet me-2"></i>
                Controle Financeiro
            </h4>
            <div class="text-white-50 mb-4">
                <small>
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($userName) ?>
                    <br>
                    <i class="fas fa-users me-1"></i>
                    <?= htmlspecialchars($grupoNome) ?>
                </small>
            </div>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                <i class="fas fa-home"></i>Dashboard
            </a>
            <a class="nav-link <?= $currentPage === 'transacoes.php' ? 'active' : '' ?>" href="transacoes.php">
                <i class="fas fa-exchange-alt"></i>Transações
            </a>
            <a class="nav-link <?= $currentPage === 'pendentes.php' ? 'active' : '' ?>" href="pendentes.php">
                <i class="fas fa-clock"></i>Pendentes
            </a>
            <a class="nav-link <?= $currentPage === 'contas.php' ? 'active' : '' ?>" href="contas.php">
                <i class="fas fa-university"></i>Contas
            </a>
            <a class="nav-link <?= $currentPage === 'transferencia.php' ? 'active' : '' ?>" href="transferencia.php">
                <i class="fas fa-exchange-alt"></i>Transferências
            </a>
            <a class="nav-link <?= $currentPage === 'categorias.php' ? 'active' : '' ?>" href="categorias.php">
                <i class="fas fa-tags"></i>Categorias
            </a>
            <a class="nav-link <?= $currentPage === 'tipos_pagamento.php' ? 'active' : '' ?>" href="tipos_pagamento.php">
                <i class="fas fa-credit-card"></i>Tipos de Pagamento
            </a>
            <a class="nav-link <?= $currentPage === 'fornecedores.php' ? 'active' : '' ?>" href="fornecedores.php">
                <i class="fas fa-truck"></i>Fornecedores
            </a>
            <a class="nav-link <?= $currentPage === 'produtos.php' ? 'active' : '' ?>" href="produtos.php">
                <i class="fas fa-box"></i>Produtos
            </a>
            <a class="nav-link <?= $currentPage === 'compras.php' ? 'active' : '' ?>" href="compras.php">
                <i class="fas fa-shopping-cart"></i>Compras
            </a>
            <a class="nav-link <?= $currentPage === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php">
                <i class="fas fa-chart-bar"></i>Relatórios
            </a>
            <a class="nav-link <?= $currentPage === 'notificacoes.php' ? 'active' : '' ?>" href="notificacoes.php">
                <i class="fas fa-bell"></i>Notificações
                <span id="notification-count" class="badge bg-danger ms-2" style="display: none;">0</span>
            </a>
            <a class="nav-link <?= $currentPage === 'convites.php' ? 'active' : '' ?>" href="convites.php">
                <i class="fas fa-user-plus"></i>Convites
            </a>
            <a class="nav-link <?= $currentPage === 'usuarios_convidados.php' ? 'active' : '' ?>" href="usuarios_convidados.php">
                <i class="fas fa-users-cog"></i>Usuários Convidados
            </a>
            
            <hr class="text-white-50">
            <a class="nav-link <?= $currentPage === 'perfil.php' ? 'active' : '' ?>" href="perfil.php">
                <i class="fas fa-user-edit"></i>Meu Perfil
            </a>
            <a class="nav-link <?= $currentPage === 'configuracoes.php' ? 'active' : '' ?>" href="configuracoes.php">
                <i class="fas fa-cog"></i>Configurações
            </a>
            
            <?php if($userRole === 'admin'): ?>
            <hr class="text-white-50">
            <a class="nav-link <?= $currentPage === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
                <i class="fas fa-shield-alt"></i>Dashboard Admin
            </a>
            <a class="nav-link <?= $currentPage === 'usuarios.php' ? 'active' : '' ?>" href="usuarios.php">
                <i class="fas fa-users"></i>Usuários
            </a>
            <a class="nav-link <?= $currentPage === 'grupos.php' ? 'active' : '' ?>" href="grupos.php">
                <i class="fas fa-layer-group"></i>Grupos
            </a>
            <a class="nav-link <?= $currentPage === 'configuracoes_email.php' ? 'active' : '' ?>" href="configuracoes_email.php">
                <i class="fas fa-envelope"></i>Configurações de Email
            </a>
            <?php endif; ?>
            
            <hr class="text-white-50">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i>Sair
            </a>
        </nav>
    </div>
</div>
