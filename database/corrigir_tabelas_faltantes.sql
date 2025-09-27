-- Script para corrigir tabelas com colunas faltantes
-- Executar este script para adicionar colunas necessárias

-- Desabilitar verificação de chaves estrangeiras temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Corrigir tabela notificacoes
ALTER TABLE notificacoes 
ADD COLUMN IF NOT EXISTS grupo_id INT NOT NULL DEFAULT 1,
ADD COLUMN IF NOT EXISTS prioridade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media',
ADD COLUMN IF NOT EXISTS dados_extras JSON,
ADD COLUMN IF NOT EXISTS is_lida TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS data_leitura TIMESTAMP NULL;

-- Adicionar chave estrangeira para grupo_id em notificacoes
ALTER TABLE notificacoes 
ADD CONSTRAINT fk_notificacoes_grupo 
FOREIGN KEY (grupo_id) REFERENCES grupos(id);

-- 2. Corrigir tabela convites
ALTER TABLE convites 
ADD COLUMN IF NOT EXISTS convidado_por INT,
ADD COLUMN IF NOT EXISTS observacoes TEXT,
ADD COLUMN IF NOT EXISTS data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Adicionar chave estrangeira para convidado_por em convites
ALTER TABLE convites 
ADD CONSTRAINT fk_convites_convidado_por 
FOREIGN KEY (convidado_por) REFERENCES usuarios(id);

-- 3. Verificar e corrigir outras tabelas se necessário
-- Verificar se tabela usuarios_convidados tem as colunas necessárias
ALTER TABLE usuarios_convidados 
ADD COLUMN IF NOT EXISTS aceito_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Reabilitar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- Verificar tabelas corrigidas
SHOW TABLES;

