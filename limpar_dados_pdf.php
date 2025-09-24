<?php
// Script para limpar dados de PDF processados
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧹 Limpando Dados de PDF Processados\n";
echo "=====================================\n\n";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✅ Conectado ao banco de dados\n";
    
    // Verificar quantos registros existem
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pdf_processed");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_registros = $result['total'];
    
    echo "📊 Registros encontrados: {$total_registros}\n";
    
    if ($total_registros > 0) {
        // Listar registros antes de deletar
        echo "\n📋 Registros que serão removidos:\n";
        $stmt = $conn->query("SELECT id, filename, razao_social, valor_total, created_at FROM pdf_processed ORDER BY created_at DESC");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($registros as $registro) {
            echo "- ID: {$registro['id']} | Arquivo: {$registro['filename']} | Emitente: {$registro['razao_social']} | Valor: R$ " . number_format($registro['valor_total'], 2, ',', '.') . " | Data: {$registro['created_at']}\n";
        }
        
        // Deletar todos os registros
        echo "\n🗑️ Removendo registros...\n";
        $stmt = $conn->query("DELETE FROM pdf_processed");
        $registros_removidos = $stmt->rowCount();
        
        echo "✅ {$registros_removidos} registros removidos com sucesso!\n";
        
        // Verificar se a tabela está vazia
        $stmt = $conn->query("SELECT COUNT(*) as total FROM pdf_processed");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] == 0) {
            echo "✅ Tabela pdf_processed está vazia\n";
        } else {
            echo "⚠️ Ainda restam {$result['total']} registros na tabela\n";
        }
        
    } else {
        echo "ℹ️ Nenhum registro encontrado para remover\n";
    }
    
    // Verificar se há arquivos na pasta uploads/pdf
    $upload_dir = 'uploads/pdf/';
    if (file_exists($upload_dir)) {
        $arquivos = glob($upload_dir . '*.pdf');
        echo "\n📁 Arquivos na pasta uploads/pdf: " . count($arquivos) . "\n";
        
        if (count($arquivos) > 0) {
            echo "Arquivos encontrados:\n";
            foreach ($arquivos as $arquivo) {
                echo "- " . basename($arquivo) . "\n";
            }
            
            echo "\n🗑️ Removendo arquivos PDF...\n";
            $arquivos_removidos = 0;
            foreach ($arquivos as $arquivo) {
                if (unlink($arquivo)) {
                    $arquivos_removidos++;
                    echo "✅ Removido: " . basename($arquivo) . "\n";
                } else {
                    echo "❌ Erro ao remover: " . basename($arquivo) . "\n";
                }
            }
            echo "✅ {$arquivos_removidos} arquivos removidos\n";
        } else {
            echo "ℹ️ Nenhum arquivo PDF encontrado na pasta\n";
        }
    } else {
        echo "ℹ️ Pasta uploads/pdf não existe\n";
    }
    
    echo "\n🎯 Limpeza concluída!\n";
    echo "📝 Agora você pode testar a importação manual novamente\n";
    echo "🔗 Acesse: http://localhost/controle-financeiro-V2_PHP/pdf_processor.php\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
