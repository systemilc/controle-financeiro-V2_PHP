-- Tabela para armazenar DANFEs processadas
CREATE TABLE IF NOT EXISTS pdf_processed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    chave_acesso VARCHAR(44),
    cnpj VARCHAR(18),
    razao_social VARCHAR(255),
    data_emissao DATE,
    valor_total DECIMAL(10,2),
    status ENUM('processado', 'erro') DEFAULT 'processado',
    dados_json TEXT,
    grupo_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    INDEX idx_chave_acesso (chave_acesso),
    INDEX idx_cnpj (cnpj),
    INDEX idx_grupo_id (grupo_id),
    INDEX idx_created_at (created_at)
);
