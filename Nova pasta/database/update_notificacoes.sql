-- Script para adicionar tabela de notificações ao banco existente
-- Execute este script no phpMyAdmin

USE controle_financeiro;

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

-- Inserir algumas notificações de exemplo (opcional)
INSERT INTO notificacoes (grupo_id, tipo, titulo, mensagem, prioridade) VALUES
(1, 'sistema', 'Sistema de Notificações Ativado', 'O sistema de notificações foi ativado com sucesso! Agora você receberá alertas sobre vencimentos, pagamentos e outros eventos importantes.', 'media'),
(1, 'sistema', 'Bem-vindo ao Sistema', 'Bem-vindo ao sistema de controle financeiro! Configure suas contas e categorias para começar a usar todas as funcionalidades.', 'baixa');
