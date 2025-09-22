<?php
/**
 * Configurações de Email
 * 
 * Configure as informações do servidor SMTP para envio de emails
 */

return [
    // Configurações do servidor SMTP
    'smtp' => [
        'host' => 'mail.smartvirtua.com.br',  // Servidor SMTP da SmartVirtua
        'port' => 465,                        // Porta SMTP (465 para SSL)
        'encryption' => 'ssl',                // Tipo de criptografia (SSL)
        'username' => 'administrador@smartvirtua.com.br',  // Email da conta
        'password' => '',                     // Senha da conta (será configurada via interface)
        'from_email' => 'administrador@smartvirtua.com.br', // Email remetente
        'from_name' => 'Controle Financeiro - SmartVirtua'  // Nome do remetente
    ],
    
    // Configurações gerais
    'general' => [
        'timeout' => 30,                      // Timeout para conexão SMTP (segundos)
        'charset' => 'UTF-8',                 // Charset dos emails
        'debug' => false                      // Ativar debug do SMTP
    ],
    
    // Configurações de convite
    'convite' => [
        'expira_dias' => 7,                   // Dias para expiração do convite
        'url_base' => 'https://smartvirtua.com.br/controle-financeiro', // URL base do sistema
        'template_path' => 'templates/email/' // Caminho para templates de email
    ]
];
?>
