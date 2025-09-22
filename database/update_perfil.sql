-- Adicionar campos para perfil completo na tabela usuarios
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS endereco VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS estado VARCHAR(2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL;

-- Criar diretório de uploads se não existir
-- Nota: Este comando SQL não cria diretórios, apenas adiciona as colunas
-- O diretório deve ser criado manualmente no servidor
