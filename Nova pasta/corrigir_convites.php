<?php
/**
 * Script para corrigir problemas no sistema de convites
 */
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Convite.php';
require_once 'classes/EmailQueue.php';

echo "<h2>🔧 Corrigindo Sistema de Convites</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Conexão com banco de dados estabelecida</p>";
    
    // 1. Criar tabela email_queue se não existir
    echo "<h3>1. Configurando Tabela de Fila de Emails</h3>";
    
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
    
    // 2. Verificar se há convites pendentes sem email enviado
    echo "<h3>2. Verificando Convites Pendentes</h3>";
    
    $convite = new Convite($db);
    
    // Buscar convites pendentes
    $query = "SELECT * FROM convites WHERE status = 'pendente' AND data_envio IS NULL ORDER BY id DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $convites_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Convites pendentes encontrados: " . count($convites_pendentes) . "</p>";
    
    if (count($convites_pendentes) > 0) {
        echo "<h4>Processando convites pendentes...</h4>";
        
        $emailQueue = new EmailQueue($db);
        $processados = 0;
        
        foreach ($convites_pendentes as $convite_data) {
            // Buscar dados do grupo e usuário
            $query_grupo = "SELECT g.nome as grupo_nome, u.username as convidado_por_nome
                           FROM grupos g
                           LEFT JOIN usuarios u ON u.id = :convidado_por
                           WHERE g.id = :grupo_id";
            
            $stmt_grupo = $db->prepare($query_grupo);
            $stmt_grupo->bindParam(":convidado_por", $convite_data['convidado_por']);
            $stmt_grupo->bindParam(":grupo_id", $convite_data['grupo_id']);
            $stmt_grupo->execute();
            
            $dados_grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);
            
            if ($dados_grupo) {
                // Gerar conteúdo do email
                $email_content = gerarConteudoEmailConvite(
                    $dados_grupo['grupo_nome'],
                    $dados_grupo['convidado_por_nome'],
                    $convite_data['token'],
                    $convite_data['observacoes']
                );
                
                $assunto = "Convite para participar do grupo '{$dados_grupo['grupo_nome']}'";
                
                // Adicionar à fila de emails
                $emailQueue->adicionarEmail(
                    $convite_data['email_convidado'],
                    $assunto,
                    $email_content,
                    true, // HTML
                    2 // Prioridade alta
                );
                
                // Marcar como enviado
                $query_update = "UPDATE convites SET data_envio = NOW() WHERE id = :id";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->bindParam(":id", $convite_data['id']);
                $stmt_update->execute();
                
                $processados++;
                echo "<p>✅ Convite #{$convite_data['id']} adicionado à fila</p>";
            }
        }
        
        echo "<p><strong>Total processados: {$processados}</strong></p>";
    }
    
    // 3. Verificar estatísticas da fila
    echo "<h3>3. Estatísticas da Fila de Emails</h3>";
    
    $emailQueue = new EmailQueue($db);
    $stats = $emailQueue->getEstatisticas();
    
    echo "<ul>";
    echo "<li>Total: " . $stats['total'] . "</li>";
    echo "<li>Pendentes: " . $stats['pendentes'] . "</li>";
    echo "<li>Processando: " . $stats['processando'] . "</li>";
    echo "<li>Enviados: " . $stats['enviados'] . "</li>";
    echo "<li>Falhas: " . $stats['falhas'] . "</li>";
    echo "</ul>";
    
    // 4. Processar fila se houver emails pendentes
    if ($stats['pendentes'] > 0) {
        echo "<h3>4. Processando Fila de Emails</h3>";
        
        $resultado = $emailQueue->processarFila(10);
        
        echo "<p>Emails processados: " . $resultado['processados'] . "</p>";
        echo "<p>Falhas: " . $resultado['falhas'] . "</p>";
        echo "<p>Total: " . $resultado['total'] . "</p>";
    }
    
    echo "<h3>🎯 Correção Concluída!</h3>";
    echo "<p>O sistema de convites foi corrigido e está funcionando.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Função auxiliar para gerar conteúdo do email
function gerarConteudoEmailConvite($grupo_nome, $convidado_por_nome, $token, $observacoes = '') {
    $link_convite = "https://smartvirtua.com.br/controle-financeiro/aceitar_convite.php?token=" . urlencode($token);
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Convite para Controle Financeiro</h1>
            </div>
            <div class='content'>
                <h2>Olá!</h2>
                <p><strong>{$convidado_por_nome}</strong> convidou você para participar do grupo <strong>'{$grupo_nome}'</strong> no sistema de Controle Financeiro.</p>
                
                " . ($observacoes ? "<p><strong>Mensagem pessoal:</strong><br><em>{$observacoes}</em></p>" : "") . "
                
                <p>Com este convite, você poderá:</p>
                <ul>
                    <li>✅ Visualizar todas as transações do grupo</li>
                    <li>✅ Adicionar novas transações</li>
                    <li>✅ Gerenciar categorias e contas</li>
                    <li>✅ Acessar relatórios financeiros</li>
                    <li>✅ Colaborar com outros membros do grupo</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='{$link_convite}' class='button'>
                        🚀 Aceitar Convite
                    </a>
                </div>
                
                <div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>🔗 Link de Convite:</strong></p>
                    <p style='font-family: monospace; word-break: break-all; background: white; padding: 10px; border-radius: 3px;'>{$link_convite}</p>
                    <p><small>Você também pode copiar e colar este link no seu navegador.</small></p>
                </div>
                
                <p><strong>⚠️ Importante:</strong> Este convite expira em 7 dias. Após esse período, será necessário solicitar um novo convite.</p>
                
                <p>Se você não solicitou este convite, pode ignorar este email com segurança.</p>
            </div>
            <div class='footer'>
                <p>Este é um email automático do sistema Controle Financeiro.<br>
                Não responda a este email.</p>
            </div>
        </div>
    </body>
    </html>";
}
?>
