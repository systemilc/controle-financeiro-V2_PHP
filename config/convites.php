<?php
// Configurações do sistema de convites

// Tempo de expiração dos convites (em dias)
define('CONVITE_EXPIRACAO_DIAS', 7);

// Configurações de email
define('EMAIL_FROM', 'noreply@controlefinanceiro.com');
define('EMAIL_FROM_NAME', 'Controle Financeiro');

// URLs base para links de convite
define('BASE_URL', 'http://localhost/controle-financeiro');

// Configurações de segurança
define('CONVITE_TOKEN_LENGTH', 64); // Tamanho do token em bytes
// Limite removido - sistema sem planos

// Configurações de notificação
define('NOTIFICAR_CONVITE_ENVIADO', true);
define('NOTIFICAR_CONVITE_ACEITO', true);
define('NOTIFICAR_CONVITE_RECUSADO', false);

// Configurações de permissões para usuários convidados
define('PERMISSOES_PADRAO_CONVIDADO', [
    'visualizar_transacoes' => true,
    'criar_transacoes' => true,
    'editar_transacoes' => false,
    'deletar_transacoes' => false,
    'visualizar_relatorios' => true,
    'gerenciar_categorias' => false,
    'gerenciar_contas' => false,
    'gerenciar_usuarios' => false
]);

// Mensagens padrão
define('MSG_CONVITE_ENVIADO', 'Convite enviado com sucesso!');
define('MSG_CONVITE_ACEITO', 'Convite aceito com sucesso!');
define('MSG_CONVITE_RECUSADO', 'Convite recusado.');
define('MSG_CONVITE_EXPIRADO', 'Convite expirado.');
define('MSG_CONVITE_INVALIDO', 'Convite inválido ou não encontrado.');
// Mensagem de limite removida - sistema sem planos
define('MSG_USUARIO_JA_NO_GRUPO', 'Este usuário já está no grupo.');
define('MSG_CONVITE_JA_EXISTE', 'Já existe um convite pendente para este email.');
?>
