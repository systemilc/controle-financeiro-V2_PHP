<?php
require_once 'config/database.php';

class Convite {
    private $conn;
    private $table_name = "convites";

    public $id;
    public $grupo_id;
    public $convidado_por;
    public $email_convidado;
    public $token;
    public $status;
    public $data_envio;
    public $data_expiracao;
    public $data_resposta;
    public $observacoes;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo convite
    public function create() {
        // Verificar se já existe convite pendente para este email no grupo
        if ($this->conviteExistente()) {
            return false;
        }

        // Gerar token único
        $this->token = $this->gerarToken();
        
        // Definir data de expiração (7 dias)
        $this->data_expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));

        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, convidado_por=:convidado_por, 
                      email=:email_convidado, token=:token, 
                      data_expiracao=:data_expiracao, observacoes=:observacoes";

        $stmt = $this->conn->prepare($query);

        $this->email_convidado = htmlspecialchars(strip_tags($this->email_convidado));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":convidado_por", $this->convidado_por);
        $stmt->bindParam(":email_convidado", $this->email_convidado);
        $stmt->bindParam(":token", $this->token);
        $stmt->bindParam(":data_expiracao", $this->data_expiracao);
        $stmt->bindParam(":observacoes", $this->observacoes);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Enviar email de convite apenas se email_convidado não estiver vazio
            if(!empty($this->email_convidado)) {
                $this->enviarEmailConvite();
            }
            
            return true;
        }
        return false;
    }

    // Verificar se já existe convite pendente
    private function conviteExistente() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND email = :email_convidado 
                  AND status = 'pendente' AND data_expiracao > NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":email_convidado", $this->email_convidado);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Gerar token único
    private function gerarToken() {
        do {
            $token = bin2hex(random_bytes(32));
            $query = "SELECT id FROM " . $this->table_name . " WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
        } while ($stmt->rowCount() > 0);

        return $token;
    }

    // Obter convite por token
    public function getByToken($token) {
        $query = "SELECT c.*, g.nome as grupo_nome, u.username as convidado_por_nome
                  FROM " . $this->table_name . " c
                  LEFT JOIN grupos g ON c.grupo_id = g.id
                  LEFT JOIN usuarios u ON c.convidado_por = u.id
                  WHERE c.token = :token AND c.status = 'pendente' 
                  AND c.data_expiracao > NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Aceitar convite
    public function aceitar($usuario_id) {
        // Verificar se usuario_id é válido
        if(empty($usuario_id)) {
            throw new Exception("ID do usuário não pode ser vazio para aceitar convite");
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'aceito', data_resposta = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            // Adicionar usuário ao grupo como convidado
            $this->adicionarUsuarioConvidado($usuario_id);
            return true;
        }
        return false;
    }

    // Recusar convite
    public function recusar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'recusado', data_resposta = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Adicionar usuário convidado ao grupo
    private function adicionarUsuarioConvidado($usuario_id) {
        $query = "INSERT INTO usuarios_convidados 
                  SET grupo_id=:grupo_id, usuario_id=:usuario_id, convite_id=:convite_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":convite_id", $this->id);

        return $stmt->execute();
    }

    // Obter convites do grupo
    public function getByGrupo($grupo_id) {
        $query = "SELECT c.*, u.username as convidado_por_nome
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios u ON c.convidado_por = u.id
                  WHERE c.grupo_id = :grupo_id
                  ORDER BY c.data_envio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter convites pendentes do grupo
    public function getPendentesByGrupo($grupo_id) {
        $query = "SELECT c.*, u.username as convidado_por_nome
                  FROM " . $this->table_name . " c
                  LEFT JOIN usuarios u ON c.convidado_por = u.id
                  WHERE c.grupo_id = :grupo_id AND c.status = 'pendente' 
                  AND c.data_expiracao > NOW()
                  ORDER BY c.data_envio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar convites pendentes do grupo
    public function countPendentesByGrupo($grupo_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND status = 'pendente' 
                  AND data_expiracao > NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Contar convites aceitos do grupo
    public function countAceitosByGrupo($grupo_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND status = 'aceito'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Cancelar convite
    public function cancelar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'expirado', data_resposta = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Verificar se usuário já está no grupo
    public function usuarioJaNoGrupo($email, $grupo_id) {
        $query = "SELECT u.id FROM usuarios u
                  LEFT JOIN usuarios_convidados uc ON u.id = uc.usuario_id AND uc.grupo_id = :grupo_id
                  WHERE u.email = :email AND (u.grupo_id = :grupo_id OR uc.usuario_id IS NOT NULL)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Limpar convites expirados
    public function limparExpirados() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'expirado'
                  WHERE status = 'pendente' AND data_expiracao < NOW()";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    // Obter estatísticas de convites do grupo
    public function getEstatisticasByGrupo($grupo_id) {
        $query = "SELECT 
                    COUNT(*) as total_convites,
                    COUNT(CASE WHEN status = 'pendente' AND data_expiracao > NOW() THEN 1 END) as pendentes,
                    COUNT(CASE WHEN status = 'aceito' THEN 1 END) as aceitos,
                    COUNT(CASE WHEN status = 'recusado' THEN 1 END) as recusados,
                    COUNT(CASE WHEN status = 'expirado' THEN 1 END) as expirados
                  FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Enviar email de convite (usando fila)
    private function enviarEmailConvite() {
        require_once 'classes/EmailQueue.php';
        
        // Buscar dados do grupo e usuário que convidou
        $query = "SELECT g.nome as grupo_nome, u.username as convidado_por_nome
                  FROM grupos g
                  LEFT JOIN usuarios u ON u.id = :convidado_por
                  WHERE g.id = :grupo_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":convidado_por", $this->convidado_por);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->execute();
        
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dados) {
            // Gerar conteúdo do email
            $email_content = $this->gerarConteudoEmailConvite(
                $dados['grupo_nome'],
                $dados['convidado_por_nome'],
                $this->token,
                $this->observacoes
            );
            
            $assunto = "Convite para participar do grupo '{$dados['grupo_nome']}'";
            
            // Adicionar à fila de emails (prioridade alta)
            $emailQueue = new EmailQueue($this->conn);
            $emailQueue->adicionarEmail(
                $this->email_convidado,
                $assunto,
                $email_content,
                true, // HTML
                2 // Prioridade alta
            );
        }
    }
    
    // Gerar conteúdo do email de convite
    private function gerarConteudoEmailConvite($grupo_nome, $convidado_por_nome, $token, $observacoes = '') {
        require_once 'classes/EmailManager.php';
        
        // Gerar link de convite
        $emailManager = new EmailManager();
        $link_convite = $emailManager->gerarLinkConvite($token);
        
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
}
?>
