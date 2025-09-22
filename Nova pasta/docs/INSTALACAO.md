# 🚀 Instalação do Sistema de Controle Financeiro

## ⚠️ IMPORTANTE: Execute estes passos antes de usar o sistema!

### Passo 1: Criar o Banco de Dados

1. **Abra o phpMyAdmin** no seu navegador:
   ```
   http://localhost/phpmyadmin
   ```

2. **Clique na aba "SQL"** no topo da página

3. **Copie e cole todo o conteúdo** do arquivo `database/schema.sql` na caixa de texto

4. **Clique em "Executar"** (botão azul)

### Passo 2: Verificar se Funcionou

1. **Verifique se o banco foi criado**:
   - No painel esquerdo, você deve ver `controle-financeiro`
   - Clique nele para expandir
   - Você deve ver as tabelas: `categorias`, `transacoes`, `metas`

2. **Verifique se as categorias foram inseridas**:
   - Clique na tabela `categorias`
   - Você deve ver 10 categorias padrão (Salário, Freelance, Alimentação, etc.)

### Passo 3: Acessar o Sistema

1. **Abra o navegador** e acesse:
   ```
   http://localhost/controle-financeiro
   ```

2. **Você deve ver o dashboard** com:
   - Cards de resumo (Receitas, Despesas, Saldo)
   - Botões de ação rápida
   - Área para transações recentes

## 🔧 Solução de Problemas

### Erro: "Table doesn't exist"
- **Causa**: Banco de dados não foi criado
- **Solução**: Execute o Passo 1 acima

### Erro: "Access denied"
- **Causa**: Credenciais incorretas
- **Solução**: Verifique o arquivo `config/database.php`

### Página em branco
- **Causa**: Erro de PHP
- **Solução**: Verifique os logs de erro do Apache

## 📞 Precisa de Ajuda?

Se ainda tiver problemas:
1. Verifique se o XAMPP está rodando
2. Confirme se o MySQL está ativo
3. Execute o script SQL novamente
4. Limpe o cache do navegador

---

**✅ Após executar estes passos, seu sistema estará funcionando perfeitamente!**
