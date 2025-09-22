# 🗑️ Limpeza Completa do Banco de Dados

## 🎯 **Objetivo:**
Limpar completamente o banco de dados e recriar apenas o usuário admin com ID 1, sem nenhum registro no sistema.

## ✅ **Scripts Criados:**

### 1. **limpar_banco_completo.php** (Execute este primeiro)
- Limpa todas as tabelas do banco
- Reseta auto_increment para 1
- Cria apenas os dados essenciais
- Configura sessão para usuário admin

### 2. **verificar_limpeza.php** (Execute após a limpeza)
- Verifica se a limpeza foi bem-sucedida
- Confirma que apenas os dados essenciais existem
- Valida a configuração do sistema

## 🚀 **Como Executar:**

### **Passo 1 - Limpeza:**
1. Execute `limpar_banco_completo.php`
2. Aguarde a conclusão da limpeza
3. Verifique as mensagens de sucesso

### **Passo 2 - Verificação:**
1. Execute `verificar_limpeza.php`
2. Confirme que tudo está correto
3. Se houver problemas, execute novamente o Passo 1

## 📊 **O que será criado:**

### **Usuário:**
- **ID:** 1
- **Username:** admin
- **Password:** 123456
- **Email:** admin@admin.com
- **Grupo:** 1
- **Role:** admin
- **Status:** Aprovado e Ativo

### **Grupo:**
- **ID:** 1
- **Nome:** Grupo Principal
- **Descrição:** Grupo padrão do sistema

### **Conta:**
- **ID:** 1
- **Nome:** Conta Corrente
- **Tipo:** corrente
- **Saldo:** R$ 0,00

### **Categorias:**
- **ID 1:** Receita (verde)
- **ID 2:** Despesa (vermelho)
- **ID 3:** Transferência (azul)

### **Tipos de Pagamento:**
- **ID 1:** Dinheiro
- **ID 2:** Cartão de Crédito
- **ID 3:** Cartão de Débito
- **ID 4:** PIX
- **ID 5:** Transferência

## ⚠️ **Atenção:**
- **TODOS os dados serão perdidos**
- **Não há como desfazer a limpeza**
- **Faça backup se necessário**
- **Execute apenas se tiver certeza**

## ✅ **Resultado Esperado:**
Após a execução, o sistema terá:
- 1 usuário (admin)
- 1 grupo (Grupo Principal)
- 1 conta (Conta Corrente)
- 3 categorias
- 5 tipos de pagamento
- 0 transações
- 0 outros registros

## 🔑 **Credenciais de Acesso:**
- **Username:** admin
- **Password:** 123456
- **ID:** 1

## 🎉 **Benefícios:**
1. **Banco Limpo:** Sem dados desnecessários
2. **ID 1 Garantido:** Usuário admin com ID 1
3. **Sistema Funcional:** Todos os dados essenciais criados
4. **Sessão Configurada:** Pronto para usar
5. **Sem Conflitos:** Nenhum registro duplicado

---
**Execute `limpar_banco_completo.php` para começar! 🚀**
