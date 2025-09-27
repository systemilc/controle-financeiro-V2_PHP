# 💰 Sistema de Controle Financeiro v2.2.0

Sistema completo de controle financeiro desenvolvido em PHP com MySQL, Bootstrap e JavaScript.

[![Version](https://img.shields.io/badge/version-2.2.0-blue.svg)](https://github.com/seu-usuario/controle-financeiro)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 🚀 Funcionalidades

### 📊 Gestão Financeira Avançada
- ✅ Controle completo de receitas e despesas
- ✅ Gestão de contas bancárias com saldo automático
- ✅ Categorização inteligente de transações
- ✅ Tipos de pagamento personalizáveis com ícones
- ✅ Metas financeiras e acompanhamento
- ✅ **NOVO**: Visão geral completa do financeiro
- ✅ **NOVO**: Totais gerais, mês atual e período selecionado
- ✅ **NOVO**: Saldo em tempo real por conta

### 🛒 Sistema de Compras Inteligente
- ✅ Cadastro completo de compras
- ✅ Gestão avançada de fornecedores
- ✅ Controle detalhado de produtos
- ✅ **NOVO**: Vinculação inteligente de produtos na importação
- ✅ **NOVO**: Modal customizado para comparação de produtos
- ✅ **NOVO**: Histórico de preços por fornecedor
- ✅ **NOVO**: Identificação do fornecedor mais barato
- ✅ Importação de planilhas (Excel, CSV, TSV)
- ✅ Processamento automático de PDFs DANFE
- ✅ **NOVO**: Confirmação automática de transações
- ✅ **NOVO**: Sistema de Sugestões de Compra Inteligente
- ✅ **NOVO**: Cálculo automático de consumo baseado em histórico
- ✅ **NOVO**: Priorização de sugestões (crítica, alta, média, baixa)
- ✅ **NOVO**: Remoção automática de sugestões ao importar planilhas

### 👥 Gestão de Usuários e Grupos
- ✅ Sistema de grupos isolados
- ✅ Convites por email com validação
- ✅ Controle granular de permissões
- ✅ Perfis de usuário personalizáveis
- ✅ **NOVO**: Usuários convidados com controle de acesso

### 📈 Relatórios e Análises Avançadas
- ✅ Dashboard interativo com estatísticas em tempo real
- ✅ **NOVO**: Relatórios mostram todas as transações por padrão
- ✅ **NOVO**: Filtros inteligentes com instruções claras
- ✅ **NOVO**: Gráfico de tipos de pagamento
- ✅ **NOVO**: Análise de produtos mais comprados
- ✅ **NOVO**: Histórico detalhado de compras por produto
- ✅ **NOVO**: Evolução mensal de receitas e despesas
- ✅ **NOVO**: Títulos dinâmicos baseados nos filtros
- ✅ Exportação de relatórios em múltiplos formatos

### 🔧 Funcionalidades Técnicas
- ✅ **NOVO**: Sistema de fila de emails com processamento assíncrono
- ✅ **NOVO**: Processamento de planilhas com validação avançada
- ✅ **NOVO**: Sistema de notificações em tempo real
- ✅ **NOVO**: Logs detalhados para debugging
- ✅ **NOVO**: Interface responsiva otimizada para mobile
- ✅ **NOVO**: Sistema anti-flickering para modais
- ✅ **NOVO**: Banco de dados otimizado com todas as colunas necessárias
- ✅ **NOVO**: Sistema robusto de tratamento de erros
- ✅ **NOVO**: Validação automática de integridade dos dados

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

### 2. Gestão de Compras Inteligente
- Cadastre fornecedores
- **Importe planilhas** com vinculação automática de produtos
- **Compare produtos** existentes durante a importação
- **Identifique fornecedores** com melhores preços
- Processe PDFs DANFE automaticamente
- **Use o Sistema de Sugestões**: Marque produtos como "acabaram" para gerar sugestões automáticas
- **Gerencie Sugestões**: Acesse a página dedicada para ver e comprar produtos sugeridos

### 3. Controle Financeiro Avançado
- Registre receitas e despesas
- **Visualize visão geral** completa do financeiro
- **Acompanhe totais** gerais e do mês atual
- **Filtre relatórios** por período, conta ou categoria
- Gere relatórios detalhados com gráficos

### 4. Análise de Produtos
- **Visualize produtos mais comprados**
- **Compare preços** entre fornecedores
- **Acompanhe histórico** de compras
- **Identifique oportunidades** de economia

### 5. Sistema de Sugestões Inteligente
- **Marque produtos como acabaram** para gerar sugestões automáticas
- **Visualize sugestões priorizadas** por criticidade
- **Registre compras diretamente** das sugestões
- **Acompanhe histórico** de sugestões compradas
- **Remoção automática** ao importar planilhas

#### 🧠 Como Funciona o Sistema de Sugestões
1. **Marcação de Produto Acabado**: Clique em "Produto Acabou" na página de produtos
2. **Cálculo Automático**: Sistema analisa histórico de compras e consumo
3. **Geração de Sugestão**: Cria sugestão com quantidade calculada automaticamente
4. **Priorização**: Classifica por criticidade baseada no tempo de consumo
5. **Compra Integrada**: Registre compras diretamente das sugestões
6. **Remoção Automática**: Sugestões são removidas ao importar planilhas

#### 📊 Tipos de Prioridade
- 🔴 **Crítica**: Produto acabou há mais de 7 dias
- 🟠 **Alta**: Produto acabou há 3-7 dias
- 🟡 **Média**: Produto acabou há 1-3 dias
- 🟢 **Baixa**: Produto acabou há menos de 1 dia

## 📋 Changelog

### v2.2.0 (2025-09-26) - "Sistema de Sugestões Inteligente"
#### 🆕 Novas Funcionalidades
- **Sistema de Sugestões de Compra**: Lista inteligente de produtos que acabaram
- **Cálculo Automático de Consumo**: Algoritmo que calcula consumo baseado no histórico
- **Priorização Inteligente**: Sugestões classificadas por criticidade (crítica, alta, média, baixa)
- **Modal de Compra Integrado**: Registro de compras diretamente das sugestões
- **Remoção Automática**: Sugestões removidas automaticamente ao importar planilhas
- **Página de Sugestões Dedicada**: Interface completa para gerenciar sugestões
- **Sistema de Notificações Aprimorado**: Notificações inteligentes com fallback para admin
- **Banco de Dados Otimizado**: Todas as colunas necessárias adicionadas

#### 🔧 Melhorias
- **Interface de Produtos**: Cards de produtos redesenhados com melhor UX
- **Sistema de Convites**: Correção completa do fluxo de aceite de convites
- **Validação de Dados**: Verificação automática de integridade dos dados
- **Tratamento de Erros**: Sistema robusto de tratamento de exceções
- **Performance**: Otimizações gerais de performance

#### 🐛 Correções Críticas
- **Colunas Faltantes**: Corrigidas 8+ colunas faltantes no banco de dados
  - `is_confirmed`, `data_vencimento`, `data_confirmacao` em `transacoes`
  - `saldo`, `icone` em `contas`
  - `grupo_id`, `prioridade`, `dados_extras` em `notificacoes`
  - `convidado_por`, `observacoes`, `data_envio`, `data_expiracao`, `status`, `role`, `data_resposta` em `convites`
- **Consultas SQL**: Todas as consultas SQL corrigidas e otimizadas
- **Sistema de Convites**: Fluxo completo de convites funcionando
- **Notificações**: Sistema de notificações totalmente funcional
- **Importação**: Processo de importação de planilhas robusto
- **Interface**: Todos os problemas de interface corrigidos
- **Tratamento de Erros**: Sistema robusto para colunas inexistentes
- **Validação de Dados**: Verificação automática de integridade

### v2.1.0 (2024-12-19) - "Inteligência Financeira"
#### 🆕 Novas Funcionalidades
- **Vinculação Inteligente de Produtos**: Sistema que permite vincular produtos durante importação de planilhas
- **Modal Customizado**: Interface para comparação e seleção de produtos existentes
- **Fornecedor Mais Barato**: Identificação automática do fornecedor com melhor preço
- **Visão Geral Completa**: Dashboard com totais gerais, mês atual e período selecionado
- **Relatórios Inteligentes**: Mostram todas as transações por padrão com filtros avançados
- **Gráfico de Tipos de Pagamento**: Nova visualização de dados financeiros

#### 🔧 Melhorias
- **Interface Aprimorada**: Filtros com instruções claras e títulos dinâmicos
- **Sistema Anti-flickering**: Modais estáveis sem problemas de renderização
- **Logs Detalhados**: Sistema de debugging aprimorado
- **Validação Avançada**: Melhor processamento de planilhas e dados

#### 🐛 Correções
- **Alimentação de Estoque**: Corrigido problema de produtos não sendo vinculados
- **Histórico de Produtos**: Valores corretos exibidos no histórico de compras
- **Gráficos de Relatórios**: Despesas exibidas corretamente nos gráficos
- **Erros de Banco**: Corrigidas referências a colunas inexistentes
- **Cláusulas SQL**: Resolvidos problemas com aliases em funções agregadas

### v2.0.0 (2024-12-15) - "Sistema Completo"
- Sistema base de controle financeiro
- Gestão de usuários e grupos
- Importação de planilhas básica
- Relatórios fundamentais
- Dashboard inicial

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

## 🗺️ Roadmap

### v2.2.0 - "Automação Inteligente" (Q1 2025)
- 🤖 **IA para Categorização**: Classificação automática de transações
- 📱 **App Mobile**: Aplicativo nativo para iOS/Android
- 🔄 **Sincronização**: Backup automático na nuvem
- 📊 **Dashboards Personalizáveis**: Widgets configuráveis pelo usuário

### v2.3.0 - "Integração Avançada" (Q2 2025)
- 🏦 **Open Banking**: Integração com bancos brasileiros
- 📧 **Email Inteligente**: Processamento automático de extratos por email
- 🤝 **API REST**: Integração com sistemas externos
- 📈 **Machine Learning**: Previsões financeiras baseadas em IA

## 🎯 Destaques da Versão Atual

### 🏆 Funcionalidades Únicas
- **Sistema de Sugestões Inteligente**: Único sistema que calcula automaticamente quando e quanto comprar
- **Vinculação Inteligente**: Único sistema que permite vincular produtos automaticamente durante importação
- **Fornecedor Mais Barato**: Identificação automática do melhor preço histórico
- **Visão 360°**: Dashboard completo com totais gerais, mês atual e período selecionado
- **Relatórios Inteligentes**: Mostram todas as transações por padrão com filtros avançados
- **Cálculo de Consumo Automático**: Algoritmo que aprende com o histórico de compras

### 🚀 Performance
- **Processamento Rápido**: Importação de planilhas em segundos
- **Interface Fluida**: Modais sem flickering e navegação suave
- **Responsivo**: Funciona perfeitamente em desktop, tablet e mobile
- **Escalável**: Suporta milhares de transações sem perda de performance

### 💡 Benefícios do Sistema de Sugestões
- **Economia de Tempo**: Nunca mais esqueça de comprar produtos essenciais
- **Otimização de Estoque**: Compre a quantidade certa baseada no consumo real
- **Redução de Desperdício**: Evite compras excessivas ou insuficientes
- **Inteligência Artificial**: Sistema aprende com seus padrões de consumo
- **Automação Completa**: Sugestões são removidas automaticamente ao comprar
- **Priorização Inteligente**: Foque nos produtos mais críticos primeiro

## 📞 Suporte

- 🐛 **Bugs**: Abra uma issue no GitHub
- 💡 **Sugestões**: Use a aba "Discussions"
- 📧 **Email**: suporte@controlefinanceiro.com
- 📚 **Documentação**: [Wiki do Projeto](https://github.com/seu-usuario/controle-financeiro/wiki)

## 🤝 Contribuição

Contribuições são sempre bem-vindas! Veja nosso [Guia de Contribuição](CONTRIBUTING.md) para mais detalhes.

### Como Contribuir
1. 🍴 Faça um fork do projeto
2. 🌿 Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. 💾 Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. 📤 Push para a branch (`git push origin feature/AmazingFeature`)
5. 🔄 Abra um Pull Request

---

<div align="center">

**💰 Sistema de Controle Financeiro v2.2.0**

*Desenvolvido com ❤️ em PHP para empresas que buscam controle financeiro inteligente*

[![GitHub stars](https://img.shields.io/github/stars/seu-usuario/controle-financeiro?style=social)](https://github.com/seu-usuario/controle-financeiro)
[![GitHub forks](https://img.shields.io/github/forks/seu-usuario/controle-financeiro?style=social)](https://github.com/seu-usuario/controle-financeiro)
[![GitHub issues](https://img.shields.io/github/issues/seu-usuario/controle-financeiro)](https://github.com/seu-usuario/controle-financeiro/issues)

</div>
