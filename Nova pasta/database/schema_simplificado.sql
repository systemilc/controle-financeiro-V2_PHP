-- Schema simplificado do Sistema de Controle Financeiro
-- Sem sistema de assinaturas e planos

-- Tabela de grupos (simplificada)
CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100),
    email VARCHAR(100),
    telefone VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    avatar VARCHAR(255),
    grupo_id INT NOT NULL,
    role ENUM('admin', 'user', 'collaborator') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    is_approved TINYINT(1) DEFAULT 0,
    whatsapp VARCHAR(20),
    instagram VARCHAR(100),
    consent_lgpd TINYINT(1) DEFAULT 0,
    tentativas_login INT DEFAULT 0,
    bloqueado_ate TIMESTAMP NULL,
    data_ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de contas bancárias
CREATE TABLE IF NOT EXISTS contas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    grupo_id INT NOT NULL,
    saldo DECIMAL(15,2) DEFAULT 0,
    icone VARCHAR(50) DEFAULT 'fas fa-university',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    cor VARCHAR(7) DEFAULT '#007bff',
    icone VARCHAR(50) DEFAULT 'fas fa-tag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_categoria_grupo (grupo_id, nome, tipo),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de tipos de pagamento
CREATE TABLE IF NOT EXISTS tipos_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    is_income TINYINT(1) DEFAULT 0,
    is_expense TINYINT(1) DEFAULT 0,
    is_asset TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    icone VARCHAR(50) DEFAULT 'fas fa-credit-card',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tipo_grupo (grupo_id, nome),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de fornecedores
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18),
    email VARCHAR(100),
    telefone VARCHAR(20),
    endereco TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fornecedor_grupo (grupo_id, nome),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(100),
    quantidade DECIMAL(10,2) DEFAULT 0,
    valor_total DECIMAL(15,2) DEFAULT 0,
    preco_medio DECIMAL(10,2) DEFAULT 0,
    data_ultima_compra DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_produto_grupo (grupo_id, nome, codigo),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de associações de produtos
CREATE TABLE IF NOT EXISTS associacoes_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    produto_associado_id INT NOT NULL,
    grupo_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_associado_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_associacao (produto_id, produto_associado_id)
);

-- Tabela de compras
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    fornecedor_id INT,
    numero_nota VARCHAR(50),
    valor_total DECIMAL(15,2) NOT NULL,
    data_compra DATE NOT NULL,
    quantidade_parcelas INT DEFAULT 1,
    data_primeira_parcela DATE,
    conta_id INT,
    tipo_pagamento_id INT,
    categoria_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_pagamento_id) REFERENCES tipos_pagamento(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabela de itens de compra
CREATE TABLE IF NOT EXISTS itens_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    preco_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- Tabela de transações (receitas e despesas)
CREATE TABLE IF NOT EXISTS transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    conta_id INT NOT NULL,
    categoria_id INT,
    tipo_pagamento_id INT,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    is_confirmed TINYINT(1) DEFAULT 0,
    data_transacao DATE NOT NULL,
    data_vencimento DATE,
    data_confirmacao DATE,
    observacoes TEXT,
    is_transfer TINYINT(1) DEFAULT 0,
    conta_original_nome VARCHAR(100),
    multiplicador INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_pagamento_id) REFERENCES tipos_pagamento(id) ON DELETE SET NULL
);

-- Tabela de metas financeiras
CREATE TABLE IF NOT EXISTS metas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    valor_meta DECIMAL(15,2) NOT NULL,
    valor_atual DECIMAL(15,2) DEFAULT 0,
    data_limite DATE,
    descricao TEXT,
    status ENUM('ativa', 'concluida', 'cancelada') DEFAULT 'ativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
);

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    usuario_id INT,
    tipo ENUM('vencimento_proximo', 'vencimento_atrasado', 'pagamento_confirmado', 'saldo_baixo', 'meta_atingida', 'transferencia_realizada', 'conta_criada', 'categoria_criada', 'sistema') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    dados_extras JSON,
    is_lida TINYINT(1) DEFAULT 0,
    prioridade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media',
    data_notificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_leitura TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Inserir dados padrão
INSERT INTO grupos (nome, descricao) VALUES
('Grupo Principal', 'Grupo padrão do sistema');

INSERT INTO usuarios (username, password, nome, email, grupo_id, role, is_approved, whatsapp, consent_lgpd) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@controle.com', 1, 'admin', 1, '(73) 99999-9999', 1);

INSERT INTO contas (nome, grupo_id) VALUES
('Conta Corrente', 1),
('Poupança', 1),
('Cartão de Crédito', 1);

INSERT INTO categorias (grupo_id, nome, tipo, cor) VALUES
(1, 'Salário', 'receita', '#28a745'),
(1, 'Freelance', 'receita', '#17a2b8'),
(1, 'Investimentos', 'receita', '#6f42c1'),
(1, 'Alimentação', 'despesa', '#dc3545'),
(1, 'Transporte', 'despesa', '#fd7e14'),
(1, 'Moradia', 'despesa', '#6c757d'),
(1, 'Saúde', 'despesa', '#e83e8c'),
(1, 'Educação', 'despesa', '#20c997'),
(1, 'Lazer', 'despesa', '#ffc107'),
(1, 'Outros', 'despesa', '#6c757d');

INSERT INTO tipos_pagamento (grupo_id, nome, is_income, is_expense, is_asset, is_active) VALUES
(1, 'Dinheiro', 1, 1, 0, 1),
(1, 'Cartão de Débito', 1, 1, 0, 1),
(1, 'Cartão de Crédito', 1, 1, 0, 1),
(1, 'PIX', 1, 1, 0, 1),
(1, 'Transferência Bancária', 1, 1, 0, 1),
(1, 'Boleto', 0, 1, 0, 1),
(1, 'Cheque', 1, 1, 0, 1);
