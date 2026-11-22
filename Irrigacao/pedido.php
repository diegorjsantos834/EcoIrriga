<?php
/**
 * ============================================================================
 *  pedido.php — CONFIRMAÇÃO / DETALHE DO PEDIDO  (READ com JOINs)
 * ----------------------------------------------------------------------------
 *  Recebe ?id= e monta o resumo da compra com três consultas:
 *    • pedido + cliente   (JOIN clientes)
 *    • itens do pedido    (tabela associativa)
 *    • dados de exibição  (leitura do histórico)
 * ============================================================================
 */

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo = conexao();
$id  = (int) ($_GET['id'] ?? 0);

// READ — pedido com dados do cliente
$stmt = $pdo->prepare(
    'SELECT p.id, p.total, p.status, p.criado_em,
            c.nome AS cliente_nome, c.email AS cliente_email,
            c.endereco, c.cidade
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
      WHERE p.id = ?'
);
$stmt->execute([$id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    $titulo = 'Pedido não encontrado';
    $pagina = 'loja';
    include __DIR__ . '/includes/header.php';
    echo '<section class="store-body"><div class="container">
            <div class="empty-state card">
              <h2>Pedido não encontrado</h2>
              <a class="btn btn-primary btn-sm" href="loja.php">Voltar para a loja</a>
            </div>
          </div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// READ — itens do pedido (nome e preço congelados no momento da compra)
$stmt = $pdo->prepare(
    'SELECT nome_produto, preco_unitario, quantidade
       FROM itens_pedido
      WHERE pedido_id = ?
      ORDER BY id'
);
$stmt->execute([$id]);
$itens = $stmt->fetchAll();

$titulo = 'Pedido #' . $id;
$pagina = 'loja';
include __DIR__ . '/includes/header.php';
?>

<section class="store-body">
  <div class="container narrow">

    <div class="card order-card">
      <div class="order-hero">
        <span class="order-check" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
            <path d="m5 12.5 4.5 4.5L19 7.5"/>
          </svg>
        </span>
        <h1>Pedido confirmado!</h1>
        <p>Obrigado pela compra, <strong><?= esc($pedido['cliente_nome']) ?></strong>.</p>
        <span class="badge badge-green">Pedido #<?= (int) $pedido['id'] ?> — <?= esc($pedido['status']) ?></span>
      </div>

      <div class="order-meta">
        <div>
          <small>E-mail</small>
          <strong><?= esc($pedido['cliente_email']) ?></strong>
        </div>
        <div>
          <small>Entrega</small>
          <strong><?= esc($pedido['endereco']) ?> — <?= esc($pedido['cidade']) ?></strong>
        </div>
        <div>
          <small>Data</small>
          <strong><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></strong>
        </div>
      </div>

      <table class="admin-table">
        <thead>
          <tr><th>Item</th><th>Qtd</th><th>Preço unit.</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($itens as $i): ?>
            <tr>
              <td><?= esc($i['nome_produto']) ?></td>
              <td><?= (int) $i['quantidade'] ?></td>
              <td><?= brl($i['preco_unitario']) ?></td>
              <td><?= brl($i['preco_unitario'] * $i['quantidade']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3"><strong>Total do pedido</strong></td>
            <td><strong><?= brl($pedido['total']) ?></strong></td>
          </tr>
        </tfoot>
      </table>

      <div class="cart-actions">
        <a class="btn btn-primary btn-sm" href="loja.php">Continuar comprando</a>
        <a class="btn btn-ghost btn-sm" href="admin/pedidos.php">Ver no painel admin</a>
      </div>
    </div>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
