<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    
    include_once 'usuario.php';

    if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $metodo = $_SERVER['REQUEST_METHOD'];
    $acao = $_GET['acao'] ?? '';

    // --- ROTA DE CADASTRO ---
    if ($metodo === 'POST' && $acao === 'cadastro') {
        $dados = json_decode(file_get_contents("php://input"), true);

        if(!empty($dados)) {
            $cpf = $dados['cpf'] ?? '';
            $id_usuario = $dados['id_usuario'] ?? '';
            $email = $dados['email'] ?? '';
            $senha = $dados['senha'] ?? '';
            
            if(!empty($cpf) && !empty($id_usuario) && !empty($email) && !empty($senha)) {
                try {
                    Usuario::criarUsuario($cpf, $id_usuario, $email, $senha);
                    http_response_code(200);
                    echo json_encode(["Sucesso" => true, "mensagem" => "Usuário cadastrado com sucesso!"]);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["erro" => "Erro ao cadastrar: " . $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["erro" => "Falta cpf, id_usuario, email ou senha."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos."]);
        }
    } 
    // --- ROTA DE LOGIN ---
    else if ($metodo === 'POST' && $acao === 'login') {
        $dados = json_decode(file_get_contents("php://input"), true);

        // Correção dos parênteses do !empty para cada campo individualmente
        if(!empty($dados['email']) && !empty($dados['senha'])) {
            $email = $dados['email'];
            $senhaDigitada = $dados['senha'];

            try {
                $usuario = Usuario::buscarUsuarioPeloEmail($email);

                if(!empty($usuario)) {
                    if (password_verify($senhaDigitada, $usuario['senha'])) {
                        http_response_code(200);
                        echo json_encode([
                            "Sucesso" => true,
                            "mensagem" => "Login realizado com sucesso!",
                            "usuario" => [
                                "id" => $usuario['id'],
                                "permissao_id" => Usuario::buscarPermissaoUsuario($usuario['id']),
                                "email" => $usuario['email']
                            ]
                        ]);
                    } else {
                        http_response_code(401);
                        echo json_encode(["erro" => "Senha incorreta"]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(["erro" => "Usuário não encontrado"]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao buscar usuário: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos, falta e-mail ou senha"]);
        }
    }

    else if($metodo === 'POST' && $acao === 'cadastro') {
        $dados = json_decode(file_get_contents("php://input"), true);

        // Valida se todos os campos obrigatórios foram enviados
        if(
            !empty($dados['cpf']) && 
            !empty($dados['usuario_id']) && 
            !empty($dados['email']) && 
            !empty($dados['senha'])
        ) {
            $cpf = $dados['cpf'];
            $usuario_id = $dados['usuario_id'];
            $email = $dados['email'];
            $senha = $dados['senha']; // Será criptografada dentro da função

            try {
                // Chama a função que criptografa a senha e salva no banco
                Usuario::criarUsuario($cpf, $usuario_id, $email, $senha);

                http_response_code(200);
                echo json_encode(["Sucesso" => true, "mensagem" => "Usuário autenticado cadastrado com sucesso!"]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao registrar autenticação: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos. Preencha cpf, usuario_id, email e senha."]);
        }
    } 

    // --- ROTA INVÁLIDA ---
    else {
        http_response_code(400);
        echo json_encode(["erro" => "Erro: Ação inválida ou método incorreto. Verifique a URL (?acao=login ou ?acao=cadastro)."]);
    }
?>