<?php
/**
 * ============================================================================
 *  admin/clientes.php — CRUD DE CLIENTES  (2ª entidade principal)
 * ----------------------------------------------------------------------------
 *  READ   → lista todos os clientes + contagem de pedidos (JOIN);
 *  DELETE → exclui o cliente (bloqueado enquanto houver pedidos — FK RESTRICT);
 *  CREATE/UPDATE → em cliente_form.php
 * ============================================================================
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conexao();

// ---------- DELETE (POST) ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $id = (int) ($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            flash('ok', 'Cliente excluído com sucesso.');
        } else {
            flash('erro', 'Cliente não encontrado.');
        }
    } catch (PDOException $e) {
        // A chave estrangeira pedidos.cliente_id impede apagar quem já comprou
        flash('erro', 'Não foi possível excluir: o cliente possui pedidos registrados.');
    }

    header('Location: clientes.php');
    exit;
}

// ---------- READ — listagem com contagem de pedidos (JOIN) ------------------
$clientes = $pdo->query(
    'SELECT c.id, c.nome, c.email, c.telefone, c.cidade, c.criado_em,
            COUNT(p.id) AS total_pedidos,
            COALESCE(SUM(p.total), 0) AS total_gasto
       FROM clientes c
       LEFT JOIN pedidos p ON p.cliente_id = c.id
      GROUP BY c.id, c.nome, c.email, c.telefone, c.cidade, c.criado_em
      ORDER BY c.nome'
)->fetchAll();

$titulo = 'Clientes';
$pagina = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <div class="section-head row">
      <div>
        <h1>Clientes</h1>
        <p>2ª entidade com CRUD completo: cadastrar, listar, editar e excluir.</p>
      </div>
      <a class="btn btn-primary" href="cliente_form.php">+ Novo cliente</a>
    </div>

    <div class="card admin-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th>
              <th>Cidade</th><th>Pedidos</th><th>Total gasto</th><th class="actions-col">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$clientes): ?>
              <tr><td colspan="8" class="muted">Nenhum cliente cadastrado.</td></tr>
            <?php endif; ?>

            <?php foreach ($clientes as $c): ?>
              <tr>
                <td><?= (int) $c['id'] ?></td>
                <td><strong><?= esc($c['nome']) ?></strong></td>
                <td><?= esc($c['email']) ?></td>
                <td><?= esc($c['telefone'] ?: '—') ?></td>
                <td><?= esc($c['cidade'] ?: '—') ?></td>
                <td><span class="badge badge-green"><?= (int) $c['total_pedidos'] ?></span></td>
                <td><?= brl($c['total_gasto']) ?></td>
                <td class="actions-col">
                  <!-- UPDATE -->
                  <a class="icon-btn" href="cliente_form.php?id=<?= (int) $c['id'] ?>" title="Editar" aria-label="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17v3Z"/><path d="M14.5 6.5 17.5 9.5"/>
                    </svg>
                  </a>

                  <!-- DELETE -->
                  <form method="post" action="clientes.php"
                        onsubmit="return confirm('Excluir o cliente “<?= esc($c['nome']) ?>”?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id"   value="<?= (int) $c['id'] ?>">
                    <button class="icon-btn danger" type="submit" title="Excluir" aria-label="Excluir">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>
                      </svg>
                    </button>
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
