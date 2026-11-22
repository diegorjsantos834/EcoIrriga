<?php
/**
 * ============================================================================
 *  admin/index.php — PAINEL ADMINISTRATIVO (dashboard)
 * ----------------------------------------------------------------------------
 *  Reúne consultas agregadas (COUNT/SUM) e as últimas compras.
 *  A partir daqui o usuário acessa os CRUDs de produtos e clientes.
 * ============================================================================
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conexao();

// Consultas agregadas — quantidades e faturamento
$stats = [
    'produtos'  => (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn(),
    'clientes'  => (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
    'pedidos'   => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
    'faturamento' => (float) $pdo->query(
        "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE status <> 'cancelado'"
    )->fetchColumn(),
];

// Últimos 5 pedidos com o nome do cliente (JOIN)
$ultimos = $pdo->query(
    'SELECT p.id, p.total, p.status, p.criado_em, c.nome AS cliente_nome
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
      ORDER BY p.id DESC
      LIMIT 5'
)->fetchAll();

$titulo = 'Painel';
$pagina = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <div class="section-head">
      <h1>Painel administrativo</h1>
      <p>Visão geral da loja Ecolrriga — dados lidos do banco <code>ecolrriga</code>.</p>
    </div>

    <div class="stat-grid">
      <a class="stat-card card" href="produtos.php">
        <small>Produtos cadastrados</small>
        <strong><?= $stats['produtos'] ?></strong>
        <span>Gerenciar CRUD →</span>
      </a>
      <a class="stat-card card" href="clientes.php">
        <small>Clientes</small>
        <strong><?= $stats['clientes'] ?></strong>
        <span>Gerenciar CRUD →</span>
      </a>
      <a class="stat-card card" href="pedidos.php">
        <small>Pedidos</small>
        <strong><?= $stats['pedidos'] ?></strong>
        <span>Ver pedidos →</span>
      </a>
      <div class="stat-card card highlight">
        <small>Faturamento</small>
        <strong><?= brl($stats['faturamento']) ?></strong>
        <span>pedidos não cancelados</span>
      </div>
    </div>

    <div class="admin-actions">
      <a class="btn btn-primary" href="produto_form.php">+ Novo produto</a>
      <a class="btn btn-ghost" href="cliente_form.php">+ Novo cliente</a>
    </div>

    <div class="card admin-card">
      <h2>Últimos pedidos</h2>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Pedido</th><th>Cliente</th><th>Total</th><th>Status</th><th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$ultimos): ?>
              <tr><td colspan="5" class="muted">Nenhum pedido registrado ainda.</td></tr>
            <?php endif; ?>
            <?php foreach ($ultimos as $p): ?>
              <tr>
                <td><strong>#<?= (int) $p['id'] ?></strong></td>
                <td><?= esc($p['cliente_nome']) ?></td>
                <td><?= brl($p['total']) ?></td>
                <td><span class="badge badge-<?= esc($p['status']) ?>"><?= esc($p['status']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
