<?php
/**
 * ============================================================================
 *  admin/produtos.php — CRUD DE PRODUTOS
 * ----------------------------------------------------------------------------
 *  READ  → lista todos os produtos com JOIN na categoria;
 *  UPDATE → botão "ativar/desativar" muda o campo `ativo`;
 *  DELETE → botão excluir remove o registro do banco (com confirmação);
 *  CREATE/UPDATE → em produto_form.php
 *
 *  É a 1ª entidade principal com o ciclo CRUD completo.
 * ============================================================================
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conexao();

// ---------- AÇÕES (POST) — UPDATE e DELETE ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($acao === 'excluir') {
        try {
            // DELETE — itens_pedido.produto_id fica NULL (ON DELETE SET NULL)
            $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                flash('ok', 'Produto excluído com sucesso.');
            } else {
                flash('erro', 'Produto não encontrado.');
            }
        } catch (PDOException $e) {
            // Ex.: produto com pedidos históricos ou erro de integridade
            flash('erro', 'Não foi possível excluir: ' . $e->getMessage());
        }
    }

    if ($acao === 'toggle') {
        // UPDATE parcial — alterna entre visível (1) e oculto (0)
        $stmt = $pdo->prepare('UPDATE produtos SET ativo = 1 - ativo WHERE id = ?');
        $stmt->execute([$id]);
        flash('ok', 'Status do produto atualizado.');
    }

    header('Location: produtos.php');
    exit;
}

// ---------- READ — listagem -------------------------------------------------
$produtos = $pdo->query(
    'SELECT p.id, p.nome, p.preco, p.estoque, p.ativo, p.destaque,
            c.nome AS categoria_nome
       FROM produtos p
       JOIN categorias c ON c.id = p.categoria_id
      ORDER BY p.nome'
)->fetchAll();

$titulo = 'Produtos';
$pagina = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <div class="section-head row">
      <div>
        <h1>Produtos</h1>
        <p>Ciclo CRUD completo: criar, listar, editar e excluir.</p>
      </div>
      <a class="btn btn-primary" href="produto_form.php">+ Novo produto</a>
    </div>

    <div class="card admin-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th><th>Produto</th><th>Categoria</th><th>Preço</th>
              <th>Estoque</th><th>Status</th><th class="actions-col">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$produtos): ?>
              <tr><td colspan="7" class="muted">Nenhum produto cadastrado.</td></tr>
            <?php endif; ?>

            <?php foreach ($produtos as $p): ?>
              <tr class="<?= $p['ativo'] ? '' : 'row-off' ?>">
                <td><?= (int) $p['id'] ?></td>
                <td>
                  <strong><?= esc($p['nome']) ?></strong>
                  <?php if ($p['destaque']): ?><span class="badge badge-amber">destaque</span><?php endif; ?>
                </td>
                <td><?= esc($p['categoria_nome']) ?></td>
                <td><?= brl($p['preco']) ?></td>
                <td><?= (int) $p['estoque'] ?></td>
                <td>
                  <span class="badge badge-<?= $p['ativo'] ? 'verde' : 'cinza' ?>">
                    <?= $p['ativo'] ? 'visível' : 'oculto' ?>
                  </span>
                </td>
                <td class="actions-col">
                <div class="actions-grid">
                  <!-- READ (detalhe) -->
                  <a class="icon-btn" href="../produto.php?id=<?= (int) $p['id'] ?>" title="Ver na loja" aria-label="Ver na loja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.6"/>
                    </svg>
                  </a>

                  <!-- UPDATE (formulário de edição) -->
                  <a class="icon-btn" href="produto_form.php?id=<?= (int) $p['id'] ?>" title="Editar" aria-label="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17v3Z"/><path d="M14.5 6.5 17.5 9.5"/>
                    </svg>
                  </a>

                  <!-- UPDATE de status (ligar/desligar) -->
                  <form method="post" action="produtos.php">
                    <input type="hidden" name="acao" value="toggle">
                    <input type="hidden" name="id"   value="<?= (int) $p['id'] ?>">
                    <button class="icon-btn" type="submit"
                            title="<?= $p['ativo'] ? 'Ocultar da loja' : 'Publicar na loja' ?>"
                            aria-label="Alterar status">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3v9"/><path d="M6.5 6.5a7.5 7.5 0 1 0 11 0"/>
                      </svg>
                    </button>
                  </form>

                  <!-- DELETE -->
                  <form method="post" action="produtos.php"
                        onsubmit="return confirm('Excluir o produto “<?= esc($p['nome']) ?>”? Esta ação não pode ser desfeita.')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id"   value="<?= (int) $p['id'] ?>">
                    <button class="icon-btn danger" type="submit" title="Excluir" aria-label="Excluir">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>
                      </svg>
                    </button>
                  </form>
                </div>
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
