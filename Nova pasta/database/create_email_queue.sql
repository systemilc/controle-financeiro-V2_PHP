-- Tabela para fila de emails
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    para VARCHAR(255) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem LONGTEXT NOT NULL,
    eh_html BOOLEAN DEFAULT TRUE,
    prioridade INT DEFAULT 1,
    status ENUM('pendente', 'processando', 'enviado', 'falha') DEFAULT 'pendente',
    tentativas INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_processamento TIMESTAMP NULL,
    data_envio TIMESTAMP NULL,
    data_falha TIMESTAMP NULL,
    erro TEXT NULL,
    INDEX idx_status (status),
    INDEX idx_prioridade (prioridade),
    INDEX idx_data_criacao (data_criacao)
);
