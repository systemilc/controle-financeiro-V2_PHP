<?php
/**
 * Classe SMTP nativa para envio de emails
 */
class SMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $timeout;
    private $socket;
    
    public function __construct($host, $port = 587, $username = '', $password = '', $encryption = 'tls', $timeout = 15) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->timeout = $timeout; // Reduzido de 30 para 15 segundos
    }
    
    /**
     * Conectar ao servidor SMTP
     */
    public function connect() {
        $context = stream_context_create();
        
        if($this->encryption === 'ssl') {
            $host = 'ssl://' . $this->host;
        } else {
            $host = $this->host;
        }
        
        $this->socket = stream_socket_client(
            $host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if(!$this->socket) {
            throw new Exception("Erro ao conectar: {$errstr} ({$errno})");
        }
        
        // Ler resposta inicial
        $this->readResponse();
        
        // EHLO
        $this->sendCommand("EHLO " . $_SERVER['HTTP_HOST']);
        
        // STARTTLS se necessário
        if($this->encryption === 'tls') {
            $this->sendCommand("STARTTLS");
            if(!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Erro ao iniciar TLS");
            }
            $this->sendCommand("EHLO " . $_SERVER['HTTP_HOST']);
        }
        
        // Autenticação se credenciais fornecidas
        if(!empty($this->username) && !empty($this->password)) {
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->username));
            $this->sendCommand(base64_encode($this->password));
        }
        
        return true;
    }
    
    /**
     * Enviar email
     */
    public function sendEmail($from, $to, $subject, $message, $isHtml = true) {
        if(!$this->socket) {
            $this->connect();
        }
        
        // MAIL FROM
        $this->sendCommand("MAIL FROM: <{$from}>");
        
        // RCPT TO
        $this->sendCommand("RCPT TO: <{$to}>");
        
        // DATA
        $this->sendCommand("DATA");
        
        // Headers
        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        $headers .= "\r\n";
        
        // Enviar headers e mensagem
        fwrite($this->socket, $headers . $message . "\r\n.\r\n");
        
        // Ler resposta
        $response = $this->readResponse();
        
        return strpos($response, '250') === 0;
    }
    
    /**
     * Desconectar
     */
    public function disconnect() {
        if($this->socket) {
            $this->sendCommand("QUIT");
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Enviar comando SMTP
     */
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
        return $this->readResponse();
    }
    
    /**
     * Ler resposta do servidor (otimizado)
     */
    private function readResponse() {
        $response = '';
        $timeout = time() + $this->timeout;
        
        while(time() < $timeout) {
            $line = fgets($this->socket, 515);
            if($line === false) {
                break;
            }
            
            $response .= $line;
            
            // Verificar se é a última linha da resposta
            if(substr($line, 3, 1) === ' ') {
                break;
            }
        }
        
        return $response;
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->disconnect();
    }
}
?>
