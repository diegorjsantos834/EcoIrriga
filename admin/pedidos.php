<?php
/**
 * ============================================================================
 *  admin/pedidos.php — PEDIDOS (leitura + atualização de status)
 * ----------------------------------------------------------------------------
 *  READ   → lista pedidos com JOIN no cliente;
 *  UPDATE → altera o status do pedido (pendente → pago → enviado → entregue);
 *  DELETE → cancela o pedido (status = cancelado).
 *
 *  Os itens de cada pedido aparecem ao expandir a linha (leitura da tabela
 *  associativa ITENS_PEDIDO).
 * ============================================================================
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conexao();

$statusPermitidos = ['pendente', 'pago', 'enviado', 'entregue', 'cancelado'];

// ---------- UPDATE / "DELETE" (POST) ---------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (in_array($status, $statusPermitidos, true)) {
        $stmt = $pdo->prepare('UPDATE pedidos SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        flash('ok', "Pedido #{$id} atualizado para “{$status}”.");
    } else {
        flash('erro', 'Status inválido.');
    }

    header('Location: pedidos.php');
    exit;
}

// ---------- READ — pedidos + cliente + itens --------------------------------
$pedidos = $pdo->query(
    'SELECT p.id, p.total, p.status, p.criado_em,
            c.nome AS cliente_nome, c.email AS cliente_email
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
      ORDER BY p.id DESC'
)->fetchAll();

// Lê todos os itens de uma vez e agrupa por pedido (evita N+1 queries)
$itensPorPedido = [];
$rows = $pdo->query(
    'SELECT pedido_id, nome_produto, preco_unitario, quantidade
       FROM itens_pedido ORDER BY pedido_id, id'
)->fetchAll();
foreach ($rows as $r) {
    $itensPorPedido[$r['pedido_id']][] = $r;
}

$titulo = 'Pedidos';
$pagina = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <div class="section-head">
      <h1>Pedidos</h1>
      <p>Acompanhe o ciclo de vida de cada compra.</p>
    </div>

    <div class="card admin-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Pedido</th><th>Cliente</th><th>Itens</th><th>Total</th>
              <th>Status</th><th>Data</th><th class="actions-col">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$pedidos): ?>
              <tr><td colspan="7" class="muted">Nenhum pedido registrado.</td></tr>
            <?php endif; ?>

            <?php foreach ($pedidos as $p): ?>
              <tr>
                <td><strong>#<?= (int) $p['id'] ?></strong></td>
                <td>
                  <?= esc($p['cliente_nome']) ?><br>
                  <small class="muted"><?= esc($p['cliente_email']) ?></small>
                </td>
                <td>
                  <details class="order-items">
                    <summary><?= count($itensPorPedido[$p['id']] ?? []) ?> item(ns)</summary>
                    <ul>
                      <?php foreach ($itensPorPedido[$p['id']] ?? [] as $i): ?>
                        <li><?= (int) $i['quantidade'] ?>× <?= esc($i['nome_produto']) ?>
                            — <?= brl($i['preco_unitario'] * $i['quantidade']) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </details>
                </td>
                <td><?= brl($p['total']) ?></td>
                <td><span class="badge badge-<?= esc($p['status']) ?>"><?= esc($p['status']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
                <td class="actions-col">
                  <!-- UPDATE do status -->
                  <form method="post" action="pedidos.php" class="status-form">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <select name="status" aria-label="Status do pedido <?= (int) $p['id'] ?>">
                      <?php foreach ($statusPermitidos as $s): ?>
                        <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>>
                          <?= ucfirst($s) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-ghost btn-sm" type="submit">OK</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <a class="back-link" href="index.php">← Voltar ao painel</a>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
