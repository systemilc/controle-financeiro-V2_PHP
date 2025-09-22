<?php
/**
 * Script para configurar banco de dados para sistema de emails
 */
require_once 'config/database.php';

echo "<h2>🔧 Configurando Banco de Dados para Sistema de Emails</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Conexão com banco de dados estabelecida</p>";
    
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
    echo "<p>✅ Tabela email_queue criada com sucesso</p>";
    
    // Verificar se tabela foi criada
    $result = $db->query("SHOW TABLES LIKE 'email_queue'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ Tabela email_queue verificada no banco</p>";
    } else {
        echo "<p>❌ Erro: Tabela email_queue não foi criada</p>";
    }
    
    // Criar diretório de logs se não existir
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
        echo "<p>✅ Diretório logs criado</p>";
    } else {
        echo "<p>✅ Diretório logs já existe</p>";
    }
    
    echo "<h3>🎯 Configuração Concluída!</h3>";
    echo "<p>O sistema de emails está pronto para uso.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
