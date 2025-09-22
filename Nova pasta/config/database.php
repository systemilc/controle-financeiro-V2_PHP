<?php
/**
 * Configurações do banco de dados
 */

// Configurar fuso horário brasileiro
date_default_timezone_set('America/Sao_Paulo');
class Database {
    private $primary = [
        'host' => 'localhost',
        'db'   => 'controle-financeiro-cpanel',
        'user' => 'root',
        'pass' => ''
    ];

    private $secondary = [
        'host' => 'localhost',
        'db'   => 'smartvirtuacom_controle-financeiro',
        'user' => 'smartvirtuacom_isaac',
        'pass' => '@#*Ilcn31Thmpv77d6f'
    ];

    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Tenta a conexão principal
            $this->conn = new PDO(
                "mysql:host=" . $this->primary['host'] . ";dbname=" . $this->primary['db'],
                $this->primary['user'],
                $this->primary['pass']
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");

        } catch(PDOException $e1) {
            // Se falhar, tenta a conexão alternativa
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->secondary['host'] . ";dbname=" . $this->secondary['db'],
                    $this->secondary['user'],
                    $this->secondary['pass']
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->exec("set names utf8");
                echo "Conectado ao banco alternativo (smartvirtuacom).";
            } catch(PDOException $e2) {
                echo "Erro ao conectar em ambos os bancos: " . $e2->getMessage();
            }
        }

        return $this->conn;
    }
}
?>

