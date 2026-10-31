<?php
    include_once 'conn.php';
    final class Usuario {

        public static function buscarUsuarioPeloEmail($email) {
            $conn = Database::getConnect();
            $sql = "SELECT * FROM authUsuario WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_assoc($result); 
        }

        public static function buscarPermissaoUsuario($id) {
            $conn = Database::getConnect();
            $sql = "SELECT p.id AS permissao_id FROM authUsuario a INNER JOIN usuarios u ON a.usuario_id = u.id INNER JOIN permissoes p ON u.permissao_id = p.id WHERE a.id = ?;";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            return $row['permissao_id'];
        }

        public static function criarUsuario($cpf, $id_usuario, $email, $senhaDigitada) {
            $conn = Database::getConnect();
            $senhaHash = password_hash($senhaDigitada, PASSWORD_DEFAULT);
            $sql = "INSERT INTO authUsuario(CPF, usuario_id, email, senha) VALUES(?, ?, ?, ?);";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siss", $cpf, $id_usuario, $email, $senhaHash);
            return mysqli_stmt_execute($stmt);
        }

        // public static function verTodosUsuarios() {
        //     $conn = Database::getConnect();
        //     $sql = 'SELECT * FROM usuarios;';

        // }
    } 
?>