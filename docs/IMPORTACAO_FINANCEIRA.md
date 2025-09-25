# Sistema de Importação Financeira

## Visão Geral

O sistema de importação financeira permite importar planilhas de compras e automaticamente criar transações financeiras no sistema, alimentando o controle financeiro com dados reais.

## Funcionalidades

### 1. Processamento de Planilhas
- Suporte a múltiplos formatos: CSV, TSV, TXT, XLSX, XLS
- Agrupamento automático por fornecedor
- Criação automática de fornecedores e produtos
- Preview dos dados antes da importação

### 2. Configuração de Importação
- Seleção de conta para débito
- Escolha de categoria para as transações
- Definição do tipo de pagamento
- Opção de confirmação automática
- Sistema de parcelamento

### 3. Criação de Transações
- Transações financeiras criadas automaticamente
- Agrupamento por compra (fornecedor + data + nota)
- Suporte a parcelamento (dividir ou multiplicar)
- Integração com sistema de saldo de contas

## Formato da Planilha

A planilha deve conter as seguintes colunas (separadas por TABULAÇÃO):

| Coluna | Descrição | Exemplo |
|--------|-----------|---------|
| DATA | Data da compra | 15/01/2024 |
| NOTA | Número da nota fiscal | 123456 |
| RAZÃO | Razão social do fornecedor | Supermercado ABC Ltda |
| CNPJ | CNPJ do fornecedor | 12.345.678/0001-90 |
| CODIGO PRODUTO | Código do produto | ARR001 |
| PRODUTO | Nome do produto | Arroz 5kg |
| QUANTIDADE | Quantidade comprada | 2 |
| VALOR UNITARIO | Preço unitário | 15,50 |
| VALOR TOTAL | Valor total do item | 31,00 |

## Processo de Importação

### Passo 1: Upload da Planilha
1. Acesse "Importar Planilha" no menu lateral
2. Selecione o arquivo da planilha
3. Clique em "Processar Planilha"

### Passo 2: Preview dos Dados
O sistema mostrará:
- Número total de compras
- Número total de produtos
- Valor total a ser importado
- Tabela com resumo das compras

### Passo 3: Configuração
Configure:
- **Conta**: Conta que será debitada
- **Categoria**: Categoria das transações (despesas)
- **Tipo de Pagamento**: Forma de pagamento
- **Confirmação Automática**: Se as transações devem ser confirmadas automaticamente
- **Parcelamento**: Se deseja parcelar as transações

### Passo 4: Confirmação
Clique em "Confirmar Importação" para criar:
- Fornecedores (se não existirem)
- Produtos (se não existirem)
- Transações financeiras
- Parcelas (se configurado)

## Tipos de Parcelamento

### Dividir Valor
- Divide o valor total pelo número de parcelas
- Cada parcela tem o mesmo valor
- Exemplo: R$ 100,00 em 3 parcelas = R$ 33,33 cada

### Multiplicar Valor
- Mantém o valor total em cada parcela
- Útil para compras recorrentes
- Exemplo: R$ 100,00 em 3 parcelas = R$ 100,00 cada

## Exemplo de Uso

1. **Preparar Planilha**: Use o arquivo `exemplo_planilha_compra.tsv` como modelo
2. **Upload**: Faça upload da planilha
3. **Preview**: Verifique se os dados estão corretos
4. **Configurar**: Selecione conta, categoria e tipo de pagamento
5. **Importar**: Confirme a importação

## Resultado

Após a importação, o sistema terá:
- ✅ Fornecedores cadastrados
- ✅ Produtos cadastrados
- ✅ Transações financeiras criadas
- ✅ Saldo das contas atualizado
- ✅ Histórico de compras disponível

## Vantagens

- **Automatização**: Elimina digitação manual de transações
- **Organização**: Dados estruturados e organizados
- **Rastreabilidade**: Histórico completo de compras
- **Integração**: Alimenta automaticamente o sistema financeiro
- **Flexibilidade**: Suporte a parcelamento e diferentes configurações

## Limitações

- Máximo de 10MB por arquivo
- Apenas transações de despesa (compras)
- Requer formato específico da planilha
- Depende de conexão com banco de dados

## Solução de Problemas

### Erro de Formato
- Verifique se as colunas estão corretas
- Use TABULAÇÃO entre colunas (não vírgula)
- Salve como TSV ou TXT

### Erro de Upload
- Verifique o tamanho do arquivo (máx 10MB)
- Confirme se o formato é suportado
- Verifique permissões da pasta uploads/

### Erro de Processamento
- Verifique se há dados válidos na planilha
- Confirme se as datas estão no formato DD/MM/AAAA
- Verifique se os valores estão com vírgula como separador decimal
