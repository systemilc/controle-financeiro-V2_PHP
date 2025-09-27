# 🛒 Sistema de Sugestões de Compra Inteligente

## 📋 Visão Geral

O Sistema de Sugestões de Compra é uma funcionalidade inteligente que analisa o padrão de consumo dos produtos e sugere automaticamente quantidades ideais para compra quando um produto acaba.

## 🎯 Como Funciona

### 1. **Marcação de Produto Acabado**
- Na página de **Produtos**, cada produto possui um botão "Produto Acabou"
- Ao clicar, o sistema:
  - Marca o produto como acabado
  - Analisa o histórico de compras dos últimos 6 meses
  - Calcula o consumo diário médio
  - Gera uma sugestão de compra inteligente

### 2. **Cálculo Inteligente**
O sistema calcula:
- **Consumo Diário Médio**: Baseado nas compras dos últimos 6 meses
- **Quantidade Sugerida**: Para 30 dias de consumo
- **Prioridade**: Baseada no volume de consumo
  - 🔴 **Crítica**: Consumo ≥ 1 unidade/dia
  - 🟠 **Alta**: Consumo ≥ 0.5 unidade/dia
  - 🟡 **Média**: Consumo ≥ 0.1 unidade/dia
  - 🟢 **Baixa**: Consumo < 0.1 unidade/dia

### 3. **Exemplo Prático**
```
Produto: Arroz 5kg
Última compra: 01/09/2025 (1 saco)
Marcado como acabado: 15/09/2025
Dias de consumo: 14 dias
Consumo diário: 1/14 = 0.071 sacos/dia
Quantidade sugerida: 0.071 × 30 = 2.13 → 3 sacos
Prioridade: Baixa (consumo < 0.1)
```

## 🚀 Funcionalidades

### **Página de Sugestões** (`sugestoes_compra.php`)
- **Dashboard com estatísticas**:
  - Total de sugestões
  - Sugestões ativas
  - Prioridade alta/crítica
  - Sugestões compradas

- **Filtros avançados**:
  - Por status (ativa, comprada, cancelada)
  - Por prioridade (crítica, alta, média, baixa)
  - Por nome/código do produto

- **Gestão de sugestões**:
  - Editar quantidade sugerida
  - Alterar prioridade
  - Marcar como comprada
  - Cancelar sugestão
  - Adicionar observações

### **Integração com Produtos**
- Botão "Produto Acabou" em cada produto
- Confirmação antes de gerar sugestão
- Feedback visual com toasts
- Redirecionamento automático para sugestões

## 📊 Estrutura do Banco de Dados

### **Tabela: `sugestoes_compra`**
```sql
- id: ID único
- produto_id: Referência ao produto
- grupo_id: Isolamento por grupo
- quantidade_sugerida: Quantidade calculada
- data_ultima_compra: Data da última compra
- data_ultimo_consumo: Data que acabou
- dias_consumo: Dias que durou
- consumo_diario_medio: Consumo calculado
- estoque_atual: Estoque atual (0 = acabou)
- status: ativa, comprada, cancelada
- prioridade: baixa, media, alta, critica
- observacoes: Notas adicionais
```

### **Colunas Adicionadas em `produtos`**
```sql
- estoque_zerado: TINYINT(1) - Se o produto acabou
- data_estoque_zerado: TIMESTAMP - Quando acabou
```

## 🔧 Arquivos Criados/Modificados

### **Novos Arquivos**
- `classes/SugestaoCompra.php` - Classe principal
- `sugestoes_compra.php` - Página principal
- `ajax_sugestoes.php` - API AJAX
- `database/create_sugestoes_compra.sql` - Script SQL
- `testar_sugestoes.php` - Arquivo de teste

### **Arquivos Modificados**
- `produtos.php` - Adicionado botão "Produto Acabou"
- `includes/sidebar.php` - Adicionado link no menu

## 🎨 Interface do Usuário

### **Cards de Sugestão**
- **Cores por prioridade**:
  - 🔴 Crítica: Borda vermelha
  - 🟠 Alta: Borda laranja
  - 🟡 Média: Borda amarela
  - 🟢 Baixa: Borda verde

- **Informações exibidas**:
  - Nome e código do produto
  - Quantidade sugerida
  - Consumo diário médio
  - Data da última compra
  - Dias de consumo
  - Status atual
  - Botões de ação

### **Responsividade**
- Design mobile-first
- Cards adaptáveis
- Menu responsivo
- Filtros organizados

## 📈 Benefícios

### **Para o Usuário**
- ✅ **Automação**: Não precisa calcular manualmente
- ✅ **Inteligência**: Baseado no consumo real
- ✅ **Organização**: Lista centralizada de compras
- ✅ **Priorização**: Foco nos produtos mais importantes
- ✅ **Histórico**: Acompanhamento do padrão de consumo

### **Para o Negócio**
- ✅ **Redução de desperdício**: Compras baseadas em necessidade real
- ✅ **Otimização de estoque**: Evita excesso ou falta
- ✅ **Economia**: Compras mais inteligentes
- ✅ **Planejamento**: Visão antecipada das necessidades

## 🔄 Fluxo de Trabalho

1. **Compra/Importação** → Produto é adicionado ao estoque
2. **Consumo** → Produto é usado ao longo do tempo
3. **Produto Acabou** → Usuário marca como acabado
4. **Análise** → Sistema calcula consumo histórico
5. **Sugestão** → Gera sugestão inteligente
6. **Gestão** → Usuário gerencia sugestões
7. **Compra** → Marca como comprada quando adquirir

## 🚀 Próximas Melhorias

### **Versão 2.2**
- 📱 **App Mobile**: Notificações push
- 🤖 **IA Avançada**: Previsão sazonal
- 📊 **Relatórios**: Análise de tendências
- 🔔 **Alertas**: Lembretes automáticos

### **Versão 2.3**
- 🏪 **Integração**: Com fornecedores
- 💰 **Preços**: Comparação automática
- 📦 **Entregas**: Agendamento automático
- 📈 **ML**: Machine Learning para previsões

## 🛠️ Instalação

1. **Execute o script SQL**:
   ```bash
   mysql -u root controle-financeiro-cpanel < database/create_sugestoes_compra.sql
   ```

2. **Teste o sistema**:
   - Acesse `testar_sugestoes.php`
   - Verifique se tudo está funcionando

3. **Use o sistema**:
   - Vá para **Produtos**
   - Marque um produto como acabado
   - Veja a sugestão gerada

## 📞 Suporte

- 🐛 **Bugs**: Reporte na página de sugestões
- 💡 **Sugestões**: Use o sistema de feedback
- 📧 **Email**: suporte@controlefinanceiro.com
- 📚 **Documentação**: Este arquivo

---

**🎉 Sistema de Sugestões de Compra Inteligente - Transformando dados em decisões inteligentes!**
