<?php
require_once 'classes/EmailManager.php';

class Email {
    private $to;
    private $subject;
    private $message;
    private $headers;
    private $emailManager;

    public function __construct($to, $subject, $message) {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->emailManager = new EmailManager();
        $this->headers = $this->buildHeaders();
    }

    private function buildHeaders() {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Controle Financeiro <noreply@controlefinanceiro.com>" . "\r\n";
        $headers .= "Reply-To: noreply@controlefinanceiro.com" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        return $headers;
    }

    public function send() {
        // Usar EmailManager se configurado, senão usar mail() nativo
        if($this->emailManager->isConfigurado()) {
            return $this->emailManager->enviarEmail($this->to, $this->subject, $this->message, true);
        } else {
            return mail($this->to, $this->subject, $this->message, $this->headers);
        }
    }

    public static function enviarConvite($email_convidado, $grupo_nome, $convidado_por_nome, $token, $observacoes = '') {
        $subject = "Convite para participar do grupo '{$grupo_nome}'";
        
        // Gerar link de convite usando EmailManager
        $emailManager = new EmailManager();
        $link_convite = $emailManager->gerarLinkConvite($token);
        
        $message = "
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
        
        $email = new self($email_convidado, $subject, $message);
        return $email->send();
    }

    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $protocol . '://' . $host . $path;
    }
}
?>
