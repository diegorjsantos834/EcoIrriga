<?php
/**
 * ============================================================================
 *  carrinho.php — CARRINHO DE COMPRAS
 * ----------------------------------------------------------------------------
 *  Recebe as ações via POST (com redirecionamento PRG para não reenviar o
 *  formulamento ao atualizar a página):
 *    acao = adicionar  → cria item no carrinho (sessão)
 *    acao = atualizar  → altera a quantidade
 *    acao = remover    → exclui o item
 *    acao = limpar     → esvazia o carrinho
 *
 *  GET → exibe o carrinho com o total (leitura dos dados do banco).
 * ============================================================================
 */

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo = conexao();

// ---------- Tratamento das ações (POST) ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao    = $_POST['acao']    ?? '';
    $produto = (int) ($_POST['produto'] ?? 0);

    switch ($acao) {
        case 'adicionar':
            $qtd = max(1, (int) ($_POST['quantidade'] ?? 1));

            // Confere estoque antes de adicionar
            $stmt = $pdo->prepare('SELECT nome, estoque FROM produtos WHERE id = ? AND ativo = 1');
            $stmt->execute([$produto]);
            $p = $stmt->fetch();

            if (!$p) {
                flash('erro', 'Produto não encontrado.');
            } elseif ($p['estoque'] < 1) {
                flash('erro', 'Este produto está esgotado.');
            } else {
                carrinhoAdicionar($produto, min($qtd, (int) $p['estoque']));
                flash('ok', '“' . $p['nome'] . '” adicionado ao carrinho.');
            }
            break;

        case 'atualizar':
            carrinhoAtualizar($produto, (int) ($_POST['quantidade'] ?? 0));
            flash('ok', 'Carrinho atualizado.');
            break;

        case 'remover':
            carrinhoRemover($produto);
            flash('ok', 'Item removido do carrinho.');
            break;

        case 'limpar':
            carrinhoLimpar();
            flash('ok', 'Carrinho esvaziado.');
            break;
    }

    // PRG: redireciona para o GET evitar reenvio do formulário
    header('Location: carrinho.php');
    exit;
}

// ---------- Exibição (GET) --------------------------------------------------
$itens = carrinhoDetalhes($pdo);
$total = 0;
foreach ($itens as $item) {
    $total += $item['produto']['preco'] * $item['quantidade'];
}

$titulo = 'Carrinho';
$pagina = 'loja';
include __DIR__ . '/includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <div class="section-head">
      <h1>Seu carrinho</h1>
      <p>Confira os itens antes de finalizar a compra.</p>
    </div>

    <?php if (!$itens): ?>
      <div class="empty-state card">
        <p><strong>Seu carrinho está vazio.</strong></p>
        <p>Que tal começar pelo sistema completo?</p>
        <a class="btn btn-primary btn-sm" href="loja.php">Ir para a loja</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div class="cart-list card">
          <?php foreach ($itens as $item): $p = $item['produto']; ?>
            <div class="cart-row">
              <div class="cart-thumb" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M12 3c-3 4-6 6.6-6 10a6 6 0 0 0 12 0c0-3.4-3-6-6-10Z"/>
                </svg>
              </div>

              <div class="cart-info">
                <span class="product-cat"><?= esc($p['categoria_nome']) ?></span>
                <h3><a href="produto.php?id=<?= (int) $p['id'] ?>"><?= esc($p['nome']) ?></a></h3>
                <span class="price"><?= brl($p['preco']) ?> / un</span>
              </div>

              <!-- Atualiza quantidade -->
              <form method="post" action="carrinho.php" class="qty-form">
                <input type="hidden" name="acao"    value="atualizar">
                <input type="hidden" name="produto" value="<?= (int) $p['id'] ?>">
                <input type="number" name="quantidade" value="<?= (int) $item['quantidade'] ?>"
                       min="0" max="<?= (int) $p['estoque'] ?>" aria-label="Quantidade">
                <button class="btn btn-ghost btn-sm" type="submit">OK</button>
              </form>

              <strong class="cart-subtotal"><?= brl($p['preco'] * $item['quantidade']) ?></strong>

              <!-- Exclui item do carrinho -->
              <form method="post" action="carrinho.php">
                <input type="hidden" name="acao"    value="remover">
                <input type="hidden" name="produto" value="<?= (int) $p['id'] ?>">
                <button class="icon-btn danger" type="submit" title="Remover" aria-label="Remover item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>
                  </svg>
                </button>
              </form>
            </div>
          <?php endforeach; ?>

          <div class="cart-actions">
            <a class="back-link" href="loja.php">← Continuar comprando</a>
            <form method="post" action="carrinho.php"
                  onsubmit="return confirm('Esvaziar todo o carrinho?')">
              <input type="hidden" name="acao" value="limpar">
              <button class="btn btn-ghost btn-sm" type="submit">Esvaziar carrinho</button>
            </form>
          </div>
        </div>

        <aside class="cart-summary card">
          <h2>Resumo</h2>
          <?php $nItens = array_sum(array_column($itens, 'quantidade')); ?>
          <div class="summary-line">
            <span>Subtotal (<?= $nItens ?> item<?= $nItens === 1 ? '' : 's' ?>)</span>
            <strong><?= brl($total) ?></strong>
          </div>
          <div class="summary-line">
            <span>Frete</span>
            <strong class="free">Grátis</strong>
          </div>
          <div class="summary-total">
            <span>Total</span>
            <strong><?= brl($total) ?></strong>
          </div>
          <a href="checkout.php" class="btn btn-primary btn-block">Finalizar compra</a>
          <p class="secure-note">Ambiente de demonstração — nenhum pagamento é processado.</p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
