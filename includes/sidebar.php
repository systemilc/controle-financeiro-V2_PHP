<?php
// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['username'] ?? '';
$grupoNome = $_SESSION['grupo_nome'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Botão do menu mobile -->
<button class="btn btn-primary d-md-none mobile-menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Desktop -->
<div class="col-md-3 col-lg-2 px-0 d-none d-md-block">
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
            <a class="nav-link <?= $currentPage === 'sugestoes_compra.php' ? 'active' : '' ?>" href="sugestoes_compra.php">
                <i class="fas fa-shopping-cart"></i>Sugestões de Compra
            </a>
            <a class="nav-link <?= $currentPage === 'compras.php' ? 'active' : '' ?>" href="compras.php">
                <i class="fas fa-shopping-cart"></i>Compras
            </a>
            <a class="nav-link <?= $currentPage === 'importar_planilha.php' ? 'active' : '' ?>" href="importar_planilha.php">
                <i class="fas fa-file-excel"></i>Importar Planilha
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

<!-- Sidebar Mobile (Offcanvas) -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel" style="background-color: #fff !important;">
    <div class="offcanvas-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important;">
        <h5 class="offcanvas-title" id="mobileSidebarLabel" style="color: white !important;">
            <i class="fas fa-wallet me-2"></i>
            Controle Financeiro
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="p-3 bg-gradient" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #dee2e6;">
            <div class="text-dark">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user text-primary me-2"></i>
                    <strong><?= htmlspecialchars($userName) ?></strong>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-users text-secondary me-2"></i>
                    <small class="text-muted"><?= htmlspecialchars($grupoNome) ?></small>
                </div>
            </div>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php" >
                <i class="fas fa-home"></i>Dashboard
            </a>
            <a class="nav-link <?= $currentPage === 'transacoes.php' ? 'active' : '' ?>" href="transacoes.php" >
                <i class="fas fa-exchange-alt"></i>Transações
            </a>
            <a class="nav-link <?= $currentPage === 'pendentes.php' ? 'active' : '' ?>" href="pendentes.php" >
                <i class="fas fa-clock"></i>Pendentes
            </a>
            <a class="nav-link <?= $currentPage === 'contas.php' ? 'active' : '' ?>" href="contas.php" >
                <i class="fas fa-university"></i>Contas
            </a>
            <a class="nav-link <?= $currentPage === 'transferencia.php' ? 'active' : '' ?>" href="transferencia.php" >
                <i class="fas fa-exchange-alt"></i>Transferências
            </a>
            <a class="nav-link <?= $currentPage === 'categorias.php' ? 'active' : '' ?>" href="categorias.php" >
                <i class="fas fa-tags"></i>Categorias
            </a>
            <a class="nav-link <?= $currentPage === 'tipos_pagamento.php' ? 'active' : '' ?>" href="tipos_pagamento.php" >
                <i class="fas fa-credit-card"></i>Tipos de Pagamento
            </a>
            <a class="nav-link <?= $currentPage === 'fornecedores.php' ? 'active' : '' ?>" href="fornecedores.php" >
                <i class="fas fa-truck"></i>Fornecedores
            </a>
            <a class="nav-link <?= $currentPage === 'produtos.php' ? 'active' : '' ?>" href="produtos.php" >
                <i class="fas fa-box"></i>Produtos
            </a>
            <a class="nav-link <?= $currentPage === 'sugestoes_compra.php' ? 'active' : '' ?>" href="sugestoes_compra.php" >
                <i class="fas fa-shopping-cart"></i>Sugestões de Compra
            </a>
            <a class="nav-link <?= $currentPage === 'compras.php' ? 'active' : '' ?>" href="compras.php" >
                <i class="fas fa-shopping-cart"></i>Compras
            </a>
            <a class="nav-link <?= $currentPage === 'importar_planilha.php' ? 'active' : '' ?>" href="importar_planilha.php" >
                <i class="fas fa-file-excel"></i>Importar Planilha
            </a>
            <a class="nav-link <?= $currentPage === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php" >
                <i class="fas fa-chart-bar"></i>Relatórios
            </a>
            <a class="nav-link <?= $currentPage === 'notificacoes.php' ? 'active' : '' ?>" href="notificacoes.php" >
                <i class="fas fa-bell"></i>Notificações
                <span id="notification-count-mobile" class="badge bg-danger ms-2" style="display: none;">0</span>
            </a>
            <a class="nav-link <?= $currentPage === 'convites.php' ? 'active' : '' ?>" href="convites.php" >
                <i class="fas fa-user-plus"></i>Convites
            </a>
            <a class="nav-link <?= $currentPage === 'usuarios_convidados.php' ? 'active' : '' ?>" href="usuarios_convidados.php" >
                <i class="fas fa-users-cog"></i>Usuários Convidados
            </a>
            
            <hr class="text-muted">
            <a class="nav-link <?= $currentPage === 'perfil.php' ? 'active' : '' ?>" href="perfil.php" >
                <i class="fas fa-user-edit"></i>Meu Perfil
            </a>
            <a class="nav-link <?= $currentPage === 'configuracoes.php' ? 'active' : '' ?>" href="configuracoes.php" >
                <i class="fas fa-cog"></i>Configurações
            </a>
            
            <?php if($userRole === 'admin'): ?>
            <hr class="text-muted">
            <a class="nav-link <?= $currentPage === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php" >
                <i class="fas fa-shield-alt"></i>Dashboard Admin
            </a>
            <a class="nav-link <?= $currentPage === 'usuarios.php' ? 'active' : '' ?>" href="usuarios.php" >
                <i class="fas fa-users"></i>Usuários
            </a>
            <a class="nav-link <?= $currentPage === 'grupos.php' ? 'active' : '' ?>" href="grupos.php" >
                <i class="fas fa-layer-group"></i>Grupos
            </a>
            <a class="nav-link <?= $currentPage === 'configuracoes_email.php' ? 'active' : '' ?>" href="configuracoes_email.php" >
                <i class="fas fa-envelope"></i>Configurações de Email
            </a>
            <?php endif; ?>
            
            <hr class="text-muted">
            <a class="nav-link" href="logout.php" >
                <i class="fas fa-sign-out-alt"></i>Sair
            </a>
        </nav>
    </div>
</div>
