<?php
/**
 * ============================================================================
 *  includes/funcoes.php — funções auxiliares compartilhadas por todas as páginas
 * ----------------------------------------------------------------------------
 *  • sessão (necessária para o carrinho e para mensagens de alerta)
 *  • escape de saída (evita XSS)
 *  • formatação de preço em reais
 *  • mensagens flash (aparecem 1 vez e somem no próximo acesso)
 *  • carrinho de compras guardado na sessão
 * ============================================================================
 */

// Inicia a sessão (carrinho + mensagens flash)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escapa texto antes de imprimir no HTML (proteção contra XSS). */
function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formata um número como moeda brasileira: 349.9 → "R$ 349,90". */
function brl($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/**
 * Converte um valor digitado no formato brasileiro ("1.234,56")
 * para o formato numérico do MySQL ("1234.56").
 */
function paraNumero(string $valor): float
{
    $valor = trim(str_replace(['R$', ' '], '', $valor));
    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);   // remove milhar
        $valor = str_replace(',', '.', $valor);  // vírgula → ponto
    }
    return (float) $valor;
}

/** Guarda uma mensagem para ser exibida UMA vez (no próximo carregamento). */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Lê e limpa todas as mensagens pendentes. */
function pegarFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ==========================================================================
 *  CARRINHO DE COMPRAS (guardado na sessão do usuário)
 *  Estrutura: $_SESSION['carrinho'] = [ id_produto => quantidade, ... ]
 * ========================================================================== */

/** Adiciona um produto ao carrinho (soma 1 à quantidade se já existir). */
function carrinhoAdicionar(int $produtoId, int $quantidade = 1): void
{
    $atual = $_SESSION['carrinho'][$produtoId] ?? 0;
    $_SESSION['carrinho'][$produtoId] = max(1, $atual + $quantidade);
}

/** Atualiza a quantidade de um item (0 remove). */
function carrinhoAtualizar(int $produtoId, int $quantidade): void
{
    if ($quantidade <= 0) {
        carrinhoRemover($produtoId);
        return;
    }
    $_SESSION['carrinho'][$produtoId] = $quantidade;
}

/** Remove um produto do carrinho. */
function carrinhoRemover(int $produtoId): void
{
    unset($_SESSION['carrinho'][$produtoId]);
}

/** Esvazia o carrinho (usado depois de finalizar a compra). */
function carrinhoLimpar(): void
{
    unset($_SESSION['carrinho']);
}

/** Total de itens no carrinho (para o ícone do menu). */
function carrinhoItens(): int
{
    return array_sum($_SESSION['carrinho'] ?? []);
}

/**
 * Carrega os produtos do carrinho juntando com os dados atuais do banco.
 * @return array lista de ['produto' => row, 'quantidade' => int]
 */
function carrinhoDetalhes(PDO $pdo): array
{
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (!$carrinho) {
        return [];
    }

    $ids = array_map('intval', array_keys($carrinho));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // JOIN com categorias para também mostrar o nome da categoria
    $stmt = $pdo->prepare(
        "SELECT p.*, c.nome AS categoria_nome
           FROM produtos p
           JOIN categorias c ON c.id = p.categoria_id
          WHERE p.id IN ($placeholders) AND p.ativo = 1"
    );
    $stmt->execute($ids);

    $itens = [];
    foreach ($stmt->fetchAll() as $produto) {
        $itens[] = [
            'produto'    => $produto,
            'quantidade' => (int) $carrinho[$produto['id']],
        ];
    }
    return $itens;
}
