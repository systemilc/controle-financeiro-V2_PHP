<?php
// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['username'] ?? '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-chart-line me-2"></i>
            <span class="d-none d-sm-inline">Controle Financeiro</span>
            <span class="d-sm-none">CF</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transacoes.php">
                            <i class="fas fa-exchange-alt me-1"></i>Transações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pendentes.php">
                            <i class="fas fa-clock me-1"></i>Pendentes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags me-1"></i>Categorias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contas.php">
                            <i class="fas fa-university me-1"></i>Contas Bancárias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tipos_pagamento.php">
                            <i class="fas fa-credit-card me-1"></i>Tipos de Pagamento
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar me-1"></i>Relatórios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notificacoes.php">
                            <i class="fas fa-bell me-1"></i>Notificações
                        </a>
                    </li>
                    
                    <?php if ($userRole === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Administração
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>Usuários
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="grupos.php">
                                <i class="fas fa-building me-2"></i>Grupos
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($userName) ?>
                            <?php if ($userRole === 'admin'): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="perfil.php">
                                <i class="fas fa-user-edit me-2"></i>Meu Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="configuracoes.php">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Cadastrar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
