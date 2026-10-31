<?php
class Database {
    private static $host = "";
    private static $usuario = "";
    private static $senha = "";
    private static $banco = ""; // Substitua pelo nome real do seu banco de dados
    private static $conexao = null;

    public static function getConnect() {
        if (self::$conexao === null) {
            // Cria a conexão
            self::$conexao = mysqli_connect(self::$host, self::$usuario, self::$senha, self::$banco);

            // Define o charset para UTF-8 para evitar problemas com acentuação
            mysqli_set_charset(self::$conexao, "utf8mb4");

            // Verifica se houve erro na conexão
            if (!self::$conexao) {
                die("Erro de conexão com o banco de dados: " . mysqli_connect_error());
            }
        }

        return self::$conexao;
    }
}
?>