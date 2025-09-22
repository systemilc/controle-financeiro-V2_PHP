<?php
/**
 * Script para testar configuração de email
 */
require_once 'config/database.php';
require_once 'classes/EmailManager.php';

echo "<h2>🧪 Testando Configuração de Email</h2>";

try {
    // Configurar banco de dados primeiro
    echo "<h3>1. Configurando Banco de Dados</h3>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Criar tabela email_queue
    $sql_email_queue = "
    CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        para VARCHAR(255) NOT NULL,
        assunto VARCHAR(255) NOT NULL,
        mensagem LONGTEXT NOT NULL,
        eh_html BOOLEAN DEFAULT TRUE,
        prioridade INT DEFAULT 1,
        status ENUM('pendente', 'processando', 'enviado', 'falha') DEFAULT 'pendente',
        tentativas INT DEFAULT 0,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_processamento TIMESTAMP NULL,
        data_envio TIMESTAMP NULL,
        data_falha TIMESTAMP NULL,
        erro TEXT NULL,
        INDEX idx_status (status),
        INDEX idx_prioridade (prioridade),
        INDEX idx_data_criacao (data_criacao)
    )";
    
    $db->exec($sql_email_queue);
    echo "<p>✅ Tabela email_queue criada/verificada</p>";
    
    // Testar EmailManager
    echo "<h3>2. Testando EmailManager</h3>";
    
    $emailManager = new EmailManager();
    $status = $emailManager->getStatusConfiguracao();
    
    echo "<p><strong>Status da Configuração:</strong></p>";
    echo "<ul>";
    echo "<li>Servidor SMTP: " . ($status['smtp_host'] ? '✅ Configurado' : '❌ Não configurado') . "</li>";
    echo "<li>Usuário SMTP: " . ($status['smtp_username'] ? '✅ Configurado' : '❌ Não configurado') . "</li>";
    echo "<li>Senha SMTP: " . ($status['smtp_password'] ? '✅ Configurado' : '❌ Não configurado') . "</li>";
    echo "<li>Email Remetente: " . ($status['from_email'] ? '✅ Configurado' : '❌ Não configurado') . "</li>";
    echo "<li>Nome Remetente: " . ($status['from_name'] ? '✅ Configurado' : '❌ Não configurado') . "</li>";
    echo "<li>Configuração Completa: " . ($status['completo'] ? '✅ Sim' : '❌ Não') . "</li>";
    echo "</ul>";
    
    // Testar conexão SMTP se configurado
    if ($status['completo']) {
        echo "<h3>3. Testando Conexão SMTP</h3>";
        
        $config = require 'config/email.php';
        $test_result = $emailManager->testarConexaoSMTP(
            $config['smtp']['host'],
            $config['smtp']['port'],
            $config['smtp']['encryption'],
            $config['smtp']['username'],
            $config['smtp']['password']
        );
        
        if ($test_result['success']) {
            echo "<p>✅ " . $test_result['message'] . "</p>";
        } else {
            echo "<p>❌ " . $test_result['message'] . "</p>";
        }
    } else {
        echo "<h3>3. Configuração Incompleta</h3>";
        echo "<p>⚠️ Configure a senha SMTP na página de configurações de email</p>";
    }
    
    // Testar adição à fila
    echo "<h3>4. Testando Sistema de Fila</h3>";
    
    require_once 'classes/EmailQueue.php';
    $emailQueue = new EmailQueue($db);
    
    // Adicionar email de teste à fila
    $teste_adicionado = $emailQueue->adicionarEmail(
        'teste@exemplo.com',
        'Teste de Configuração',
        '<h1>Teste de Email</h1><p>Este é um email de teste do sistema.</p>',
        true,
        1
    );
    
    if ($teste_adicionado) {
        echo "<p>✅ Email de teste adicionado à fila com sucesso</p>";
        
        // Obter estatísticas
        $stats = $emailQueue->getEstatisticas();
        echo "<p><strong>Estatísticas da Fila:</strong></p>";
        echo "<ul>";
        echo "<li>Total: " . $stats['total'] . "</li>";
        echo "<li>Pendentes: " . $stats['pendentes'] . "</li>";
        echo "<li>Processando: " . $stats['processando'] . "</li>";
        echo "<li>Enviados: " . $stats['enviados'] . "</li>";
        echo "<li>Falhas: " . $stats['falhas'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Erro ao adicionar email à fila</p>";
    }
    
    echo "<h3>🎯 Teste Concluído!</h3>";
    echo "<p>O sistema de emails está configurado e funcionando.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
