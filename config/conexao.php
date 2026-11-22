<?php
/**
 * ============================================================================
 *  config/conexao.php — conexão com o banco de dados (MySQL via PDO)
 * ----------------------------------------------------------------------------
 *  Aqui ficam centralizadas as credenciais e a criação da conexão.
 *  Todas as páginas do sistema incluem este arquivo e usam conexao().
 *
 *  SEGURANÇA:
 *    • Usamos PDO com "prepared statements" (consulta parametrizada),
 *      o que impede SQL Injection.
 *    • As senhas ficam só aqui — nunca dentro das queries.
 * ============================================================================
 */

// --- Credenciais do banco (padrão do XAMPP) --------------------------------
define('DB_HOST', '127.0.0.1');   // servidor MySQL
define('DB_PORT', '3307');        // porta padrão do MySQL
define('DB_NAME', 'ecolrriga');   // nome do banco (criado por db/ecolrriga.sql)
define('DB_USER', 'root');        // usuário padrão do XAMPP
define('DB_PASS', '');            // senha padrão do XAMPP = vazia

/**
 * Retorna a conexão PDO (singleton — cria uma única instância e reutiliza).
 *
 * @return PDO
 */
function conexao(): PDO
{
    static $pdo = null; // guardada entre as chamadas da mesma requisição

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME);

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                // Erros viram exceção (PDOException) em vez de falhar silenciosamente
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Retorna linhas como array associativo ['coluna' => valor]
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Simula transações mesmo em bancos que não as têm (MySQL tem)
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Mensagem amigável para o aluno/debug em desenvolvimento
            http_response_code(500);
            exit('<h2>Erro ao conectar ao banco de dados</h2>
                  <p>Verifique se o MySQL (XAMPP) está ligado e se o banco
                  <code>' . DB_NAME . '</code> foi criado com o arquivo
                  <code>db/ecolrriga.sql</code>.</p>
                  <p><small>Detalhe técnico: ' . htmlspecialchars($e->getMessage()) . '</small></p>');
        }
    }

    return $pdo;
}
