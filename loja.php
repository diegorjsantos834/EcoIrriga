<?php
/**
 * ============================================================================
 *  loja.php — VITRINE DA LOJA  (operção READ / Leitura)
 * ----------------------------------------------------------------------------
 *  • Lista todos os produtos ativos do banco (com JOIN na categoria);
 *  • Permite FILTRAR por categoria e BUSCAR por nome (query string);
 *  • Botão "Adicionar ao carrinho" envia para carrinho.php (Create do carrinho).
 *
 *  Requisito atendido: LEITURA (Read) do ciclo CRUD.
 * ============================================================================
 */

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo = conexao();

// ---------- Filtros (busca e categoria) ------------------------------------
$busca      = trim($_GET['q'] ?? '');
$categoriaId = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;

// Monta a consulta com parâmetros (prepared statement = seguro contra SQL Injection)
$sql = "SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.destaque,
               p.imagem, c.id AS categoria_id, c.nome AS categoria_nome
          FROM produtos p
          JOIN categorias c ON c.id = p.categoria_id
         WHERE p.ativo = 1";
$params = [];

if ($categoriaId > 0) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $categoriaId;
}
if ($busca !== '') {
    $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $like = '%' . $busca . '%';
    $params[] = $like;
    $params[] = $like;
}
$sql .= " ORDER BY p.destaque DESC, p.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Categorias para o menu de filtro (também é uma leitura do banco)
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

$titulo = 'Loja';
$pagina = 'loja';
include __DIR__ . '/includes/header.php';
?>

<section class="store-hero">
  <div class="container">
    <span class="pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.55L21 8H6"/>
        <circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>
      </svg>
      Loja Ecolrriga
    </span>
    <h1>Leve a irrigação automática para casa</h1>
    <p>Sistemas prontos e kits para montar — apenas o essencial para automatizar sua irrigação.</p>
  </div>
</section>

<section class="store-body">
  <div class="container">

    <!-- Barra de filtros: busca + categorias vêm do banco -->
    <div class="store-toolbar">
      <form class="store-search" method="get" action="loja.php">
        <?php if ($categoriaId > 0): ?>
          <input type="hidden" name="categoria" value="<?= $categoriaId ?>">
        <?php endif; ?>
        <input type="search" name="q" value="<?= esc($busca) ?>" placeholder="Buscar sistema ou kit…" aria-label="Buscar produtos">
        <button class="btn btn-primary btn-sm" type="submit">Buscar</button>
      </form>

      <div class="cat-chips">
        <a href="loja.php" class="chip <?= $categoriaId === 0 ? 'active' : '' ?>">Todos</a>
        <?php foreach ($categorias as $c): ?>
          <a href="loja.php?categoria=<?= (int) $c['id'] ?>"
             class="chip <?= $categoriaId === (int) $c['id'] ? 'active' : '' ?>">
            <?= esc($c['nome']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <p class="store-count">
      <?= count($produtos) ?> produto<?= count($produtos) === 1 ? '' : 's' ?> encontrado<?= count($produtos) === 1 ? '' : 's' ?>
      <?php if ($busca !== ''): ?> para “<?= esc($busca) ?>”<?php endif; ?>
    </p>

    <?php if (!$produtos): ?>
      <div class="empty-state card">
        <p><strong>Nenhum produto encontrado.</strong></p>
        <a class="btn btn-ghost btn-sm" href="loja.php">Limpar filtros</a>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($produtos as $p): ?>
          <article class="product-card card">
            <a href="produto.php?id=<?= (int) $p['id'] ?>" class="product-media">
              <?php if ($p['destaque']): ?><span class="badge badge-amber">Destaque</span><?php endif; ?>
              <?php if ($p['imagem']): ?>
                <img src="<?= esc($p['imagem']) ?>" alt="<?= esc($p['nome']) ?>">
              <?php else: ?>
                <!-- Placeholder desenhado com SVG enquanto não há foto -->
                <span class="product-placeholder" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 3c-3 4-6 6.6-6 10a6 6 0 0 0 12 0c0-3.4-3-6-6-10Z"/>
                    <path d="M12 20v-6"/>
                  </svg>
                </span>
              <?php endif; ?>
            </a>

            <div class="product-info">
              <span class="product-cat"><?= esc($p['categoria_nome']) ?></span>
              <h3><a href="produto.php?id=<?= (int) $p['id'] ?>"><?= esc($p['nome']) ?></a></h3>
              <p class="product-desc"><?= esc(mb_strimwidth((string) $p['descricao'], 0, 90, '…')) ?></p>

              <div class="product-foot">
                <div>
                  <strong class="price"><?= brl($p['preco']) ?></strong>
                  <?php if ($p['estoque'] > 0): ?>
                    <span class="stock ok"><?= (int) $p['estoque'] ?> em estoque</span>
                  <?php else: ?>
                    <span class="stock out">Esgotado</span>
                  <?php endif; ?>
                </div>

                <!-- CREATE do carrinho: envia o produto escolhido -->
                <form method="post" action="carrinho.php">
                  <input type="hidden" name="acao"    value="adicionar">
                  <input type="hidden" name="produto" value="<?= (int) $p['id'] ?>">
                  <button class="btn btn-primary btn-sm" type="submit" <?= $p['estoque'] > 0 ? '' : 'disabled' ?>>
                    Adicionar
                  </button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
