-- Script para adicionar campos de ícones ao banco existente
-- Execute este script no phpMyAdmin

USE controle_financeiro;

-- Adicionar campo icone na tabela categorias
ALTER TABLE categorias ADD COLUMN icone VARCHAR(50) DEFAULT 'fas fa-tag' AFTER cor;

-- Adicionar campo icone na tabela contas
ALTER TABLE contas ADD COLUMN icone VARCHAR(50) DEFAULT 'fas fa-university' AFTER saldo;

-- Atualizar categorias existentes com ícones padrão
UPDATE categorias SET icone = 'fas fa-shopping-cart' WHERE tipo = 'despesa' AND nome LIKE '%compra%';
UPDATE categorias SET icone = 'fas fa-utensils' WHERE tipo = 'despesa' AND nome LIKE '%aliment%';
UPDATE categorias SET icone = 'fas fa-car' WHERE tipo = 'despesa' AND nome LIKE '%transporte%';
UPDATE categorias SET icone = 'fas fa-home' WHERE tipo = 'despesa' AND nome LIKE '%casa%';
UPDATE categorias SET icone = 'fas fa-medkit' WHERE tipo = 'despesa' AND nome LIKE '%saúde%';
UPDATE categorias SET icone = 'fas fa-graduation-cap' WHERE tipo = 'despesa' AND nome LIKE '%educação%';
UPDATE categorias SET icone = 'fas fa-money-bill-wave' WHERE tipo = 'receita' AND nome LIKE '%salário%';
UPDATE categorias SET icone = 'fas fa-hand-holding-usd' WHERE tipo = 'receita' AND nome LIKE '%freelance%';
UPDATE categorias SET icone = 'fas fa-chart-line' WHERE tipo = 'receita' AND nome LIKE '%investimento%';

-- Atualizar contas existentes com ícones padrão
UPDATE contas SET icone = 'fas fa-university' WHERE nome LIKE '%banco%';
UPDATE contas SET icone = 'fas fa-credit-card' WHERE nome LIKE '%cartão%';
UPDATE contas SET icone = 'fas fa-piggy-bank' WHERE nome LIKE '%poupança%';
UPDATE contas SET icone = 'fas fa-wallet' WHERE nome LIKE '%dinheiro%';
UPDATE contas SET icone = 'fas fa-coins' WHERE nome LIKE '%investimento%';

-- Adicionar campo icone na tabela tipos_pagamento
ALTER TABLE tipos_pagamento ADD COLUMN icone VARCHAR(50) DEFAULT 'fas fa-credit-card' AFTER is_active;

-- Atualizar tipos de pagamento existentes com ícones padrão
UPDATE tipos_pagamento SET icone = 'fas fa-credit-card' WHERE nome LIKE '%cartão%';
UPDATE tipos_pagamento SET icone = 'fas fa-money-bill-wave' WHERE nome LIKE '%dinheiro%';
UPDATE tipos_pagamento SET icone = 'fas fa-university' WHERE nome LIKE '%transferência%';
UPDATE tipos_pagamento SET icone = 'fas fa-hand-holding-usd' WHERE nome LIKE '%pix%';
UPDATE tipos_pagamento SET icone = 'fas fa-receipt' WHERE nome LIKE '%boleto%';
UPDATE tipos_pagamento SET icone = 'fas fa-mobile-alt' WHERE nome LIKE '%celular%';
UPDATE tipos_pagamento SET icone = 'fas fa-desktop' WHERE nome LIKE '%internet%';
UPDATE tipos_pagamento SET icone = 'fas fa-exchange-alt' WHERE nome LIKE '%débito%';
