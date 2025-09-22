# 🔐 Gestão de Usuários: Diferenças entre Desaprovar, Desativar e Bloquear

## 📋 **Visão Geral das Ações**

O sistema de controle financeiro possui três níveis diferentes de restrição de acesso para usuários:

| Ação | Campo Afetado | Duração | Efeito no Login | Efeito no Sistema |
|------|---------------|---------|-----------------|-------------------|
| **Desaprovar** | `is_approved = 0` | Permanente | ❌ Não consegue fazer login | ❌ Sem acesso ao sistema |
| **Desativar** | `is_active = 0` | Permanente | ❌ Não consegue fazer login | ❌ Sem acesso ao sistema |
| **Bloquear** | `bloqueado_ate = data_futura` | Temporária | ❌ Não consegue fazer login | ❌ Sem acesso ao sistema |

---

## 1️⃣ **DESAPROVAR** (`is_approved = 0`)

### **O que é:**
- Remove a **aprovação** do usuário pelo administrador
- Usuário volta ao status de "pendente de aprovação"

### **Quando usar:**
- Usuário criou conta mas ainda não foi aprovado pelo admin
- Admin quer reverter uma aprovação anterior
- Usuário precisa ser reavaliado

### **Efeitos:**
- ❌ **Login:** Não consegue fazer login
- ❌ **Sistema:** Sem acesso a nenhuma funcionalidade
- ⏳ **Status:** Aparece como "Pendente" na lista de usuários
- 🔄 **Reversível:** Admin pode aprovar novamente

### **Código:**
```php
// Desaprovar usuário
public function desaprovar() {
    $query = "UPDATE usuarios SET is_approved = 0 WHERE id = :id";
    // ...
}
```

---

## 2️⃣ **DESATIVAR** (`is_active = 0`)

### **O que é:**
- **Desativa** a conta do usuário permanentemente
- Usuário fica inativo no sistema

### **Quando usar:**
- Usuário não deve mais ter acesso ao sistema
- Suspensão permanente por violação de regras
- Usuário saiu da empresa/organização
- Conta temporariamente suspensa

### **Efeitos:**
- ❌ **Login:** Não consegue fazer login
- ❌ **Sistema:** Sem acesso a nenhuma funcionalidade
- ⏳ **Status:** Aparece como "Inativo" na lista de usuários
- 🔄 **Reversível:** Admin pode ativar novamente

### **Código:**
```php
// Desativar usuário
public function desativar() {
    $query = "UPDATE usuarios SET is_active = 0 WHERE id = :id";
    // ...
}
```

---

## 3️⃣ **BLOQUEAR** (`bloqueado_ate = data_futura`)

### **O que é:**
- **Bloqueia** temporariamente o acesso do usuário
- Usuário fica bloqueado por um período específico

### **Quando usar:**
- Muitas tentativas de login incorretas
- Suspeita de atividade suspeita
- Bloqueio temporário por violação
- Medida de segurança preventiva

### **Efeitos:**
- ❌ **Login:** Não consegue fazer login
- ❌ **Sistema:** Sem acesso a nenhuma funcionalidade
- ⏳ **Status:** Aparece como "Bloqueado" na lista de usuários
- ⏰ **Duração:** Bloqueio por tempo determinado (padrão: 30 minutos)
- 🔄 **Reversível:** Desbloqueia automaticamente ou manualmente

### **Código:**
```php
// Bloquear usuário (30 minutos por padrão)
public function bloquear($minutos = 30) {
    $bloqueado_ate = date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"));
    $query = "UPDATE usuarios SET bloqueado_ate = :bloqueado_ate WHERE id = :id";
    // ...
}
```

---

## 🔍 **Verificação de Status no Sistema**

### **Método de Login (`Auth.php`):**
```php
public function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['is_approved'] == 1;
}
```

### **Verificação de Bloqueio:**
```php
public function isBloqueado() {
    if($this->bloqueado_ate) {
        return strtotime($this->bloqueado_ate) > time();
    }
    return false;
}
```

---

## 📊 **Comparação Prática**

| Aspecto | Desaprovar | Desativar | Bloquear |
|---------|------------|-----------|----------|
| **Duração** | Permanente | Permanente | Temporária |
| **Motivo** | Não aprovado | Suspensão | Segurança |
| **Reversão** | Manual (aprovar) | Manual (ativar) | Automática/Manual |
| **Uso Comum** | Novos usuários | Usuários problemáticos | Tentativas de login |
| **Status Visual** | 🟡 Pendente | 🔴 Inativo | 🚫 Bloqueado |

---

## 🎯 **Fluxo de Aprovação de Usuários**

### **1. Usuário se Cadastra:**
- `is_approved = 0` (não aprovado)
- `is_active = 1` (ativo)
- `bloqueado_ate = NULL` (não bloqueado)

### **2. Admin Aprova:**
- `is_approved = 1` ✅
- `is_active = 1` ✅
- `bloqueado_ate = NULL` ✅

### **3. Se Houver Problemas:**
- **Desaprovar:** Volta para `is_approved = 0`
- **Desativar:** Muda para `is_active = 0`
- **Bloquear:** Define `bloqueado_ate = data_futura`

---

## ⚠️ **Importante Saber**

### **Hierarquia de Verificação:**
1. **Primeiro:** Verifica se está aprovado (`is_approved = 1`)
2. **Segundo:** Verifica se está ativo (`is_active = 1`)
3. **Terceiro:** Verifica se não está bloqueado (`bloqueado_ate`)

### **Para o Usuário Fazer Login, TODOS devem ser verdadeiros:**
- ✅ `is_approved = 1`
- ✅ `is_active = 1`
- ✅ `bloqueado_ate` é NULL ou data passada

### **Recomendações de Uso:**
- **Desaprovar:** Para novos usuários ou reavaliação
- **Desativar:** Para suspensão permanente
- **Bloquear:** Para medidas de segurança temporárias

---
**Sistema de gestão de usuários com três níveis de controle de acesso! 🔐**
