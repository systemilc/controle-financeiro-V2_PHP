<?php
// Script para criar a tabela pdf_processed
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Configurando tabela PDF_processed\n";
echo "=====================================\n\n";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✅ Conectado ao banco de dados\n";
    
    // Verificar se a tabela já existe
    $stmt = $conn->query("SHOW TABLES LIKE 'pdf_processed'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️ Tabela pdf_processed já existe\n";
        echo "🔍 Verificando estrutura...\n";
        
        $stmt = $conn->query("DESCRIBE pdf_processed");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📊 Colunas encontradas:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "🔧 Criando tabela pdf_processed...\n";
        
        // Ler o arquivo SQL
        $sql = file_get_contents('database/create_pdf_table.sql');
        
        if ($sql) {
            $conn->exec($sql);
            echo "✅ Tabela pdf_processed criada com sucesso\n";
        } else {
            echo "❌ Erro ao ler arquivo SQL\n";
        }
    }
    
    // Verificar se a pasta de uploads existe
    $upload_dir = 'uploads/pdf/';
    if (!file_exists($upload_dir)) {
        echo "🔧 Criando pasta de uploads...\n";
        if (mkdir($upload_dir, 0777, true)) {
            echo "✅ Pasta uploads/pdf criada\n";
        } else {
            echo "❌ Erro ao criar pasta uploads/pdf\n";
        }
    } else {
        echo "✅ Pasta uploads/pdf já existe\n";
    }
    
    // Verificar permissões da pasta
    if (is_writable($upload_dir)) {
        echo "✅ Pasta uploads/pdf tem permissão de escrita\n";
    } else {
        echo "❌ Pasta uploads/pdf NÃO tem permissão de escrita\n";
        echo "🔧 Execute: chmod 777 uploads/pdf/\n";
    }
    
    echo "\n🎯 Configuração concluída!\n";
    echo "📝 Próximos passos:\n";
    echo "1. Teste o upload de PDF em pdf_processor.php\n";
    echo "2. Se houver problemas, execute debug_pdf_upload.php\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
