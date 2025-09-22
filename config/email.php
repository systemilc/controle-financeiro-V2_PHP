<?php
/**
 * Configurações de Email
 * 
 * Configure as informações do servidor SMTP para envio de emails
 */

return array (
  'smtp' => 
  array (
    'host' => 'mail.smartvirtua.com.br',
    'port' => 465,
    'encryption' => 'ssl',
    'username' => 'administrador@smartvirtua.com.br',
    'password' => '@#*Ilcn31Thmpv77d6f',
    'from_email' => 'administrador@smartvirtua.com.br',
    'from_name' => 'Controle Financeiro',
  ),
  'general' => 
  array (
    'timeout' => 30,
    'charset' => 'UTF-8',
    'debug' => false,
  ),
  'convite' => 
  array (
    'expira_dias' => 7,
    'url_base' => 'https://smartvirtua.com.br/controle-financeiro/',
    'template_path' => 'templates/email/',
  ),
);
?>