<?php
/**
 * ============================================================================
 *  produto.php — FICHA DO PRODUTO  (operção READ / Leitura)
 * ----------------------------------------------------------------------------
 *  • Recebe ?id= e busca o produto no banco com JOIN na categoria;
 *  • Mostra descrição, preço, estoque e formulário de compra.
 *  • Se o id não existir, responde com 404.
 * ============================================================================
 */

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo = conexao();
$id  = (int) ($_GET['id'] ?? 0);

// READ — consulta parametrizada
$stmt = $pdo->prepare(
    "SELECT p.*, c.nome AS categoria_nome
       FROM produtos p
       JOIN categorias c ON c.id = p.categoria_id
      WHERE p.id = ? AND p.ativo = 1"
);
$stmt->execute([$id]);
$produto = $stmt->fetch();

if (!$produto) {
    http_response_code(404);
    $titulo = 'Produto não encontrado';
    $pagina = 'loja';
    include __DIR__ . '/includes/header.php';
    echo '<section class="store-body"><div class="container">
            <div class="empty-state card">
              <h2>Produto não encontrado</h2>
              <p>O item que você procura não existe ou foi removido.</p>
              <a class="btn btn-primary btn-sm" href="loja.php">Voltar para a loja</a>
            </div>
          </div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$titulo = $produto['nome'];
$pagina = 'loja';
include __DIR__ . '/includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <nav class="breadcrumb">
      <a href="loja.php">Loja</a> <span>/</span>
      <a href="loja.php?categoria=<?= (int) $produto['categoria_id'] ?>"><?= esc($produto['categoria_nome']) ?></a>
      <span>/</span> <em><?= esc($produto['nome']) ?></em>
    </nav>

    <div class="product-detail card">
      <div class="product-detail-media">
        <?php if ($produto['imagem']): ?>
          <img src="<?= esc($produto['imagem']) ?>" alt="<?= esc($produto['nome']) ?>">
        <?php else: ?>
          <span class="product-placeholder big" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
              <path d="M12 3c-3 4-6 6.6-6 10a6 6 0 0 0 12 0c0-3.4-3-6-6-10Z"/>
              <path d="M12 20v-6"/>
            </svg>
          </span>
        <?php endif; ?>
      </div>

      <div class="product-detail-info">
        <span class="product-cat"><?= esc($produto['categoria_nome']) ?></span>
        <h1><?= esc($produto['nome']) ?></h1>

        <p class="price big"><?= brl($produto['preco']) ?></p>

        <ul class="check-list">
          <li>
            <span class="check">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
            </span>
            <?php if ($produto['estoque'] > 0): ?>
              <?= (int) $produto['estoque'] ?> unidades em estoque — envio imediato
            <?php else: ?>
              Temporariamente esgotado
            <?php endif; ?>
          </li>
          <li>
            <span class="check">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
            </span>
            Compatível com o projeto Ecolrriga (Arduino + sensor de umidade)
          </li>
        </ul>

        <p class="product-text"><?= nl2br(esc($produto['descricao'])) ?></p>

        <!-- CREATE do carrinho -->
        <form method="post" action="carrinho.php" class="buy-form">
          <input type="hidden" name="acao"    value="adicionar">
          <input type="hidden" name="produto" value="<?= (int) $produto['id'] ?>">
          <label class="qty">
            Qtd
            <input type="number" name="quantidade" value="1" min="1" max="<?= max(1, (int) $produto['estoque']) ?>">
          </label>
          <button class="btn btn-primary" type="submit" <?= $produto['estoque'] > 0 ? '' : 'disabled' ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.55L21 8H6"/>
            </svg>
            <?= $produto['estoque'] > 0 ? 'Adicionar ao carrinho' : 'Indisponível' ?>
          </button>
        </form>

        <a class="back-link" href="loja.php">← Continuar comprando</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
