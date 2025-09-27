-- Tabela de sugestões de compra
CREATE TABLE IF NOT EXISTS sugestoes_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    grupo_id INT NOT NULL,
    quantidade_sugerida DECIMAL(10,2) NOT NULL,
    data_ultima_compra DATE,
    data_ultimo_consumo DATE NOT NULL,
    dias_consumo INT DEFAULT 0,
    consumo_diario_medio DECIMAL(10,4) DEFAULT 0,
    estoque_atual DECIMAL(10,2) DEFAULT 0,
    status ENUM('ativa', 'comprada', 'cancelada') DEFAULT 'ativa',
    prioridade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    INDEX idx_grupo_status (grupo_id, status),
    INDEX idx_prioridade (prioridade),
    INDEX idx_produto (produto_id)
);

-- Adicionar coluna de status de estoque na tabela produtos
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS estoque_zerado TINYINT(1) DEFAULT 0;
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_estoque_zerado TIMESTAMP NULL;
