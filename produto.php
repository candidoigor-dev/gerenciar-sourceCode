<?php
    include_once 'conn.php';

    final class Produto {

        public static function criarProduto($nome, $qtd, $armario_id, $prateleira_id, $usuario_id) {
            $conn = Database::getConnect();

            // 1. Inicia a transação para garantir que tudo ocorra com sucesso
            mysqli_begin_transaction($conn);

            try {
                // 2. Insere o produto
                $sqlProduto = "INSERT INTO produto(nome, quantidade) VALUES(?, ?);";
                $stmtProduto = mysqli_prepare($conn, $sqlProduto);
                mysqli_stmt_bind_param($stmtProduto, "si", $nome, $qtd);
                
                if (!mysqli_stmt_execute($stmtProduto)) {
                    throw new Exception("Erro ao cadastrar o produto.");
                }

                // Pega o ID do produto que acabou de ser inserido no banco
                $produto_id = mysqli_insert_id($conn);

                // 3. Insere a relação na tabela localizador
                $sqlLocalizador = "INSERT INTO localizador(produto_id, armario_id, prateleira_id) VALUES(?, ?, ?);";
                $stmtLocalizador = mysqli_prepare($conn, $sqlLocalizador);
                mysqli_stmt_bind_param($stmtLocalizador, "iii", $produto_id, $armario_id, $prateleira_id);
                
                if (!mysqli_stmt_execute($stmtLocalizador)) {
                    throw new Exception("Erro ao cadastrar a localização do produto.");
                }

                // 4. Insere o registro na tabela de entradas de produtos (Histórico)
                $sqlEntrada = "INSERT INTO entradaProduto(produto_id, usuario_id, quantidade_produto) VALUES(?, ?, ?);";
                $stmtEntrada = mysqli_prepare($conn, $sqlEntrada);
                mysqli_stmt_bind_param($stmtEntrada, "iii", $produto_id, $usuario_id, $qtd);

                if (!mysqli_stmt_execute($stmtEntrada)) {
                    throw new Exception("Erro ao registrar a entrada inicial do produto.");
                }

                // 5. Se deu tudo certo em todas as etapas, confirma a transação (salva de vez)
                mysqli_commit($conn);
                return true;

            } catch (Exception $e) {
                // Se qualquer coisa falhar, cancela tudo (rollback) para não bagunçar o banco
                mysqli_rollback($conn);
                throw $e; // Repassa o erro para o try/catch da sua API tratar
            }
        }

        public static function entradaProduto($id, $quantidadeAdicionada, $usuario_id) {
            $conn = Database::getConnect();

            // Inicia a transação para garantir que a atualização e o histórico ocorram juntos
            mysqli_begin_transaction($conn);

            try {
                // 1. Atualiza a quantidade do produto somando o valor informado
                $sqlProd = 'UPDATE produto SET quantidade = quantidade + ? WHERE id = ?;';
                $stmtProd = mysqli_prepare($conn, $sqlProd);
                mysqli_stmt_bind_param($stmtProd, "ii", $quantidadeAdicionada, $id);
                
                if (!mysqli_stmt_execute($stmtProd)) {
                    throw new Exception("Erro ao atualizar a quantidade do produto.");
                }

                // 2. Registra a entrada na tabela entradaProduto
                $sqlEntrada = 'INSERT INTO entradaProduto (produto_id, usuario_id, quantidade_produto) VALUES (?, ?, ?);';
                $stmtEntrada = mysqli_prepare($conn, $sqlEntrada);
                mysqli_stmt_bind_param($stmtEntrada, "iii", $id, $usuario_id, $quantidadeAdicionada);

                if (!mysqli_stmt_execute($stmtEntrada)) {
                    throw new Exception("Erro ao registrar a entrada no histórico.");
                }

                // Se tudo deu certo, efetiva as alterações no banco
                mysqli_commit($conn);
                return true;

            } catch (Exception $e) {
                // Se houver qualquer falha, desfaz as operações
                mysqli_rollback($conn);
                throw $e;
            }
        }

        public static function criarArmario($nome) {
            $conn = Database::getConnect();
            $sql = "INSERT INTO armario(nome) VALUES(?);";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $nome);
            return mysqli_stmt_execute($stmt);
        }

        public static function buscarTodosArmarios() {
            $conn = Database::getConnect();
            $sql = "SELECT * FROM armario;";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }

            return [];
        }

        public static function criarPrateleira($nome) {
            $conn = Database::getConnect();
            $sql = "INSERT INTO prateleira(nome) VALUES(?);";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $nome);
            return mysqli_stmt_execute($stmt);
        }

        // public static function addProdutoLocalizador($produto_id, $armario_id, $prateleira_id) {
        //     $conn = Database::getConnect();
        //     $sql = "INSERT INTO localizador(produto_id, armario_id, prateleira_id) VALUES(?, ?, ?);";
        //     $stmt = mysqli_prepare($conn, $sql);
        //     mysqli_stmt_bind_param($stmt, "iii", $produto_id, $armario_id, $prateleira_id);
        //     return mysqli_stmt_execute($stmt);
        // }

        public static function buscarPorArmario($armario_id) {
            $conn = Database::getConnect();
            $sql = "
                SELECT 
                    p.id AS id,
                    p.nome AS produto,
                    p.quantidade AS produto_qtd,
                    a.nome AS armario,
                    pr.nome AS prateleira
                FROM localizador l
                INNER JOIN produto p ON l.produto_id = p.id
                INNER JOIN armario a ON l.armario_id = a.id
                INNER JOIN prateleira pr ON l.prateleira_id = pr.id
                WHERE a.id = ?
                ORDER BY pr.id;
            ";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $armario_id);
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }

            return [];
        }

        public static function buscarTodosProdutos() {
            $conn = Database::getConnect();
            $sql = "
                SELECT 
                    p.id,
                    p.nome AS nome,
                    p.quantidade AS qtd,
                    p.created_at AS data,
                    a.nome AS armario,
                    pr.nome AS prateleira
                FROM localizador l
                INNER JOIN produto p ON l.produto_id = p.id
                INNER JOIN armario a ON l.armario_id = a.id
                INNER JOIN prateleira pr ON l.prateleira_id = pr.id
                ORDER BY a.id;
            ";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }

            return [];
        }

        public static function buscarProdutoPorNome($termo) {
            $conn = Database::getConnect();
            $sql = "
                SELECT 
                    p.id,
                    p.nome AS nome,
                    p.quantidade AS qtd,
                    p.created_at AS data,
                    a.nome AS armario,
                    pr.nome AS prateleira
                FROM localizador l
                INNER JOIN produto p ON l.produto_id = p.id
                INNER JOIN armario a ON l.armario_id = a.id
                INNER JOIN prateleira pr ON l.prateleira_id = pr.id
                WHERE p.nome LIKE ?
                LIMIT 10;
            ";
            
            $stmt = mysqli_prepare($conn, $sql);
            $termoBusca = "%" . $termo . "%";
            mysqli_stmt_bind_param($stmt, "s", $termoBusca);

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                $produtos = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $produtos[] = $row;
                }
                return $produtos;
            }

            return [];
        }

        public static function buscarRecentesAdd() {
            $conn = Database::getConnect();
            $sql = "
                SELECT 
                    p.id,
                    p.nome AS nome,
                    p.quantidade AS qtd,
                    p.created_at AS data,
                    a.nome AS armario,
                    pr.nome AS prateleira
                FROM localizador l
                INNER JOIN produto p ON l.produto_id = p.id
                INNER JOIN armario a ON l.armario_id = a.id
                INNER JOIN prateleira pr ON l.prateleira_id = pr.id
                ORDER BY p.created_at
                DESC LIMIT 5;
            ";
            $stmt = mysqli_prepare($conn, $sql);

            if($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
            return [];
        }

       public static function addQuantidade($id, $usuario_id) {
            $conn = Database::getConnect();

            // Inicia a transação para garantir que ambas as operações ocorram juntas
            mysqli_begin_transaction($conn);

            try {
                // 1. Atualiza a quantidade do produto somando 1
                $sqlProd = 'UPDATE produto SET quantidade = quantidade + 1 WHERE id = ?;';
                $stmtProd = mysqli_prepare($conn, $sqlProd);
                mysqli_stmt_bind_param($stmtProd, "i", $id);
                
                if (!mysqli_stmt_execute($stmtProd)) {
                    throw new Exception("Erro ao atualizar a quantidade do produto.");
                }

                // 2. Registra a entrada de 1 unidade na tabela entradaProduto
                $quantidade_adicionada = 1;
                $sqlEntrada = 'INSERT INTO entradaProduto (produto_id, usuario_id, quantidade_produto) VALUES (?, ?, ?);';
                $stmtEntrada = mysqli_prepare($conn, $sqlEntrada);
                mysqli_stmt_bind_param($stmtEntrada, "iii", $id, $usuario_id, $quantidade_adicionada);

                if (!mysqli_stmt_execute($stmtEntrada)) {
                    throw new Exception("Erro ao registrar a entrada no histórico.");
                }

                // Se tudo ocorreu bem, efetiva as alterações no banco
                mysqli_commit($conn);
                return true;

            } catch (Exception $e) {
                // Se algo der errado, desfaz tudo
                mysqli_rollback($conn);
                throw $e;
            }
        }

        public static function saidaProduto($id, $qtd, $usuario_id) {
            $conn = Database::getConnect();
            $sql = 'UPDATE produto SET quantidade = quantidade - ?, updated_at = NOW() WHERE id = ? AND quantidade >=1;';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $qtd, $id);
            $sucesso = mysqli_stmt_execute($stmt);

            if($sucesso && mysqli_stmt_affected_rows($stmt) > 0) {
                $sql = 'INSERT INTO saidaProduto(produto_id, usuario_id, quantidade_produto) VALUES (?, ?, ?);';
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iii", $id, $usuario_id, $qtd);
                return mysqli_stmt_execute($stmt);
            }
            return false;
        }

        public static function buscarEntradaProdutos() {
            $conn = Database::getConnect();
            $sql = "
                SELECT 
                    p.id,
                    u.nome AS usuario,
                    p.nome AS produto,
                    e.quantidade_produto AS quantidade,
                    e.created_at AS data_saida
                FROM entradaProduto e
                INNER JOIN produto p ON e.produto_id = p.id
                INNER JOIN usuarios u ON e.usuario_id = u.id
                ORDER BY e.created_at DESC;
            ";
            $stmt = mysqli_prepare($conn, $sql);
            if($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
            return [];
        }

        public static function buscarSaidaProdutos() {
            $conn = Database::getConnect();
            $sql = "
                SELECT 
                    u.nome AS usuario,
                    p.nome AS produto,
                    s.quantidade_produto AS quantidade,
                    s.created_at AS data_saida
                FROM saidaProduto s
                INNER JOIN produto p ON s.produto_id = p.id
                INNER JOIN usuarios u ON s.usuario_id = u.id
                ORDER BY s.created_at DESC;
            ";
            $stmt = mysqli_prepare($conn, $sql);
            if($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
            return [];
        }
        
    }
?>