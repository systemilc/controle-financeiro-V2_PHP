<?php
/**
 * Script para verificar estado atual do sistema
 */
require_once 'config/database.php';
require_once 'classes/EmailManager.php';
require_once 'classes/EmailQueue.php';

echo "<h2>🔍 Verificação do Estado do Sistema</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Conexão com banco de dados estabelecida</p>";
    
    // 1. Verificar tabela email_queue
    echo "<h3>1. Verificando Tabela email_queue</h3>";
    
    $result = $db->query("SHOW TABLES LIKE 'email_queue'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ Tabela email_queue existe</p>";
        
        // Verificar estrutura
        $result = $db->query("DESCRIBE email_queue");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Colunas encontradas: " . count($columns) . "</p>";
        
        // Verificar estatísticas
        $emailQueue = new EmailQueue($db);
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
        echo "<p>❌ Tabela email_queue NÃO existe</p>";
        echo "<p>🔧 Criando tabela...</p>";
        
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
        echo "<p>✅ Tabela email_queue criada com sucesso</p>";
    }
    
    // 2. Verificar configuração de email
    echo "<h3>2. Verificando Configuração de Email</h3>";
    
    $emailManager = new EmailManager();
    $status = $emailManager->getStatusConfiguracao();
    
    echo "<p><strong>Status da Configuração:</strong></p>";
    echo "<ul>";
    echo "<li>Servidor SMTP: " . ($status['smtp_host'] ? '✅ ' . $status['smtp_host'] : '❌ Não configurado') . "</li>";
    echo "<li>Porta SMTP: " . ($status['smtp_port'] ? '✅ ' . $status['smtp_port'] : '❌ Não configurado') . "</li>";
    echo "<li>Criptografia: " . ($status['smtp_encryption'] ? '✅ ' . $status['smtp_encryption'] : '❌ Não configurado') . "</li>";
    echo "<li>Usuário SMTP: " . ($status['smtp_username'] ? '✅ ' . $status['smtp_username'] : '❌ Não configurado') . "</li>";
    echo "<li>Senha SMTP: " . ($status['smtp_password'] ? '✅ Configurado' : '❌ Não configurado') . "</li>";
    echo "<li>Email Remetente: " . ($status['from_email'] ? '✅ ' . $status['from_email'] : '❌ Não configurado') . "</li>";
    echo "<li>Nome Remetente: " . ($status['from_name'] ? '✅ ' . $status['from_name'] : '❌ Não configurado') . "</li>";
    echo "<li>Configuração Completa: " . ($status['completo'] ? '✅ Sim' : '❌ Não') . "</li>";
    echo "</ul>";
    
    // 3. Testar conexão SMTP
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
    }
    
    // 4. Verificar convites pendentes
    echo "<h3>4. Verificando Convites Pendentes</h3>";
    
    $query = "SELECT COUNT(*) as total FROM convites WHERE status = 'pendente' AND data_envio IS NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Convites pendentes sem email: " . $result['total'] . "</p>";
    
    if ($result['total'] > 0) {
        echo "<p>⚠️ Há convites pendentes que precisam ser processados</p>";
    }
    
    // 5. Verificar erros recentes
    echo "<h3>5. Verificando Erros Recentes</h3>";
    
    if (file_exists('error_log')) {
        $error_log = file_get_contents('error_log');
        $error_lines = explode("\n", $error_log);
        $recent_errors = array_slice($error_lines, -10);
        
        echo "<p>Últimas 10 linhas do error_log:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;'>";
        foreach ($recent_errors as $line) {
            if (!empty(trim($line))) {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>";
    }
    
    echo "<h3>🎯 Verificação Concluída!</h3>";
    echo "<p>O sistema foi analisado e está pronto para uso.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
