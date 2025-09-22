# 🚀 Instalação Completa do Sistema de Controle Financeiro

## ⚠️ IMPORTANTE: Execute estes passos na ordem correta!

### Passo 1: Acessar o phpMyAdmin
1. **Abra o navegador** e acesse:
   ```
   http://localhost/phpmyadmin
   ```

### Passo 2: Executar o Script SQL Atualizado
1. **Clique na aba "SQL"** no topo da página
2. **Copie TODO o conteúdo** do arquivo `database/schema.sql` 
3. **Cole na caixa de texto** do phpMyAdmin
4. **Clique em "Executar"** (botão azul)

### Passo 3: Verificar se Funcionou
Após executar o script, você deve ver:

#### ✅ **Banco de Dados Criado:**
- `controle-financeiro` (no painel esquerdo)

#### ✅ **Tabelas Criadas:**
- `grupos`
- `usuarios` 
- `contas`
- `categorias`
- `tipos_pagamento`
- `fornecedores`
- `produtos`
- `associacoes_produtos`
- `compras`
- `itens_compra`
- `transacoes`
- `metas`

#### ✅ **Dados Padrão Inseridos:**
- **Grupo Principal** criado
- **Usuário admin** criado (senha: 123456)
- **3 contas** padrão (Conta Corrente, Poupança, Cartão de Crédito)
- **10 categorias** padrão
- **7 tipos de pagamento** padrão

### Passo 4: Acessar o Sistema
1. **Abra o navegador** e acesse:
   ```
   http://localhost/controle-financeiro/login.php
   ```

2. **Faça login** com:
   - **Usuário:** `admin`
   - **Senha:** `123456`

### Passo 5: Verificar Funcionalidades
Após o login, você deve ver:
- ✅ **Dashboard** com sidebar moderna
- ✅ **Menu lateral** com todas as opções
- ✅ **Cards de resumo** financeiro
- ✅ **Interface responsiva** e moderna

## 🔧 Solução de Problemas

### ❌ **Erro: "Table doesn't exist"**
**Causa:** Script SQL não foi executado
**Solução:** Execute o Passo 2 acima

### ❌ **Erro: "Access denied"**
**Causa:** Credenciais incorretas
**Solução:** Verifique o arquivo `config/database.php`

### ❌ **Página em branco**
**Causa:** Erro de PHP
**Solução:** 
1. Verifique se o XAMPP está rodando
2. Confirme se o MySQL está ativo
3. Execute o script SQL novamente

### ❌ **Erro de conexão**
**Causa:** Banco não existe
**Solução:** 
1. Crie o banco manualmente no phpMyAdmin
2. Execute o script SQL

## 📋 **Estrutura do Banco Criada:**

```sql
-- Principais tabelas:
grupos (id, nome, descricao)
usuarios (id, username, password, grupo_id, role, is_approved, ...)
contas (id, nome, grupo_id, saldo)
categorias (id, grupo_id, nome, tipo, cor)
tipos_pagamento (id, grupo_id, nome, is_income, is_expense, is_asset, is_active)
fornecedores (id, grupo_id, nome, cnpj, email, telefone, endereco)
produtos (id, grupo_id, nome, codigo, quantidade, valor_total, preco_medio, ...)
compras (id, grupo_id, fornecedor_id, numero_nota, valor_total, ...)
itens_compra (id, compra_id, produto_id, quantidade, preco_unitario, preco_total)
transacoes (id, usuario_id, conta_id, categoria_id, tipo_pagamento_id, ...)
metas (id, grupo_id, titulo, valor_meta, valor_atual, ...)
```

## 🎯 **Funcionalidades Disponíveis:**

### 👤 **Sistema de Usuários**
- Login/Logout seguro
- Cadastro com LGPD
- 3 tipos de usuário (admin, user, collaborator)
- Sistema de grupos isolados

### 🏦 **Contas Bancárias**
- Múltiplas contas por grupo
- Transferência entre contas
- Cálculo automático de saldos
- Interface moderna com cards

### 💳 **Tipos de Pagamento**
- Gestão completa com status ativo/inativo
- Aplicabilidade: Entrada, Saída, Ativo
- Filtro automático nas transações

### 🚚 **Fornecedores**
- Cadastro completo com CNPJ, contatos
- Máscaras automáticas
- Validação de unicidade

### 📦 **Produtos**
- Cadastro com nome e código
- Análise de preços históricos
- Estatísticas avançadas
- Histórico de compras

### 🎨 **Interface**
- Design moderno com Bootstrap 5
- Sidebar responsiva
- Cards com animações
- Modais interativos
- Layout totalmente responsivo

## ✅ **Após a Instalação:**

1. **Sistema funcionando** com todas as funcionalidades
2. **Interface moderna** e responsiva
3. **Banco de dados** completo e estruturado
4. **Usuário admin** criado e funcionando
5. **Dados padrão** inseridos automaticamente

---

**🎉 Seu sistema de controle financeiro está pronto para uso!**

**Desenvolvido com ❤️ em PHP + Bootstrap**
