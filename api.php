<?php
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    
    include_once 'produto.php';

    if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $metodo = $_SERVER['REQUEST_METHOD'];

    // GET
    if ($metodo === 'GET') {
        try {
            $tipo = $_GET['tipo'] ?? null;

            if($tipo === 'ultimo') {
                $response = Produto::buscarRecentesAdd();
            } 
            else if ($tipo === 'armario' && !empty($_GET['armario_id'])) {
                $armario_id = $_GET['armario_id'];
                $response = Produto::buscarPorArmario($armario_id);
            } 
            else if($tipo === 'produto' && !empty($_GET['nome'])) {
                $response = Produto::buscarProdutoPorNome($_GET['nome']);
            }
            else if ($tipo === 'entrada') {
                $response = Produto::buscarEntradaProdutos();
            }
            else if($tipo === 'saida') {
                $response = Produto::buscarSaidaProdutos();
            }
            else if($tipo === 'armario') {
                $response = Produto::buscarTodosArmarios();
            } 
            else {
                $response = Produto::buscarTodosProdutos();
            }

            http_response_code(200);
            echo json_encode($response);

        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao buscar produtos: " . $e->getMessage()]);
        }

    // POST
    } else if($metodo === 'POST') {
        $id = $_GET['id'] ?? null;
        $tipo = $_GET['tipo'] ?? null;
        $acao = $_GET['acao'] ?? null;

        // 1º Trata a AÇÃO DE ENTRADA em um produto EXISTENTE (Exige ID e acao=entrada)
        if($tipo === 'produto' && $acao === 'entrada' && !empty($id)) {
            $dados = json_decode(file_get_contents("php://input"), true);
            
            if(!empty($dados['usuario_id'])) {
                $usuario_id = $dados['usuario_id'];
                $qtd = isset($dados['qtd']) ? intval($dados['qtd']) : 1;

                try {
                    $sucesso = Produto::entradaProduto($id, $qtd, $usuario_id);

                    if($sucesso) {
                        http_response_code(200);
                        echo json_encode(["Sucesso" => true, "mensagem" => "Entrada registrada com sucesso!"]);
                    } else {
                        http_response_code(400);
                        echo json_encode(["erro" => "Não foi possível registrar a entrada"]);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["erro" => "Erro ao registrar entrada: " . $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["erro" => "ID do usuário não fornecido."]);
            }
        } 
        // 2º Trata a AÇÃO DE SAÍDA em um produto EXISTENTE (Exige ID e acao=saida)
        else if($tipo === 'produto' && $acao === 'saida' && !empty($id)) {
            $dados = json_decode(file_get_contents("php://input"), true);
            try {
                $sucesso = Produto::saidaProduto($id, $dados['qtd'] ?? 0, $dados['usuario_id'] ?? null);

                if($sucesso) {
                    http_response_code(200);
                    echo json_encode(["Sucesso" => true, "mensagem" => "Dado saida na quantidade"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Não foi possível dar saida na quantidade"]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao dar saida na quantidade: " . $e->getMessage()]);
            }
        } 
        // 3º Criar Armário
        else if($tipo === 'armario') {
            $dados = json_decode(file_get_contents("php://input"), true);
            if(!empty($dados['nome'])) {
                $nome = $dados['nome'];
                try {
                    $sucesso = Produto::criarArmario($nome);
                    if($sucesso) {
                        http_response_code(200);
                        echo json_encode(["Sucesso" => true, "mensagem" => "Armario criado"]);
                    } else {
                        http_response_code(400);
                        echo json_encode(["erro" => "Não foi possível criar armario"]);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["erro" => "Erro ao criar armario: " . $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["erro" => "Dados incompletos de armário"]);
            }
        } 
        // 4º Criar Prateleira
        else if($tipo === 'prateleira') {
            $dados = json_decode(file_get_contents("php://input"), true);
            if(!empty($dados['nome'])) {
                $nome = $dados['nome'];
                try {
                    $sucesso = Produto::criarPrateleira($nome);
                    if($sucesso) {
                        http_response_code(200);
                        echo json_encode(["Sucesso" => true, "mensagem" => "Prateleira criado"]);
                    } else {
                        http_response_code(400);
                        echo json_encode(["erro" => "Não foi possível criar prateleira"]);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["erro" => "Erro ao criar prateleira: " . $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["erro" => "Dados incompletos de prateleira"]);
            }
        } 
        // 5º Criar Produto NOVO do zero (Sem ID na URL)
        else if($tipo === 'produto' && empty($id)) {
            $dados = json_decode(file_get_contents("php://input"), true);

            if(!empty($dados['nome']) && !empty($dados['armario_id']) && !empty($dados['prateleira_id']) && !empty($dados['usuario_id'])) {
                $nome = $dados['nome'];
                $qtd = $dados['qtd'] ?? 0;
                $armario = $dados['armario_id'];
                $prateleira = $dados['prateleira_id'];
                $usuario_id = $dados['usuario_id'];
                try {
                    Produto::criarProduto($nome, $qtd, $armario, $prateleira, $usuario_id);
                    http_response_code(200);
                    echo json_encode(["Sucesso" => true, "mensagem" => "Produto cadastrado com sucesso!"]);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["erro" => "Erro ao cadastrar: " . $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["erro" => "Dados incompletos para cadastro do produto."]);
            }
        }
        // 6º Fallback caso nenhum parâmetro bata
        else {
            http_response_code(400);
            echo json_encode(["erro" => "Requisição inválida ou parâmetros incompletos."]);
        }
    }
?>