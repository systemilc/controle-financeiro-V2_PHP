# 💰 Sistema de Controle Financeiro

Sistema completo de controle financeiro desenvolvido em PHP com MySQL, Bootstrap e JavaScript.

## 🚀 Funcionalidades

### 📊 Gestão Financeira
- ✅ Controle de receitas e despesas
- ✅ Gestão de contas bancárias
- ✅ Categorização de transações
- ✅ Tipos de pagamento personalizáveis
- ✅ Metas financeiras

### 🛒 Sistema de Compras
- ✅ Cadastro de compras
- ✅ Gestão de fornecedores
- ✅ Controle de produtos
- ✅ Importação de planilhas (Excel, CSV, TSV)
- ✅ Processamento de PDFs DANFE

### 👥 Gestão de Usuários
- ✅ Sistema de grupos
- ✅ Convites por email
- ✅ Controle de permissões
- ✅ Perfis de usuário

### 📈 Relatórios e Análises
- ✅ Dashboard com estatísticas
- ✅ Relatórios de compras
- ✅ Análise de produtos
- ✅ Histórico de transações

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL 5.7+
- **Frontend**: Bootstrap 5.1.3, JavaScript ES6
- **Bibliotecas**: PhpSpreadsheet, Smalot PDF Parser
- **Servidor**: Apache/Nginx

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache/Nginx
- Extensões PHP: PDO, GD, ZIP, MBString

## 🚀 Instalação

1. **Clone o repositório**
   ```bash
   git clone https://github.com/seu-usuario/controle-financeiro.git
   cd controle-financeiro
   ```

2. **Configure o banco de dados**
   - Crie um banco MySQL
   - Execute o arquivo `database/schema.sql`
   - Configure as credenciais em `config/database.php`

3. **Instale as dependências**
   ```bash
   composer install
   ```

4. **Configure as permissões**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/pdf/
   chmod 755 uploads/spreadsheets/
   ```

5. **Acesse o sistema**
   - URL: `http://localhost/controle-financeiro`
   - Usuário: `admin`
   - Senha: `password`

## 📁 Estrutura do Projeto

```
controle-financeiro/
├── assets/                 # CSS e JS customizados
├── classes/               # Classes PHP
├── config/                # Arquivos de configuração
├── database/              # Scripts SQL
├── docs/                  # Documentação
├── includes/              # Includes PHP
├── uploads/               # Arquivos enviados
├── vendor/                # Dependências Composer
└── *.php                  # Arquivos principais
```

## 🔧 Configuração

### Banco de Dados
Edite o arquivo `config/database.php`:
```php
$host = 'localhost';
$dbname = 'controle_financeiro';
$username = 'seu_usuario';
$password = 'sua_senha';
```

### Email (Opcional)
Configure o envio de emails em `config/email.php`:
```php
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'seu_email@gmail.com';
$smtp_password = 'sua_senha';
```

## 📊 Uso do Sistema

### 1. Primeiro Acesso
- Faça login com `admin` / `password`
- Configure suas contas bancárias
- Crie categorias personalizadas
- Convide outros usuários (opcional)

### 2. Gestão de Compras
- Cadastre fornecedores
- Registre compras manualmente
- Importe planilhas de compras
- Processe PDFs DANFE

### 3. Controle Financeiro
- Registre receitas e despesas
- Categorize transações
- Acompanhe metas
- Gere relatórios

## 🔒 Segurança

- Senhas criptografadas com bcrypt
- Validação de dados de entrada
- Proteção contra SQL Injection
- Controle de sessões
- Validação de permissões

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

## 🤝 Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📞 Suporte

Para suporte ou dúvidas, abra uma issue no GitHub.

---

**Desenvolvido com ❤️ em PHP**
