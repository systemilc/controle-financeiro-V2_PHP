<?php
require_once 'config/email.php';

class EmailManager {
    private $config;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->config = require 'config/email.php';
        $this->smtp_host = $this->config['smtp']['host'];
        $this->smtp_port = $this->config['smtp']['port'];
        $this->smtp_username = $this->config['smtp']['username'];
        $this->smtp_password = $this->config['smtp']['password'];
        $this->smtp_encryption = $this->config['smtp']['encryption'];
        $this->from_email = $this->config['smtp']['from_email'];
        $this->from_name = $this->config['smtp']['from_name'];
    }
    
    /**
     * Enviar email usando SMTP
     */
    public function enviarEmail($para, $assunto, $mensagem, $eh_html = true) {
        // Se há configuração SMTP, tentar usar SMTP
        if(!empty($this->smtp_username) && !empty($this->smtp_password) && !empty($this->from_email)) {
            $resultado = $this->enviarEmailSMTP($para, $assunto, $mensagem, $eh_html);
            if($resultado) {
                return true;
            }
            // Se SMTP falhar, tentar mail() nativo como fallback
        }
        
        // Usar mail() nativo como fallback
        return $this->enviarEmailNativo($para, $assunto, $mensagem, $eh_html);
    }
    
    /**
     * Enviar email usando SMTP nativo
     */
    private function enviarEmailSMTP($para, $assunto, $mensagem, $eh_html = true) {
        try {
            require_once 'classes/SMTP.php';
            
            $smtp = new SMTP(
                $this->smtp_host,
                $this->smtp_port,
                $this->smtp_username,
                $this->smtp_password,
                $this->smtp_encryption,
                $this->config['general']['timeout']
            );
            
            $smtp->connect();
            $resultado = $smtp->sendEmail($this->from_email, $para, $assunto, $mensagem, $eh_html);
            $smtp->disconnect();
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email SMTP: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar email usando função mail() nativa
     */
    private function enviarEmailNativo($para, $assunto, $mensagem, $eh_html = true) {
        $headers = [];
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "MIME-Version: 1.0";
        
        if($eh_html) {
            $headers[] = "Content-Type: text/html; charset=" . $this->config['general']['charset'];
        } else {
            $headers[] = "Content-Type: text/plain; charset=" . $this->config['general']['charset'];
        }
        
        return mail($para, $assunto, $mensagem, implode("\r\n", $headers));
    }
    
    /**
     * Gerar link de convite
     */
    public function gerarLinkConvite($token) {
        $url_base = $this->config['convite']['url_base'];
        return rtrim($url_base, '/') . "/aceitar_convite.php?token=" . urlencode($token);
    }
    
    /**
     * Verificar se as configurações de email estão completas
     */
    public function isConfigurado() {
        return !empty($this->smtp_username) && 
               !empty($this->smtp_password) && 
               !empty($this->from_email);
    }
    
    /**
     * Obter status da configuração
     */
    public function getStatusConfiguracao() {
        $status = [
            'smtp_host' => !empty($this->smtp_host),
            'smtp_username' => !empty($this->smtp_username),
            'smtp_password' => !empty($this->smtp_password),
            'from_email' => !empty($this->from_email),
            'from_name' => !empty($this->from_name)
        ];
        
        $status['completo'] = array_reduce($status, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        return $status;
    }
    
    /**
     * Testar conexão SMTP
     */
    public function testarConexaoSMTP($host, $port, $encryption, $username, $password) {
        try {
            // Validar parâmetros
            if (empty($host) || empty($port) || empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Parâmetros de conexão incompletos'
                ];
            }
            
            // Configurar timeout
            $timeout = 30;
            
            // Tentar conectar ao servidor SMTP
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            
            if (!$connection) {
                return [
                    'success' => false,
                    'message' => "Não foi possível conectar ao servidor SMTP: $errstr ($errno)"
                ];
            }
            
            // Configurar timeout de leitura/escrita
            stream_set_timeout($connection, $timeout);
            
            // Ler resposta inicial do servidor
            $response = fgets($connection, 512);
            if (!$response) {
                fclose($connection);
                return [
                    'success' => false,
                    'message' => 'Servidor SMTP não respondeu'
                ];
            }
            
            // Verificar se é uma resposta válida (código 220)
            if (substr($response, 0, 3) !== '220') {
                fclose($connection);
                return [
                    'success' => false,
                    'message' => 'Resposta inválida do servidor SMTP: ' . trim($response)
                ];
            }
            
            // Enviar comando EHLO
            fwrite($connection, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $response = fgets($connection, 512);
            
            if (substr($response, 0, 3) !== '250') {
                fclose($connection);
                return [
                    'success' => false,
                    'message' => 'Servidor SMTP rejeitou comando EHLO: ' . trim($response)
                ];
            }
            
            // Se usar TLS, iniciar TLS
            if ($encryption === 'tls') {
                fwrite($connection, "STARTTLS\r\n");
                $response = fgets($connection, 512);
                
                if (substr($response, 0, 3) !== '220') {
                    fclose($connection);
                    return [
                        'success' => false,
                        'message' => 'Servidor SMTP não suporta STARTTLS: ' . trim($response)
                    ];
                }
                
                // Iniciar TLS
                if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($connection);
                    return [
                        'success' => false,
                        'message' => 'Falha ao iniciar TLS'
                    ];
                }
                
                // Reenviar EHLO após TLS
                fwrite($connection, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                $response = fgets($connection, 512);
            }
            
            // Tentar autenticação
            fwrite($connection, "AUTH LOGIN\r\n");
            $response = fgets($connection, 512);
            
            if (substr($response, 0, 3) !== '334') {
                fclose($connection);
                return [
                    'success' => false,
                    'message' => 'Servidor SMTP não suporta autenticação LOGIN: ' . trim($response)
                ];
            }
            
            // Enviar username codificado em base64
            fwrite($connection, base64_encode($username) . "\r\n");
            $response = fgets($connection, 512);
            
            if (substr($response, 0, 3) !== '334') {
                fclose($connection);
                return [
                    'success' => false,
                    'message' => 'Username rejeitado pelo servidor SMTP: ' . trim($response)
                ];
            }
            
            // Enviar password codificado em base64
            fwrite($connection, base64_encode($password) . "\r\n");
            $response = fgets($connection, 512);
            
            if (substr($response, 0, 3) !== '235') {
                fclose($connection);
                return [
                    'success' => false,
                    'message' => 'Senha rejeitada pelo servidor SMTP: ' . trim($response)
                ];
            }
            
            // Fechar conexão
            fwrite($connection, "QUIT\r\n");
            fclose($connection);
            
            return [
                'success' => true,
                'message' => "Conexão SMTP estabelecida com sucesso! Servidor: $host:$port ($encryption)"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao testar conexão SMTP: ' . $e->getMessage()
            ];
        }
    }
}
?>
