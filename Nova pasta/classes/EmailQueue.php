<?php
require_once 'config/database.php';

class EmailQueue {
    private $conn;
    private $table_name = "email_queue";

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    /**
     * Adicionar email à fila
     */
    public function adicionarEmail($para, $assunto, $mensagem, $eh_html = true, $prioridade = 1) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET para=:para, assunto=:assunto, mensagem=:mensagem, 
                      eh_html=:eh_html, prioridade=:prioridade, 
                      data_criacao=NOW(), status='pendente'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":para", $para);
        $stmt->bindParam(":assunto", $assunto);
        $stmt->bindParam(":mensagem", $mensagem);
        $stmt->bindParam(":eh_html", $eh_html, PDO::PARAM_BOOL);
        $stmt->bindParam(":prioridade", $prioridade);

        return $stmt->execute();
    }

    /**
     * Processar emails da fila
     */
    public function processarFila($limite = 10) {
        require_once 'classes/EmailManager.php';
        
        // Buscar emails pendentes
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'pendente' 
                  ORDER BY prioridade DESC, data_criacao ASC 
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $emailManager = new EmailManager();
        $processados = 0;
        $falhas = 0;

        foreach ($emails as $email) {
            // Marcar como processando
            $this->marcarProcessando($email['id']);

            // Tentar enviar email
            $sucesso = $emailManager->enviarEmail(
                $email['para'],
                $email['assunto'],
                $email['mensagem'],
                $email['eh_html']
            );

            if ($sucesso) {
                $this->marcarEnviado($email['id']);
                $processados++;
            } else {
                $this->marcarFalha($email['id']);
                $falhas++;
            }
        }

        return [
            'processados' => $processados,
            'falhas' => $falhas,
            'total' => count($emails)
        ];
    }

    /**
     * Marcar email como processando
     */
    private function marcarProcessando($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status='processando', data_processamento=NOW() 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    /**
     * Marcar email como enviado
     */
    private function marcarEnviado($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status='enviado', data_envio=NOW() 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    /**
     * Marcar email como falha
     */
    private function marcarFalha($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status='falha', tentativas=tentativas+1, data_falha=NOW() 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    /**
     * Limpar emails antigos
     */
    public function limparEmailsAntigos($dias = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE data_criacao < DATE_SUB(NOW(), INTERVAL :dias DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":dias", $dias, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obter estatísticas da fila
     */
    public function getEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status='pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN status='processando' THEN 1 ELSE 0 END) as processando,
                    SUM(CASE WHEN status='enviado' THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN status='falha' THEN 1 ELSE 0 END) as falhas
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
