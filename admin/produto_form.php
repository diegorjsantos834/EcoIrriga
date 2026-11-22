<?php
/**
 * ============================================================================
 *  admin/produto_form.php — CREATE e UPDATE de PRODUTOS
 * ----------------------------------------------------------------------------
 *  • Sem ?id=  → formulário vazio e INSERT ao enviar (CREATE);
 *  • Com ?id=  → carrega o registro e UPDATE ao enviar (UPDATE).
 *
 *  Todas as consultas usam prepared statements (PDO) e todos os dados
 *  exibidos passam por esc() — boas práticas de segurança.
 * ============================================================================
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conexao();

$id     = (int) ($_GET['id'] ?? 0);
$erros  = [];
$produto = [
    'nome' => '', 'categoria_id' => 0, 'descricao' => '',
    'preco' => '', 'estoque' => 0, 'imagem' => '', 'destaque' => 0, 'ativo' => 1,
];

// ---------- READ: carrega produto para edição -------------------------------
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        flash('erro', 'Produto não encontrado.');
        header('Location: produtos.php');
        exit;
    }
}

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

// ---------- CREATE / UPDATE (POST) -----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Leitura dos campos enviados
    $produto = [
        'nome'         => trim($_POST['nome'] ?? ''),
        'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
        'descricao'    => trim($_POST['descricao'] ?? ''),
        'preco'        => paraNumero($_POST['preco'] ?? '0'),   // "349,90" → 349.90
        'estoque'      => max(0, (int) ($_POST['estoque'] ?? 0)),
        'imagem'       => trim($_POST['imagem'] ?? ''),
        'destaque'     => isset($_POST['destaque']) ? 1 : 0,
        'ativo'        => isset($_POST['ativo']) ? 1 : 0,
    ];

    // 2) Validações
    if ($produto['nome'] === '') {
        $erros[] = 'Informe o nome do produto.';
    }
    if ($produto['categoria_id'] <= 0) {
        $erros[] = 'Selecione a categoria.';
    }
    if ($produto['preco'] <= 0) {
        $erros[] = 'Informe um preço válido (maior que zero).';
    }

    // 3) Gravação
    if (!$erros) {
        try {
            if ($id > 0) {
                // ---------------- UPDATE ----------------
                $stmt = $pdo->prepare(
                    'UPDATE produtos
                        SET categoria_id = ?, nome = ?, descricao = ?, preco = ?,
                            estoque = ?, imagem = ?, destaque = ?, ativo = ?
                      WHERE id = ?'
                );
                $stmt->execute([
                    $produto['categoria_id'], $produto['nome'], $produto['descricao'],
                    $produto['preco'], $produto['estoque'], $produto['imagem'],
                    $produto['destaque'], $produto['ativo'], $id,
                ]);
                flash('ok', 'Produto atualizado com sucesso.');
            } else {
                // ---------------- CREATE ----------------
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos
                        (categoria_id, nome, descricao, preco, estoque, imagem, destaque, ativo)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $produto['categoria_id'], $produto['nome'], $produto['descricao'],
                    $produto['preco'], $produto['estoque'], $produto['imagem'],
                    $produto['destaque'], $produto['ativo'],
                ]);
                flash('ok', 'Produto criado com sucesso.');
            }

            header('Location: produtos.php');
            exit;

        } catch (PDOException $e) {
            $erros[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }

    // Se deu erro, volta o id para continuar em modo edição
    $id = $id ?: (int) ($_GET['id'] ?? 0);
}

$titulo = $id > 0 ? 'Editar produto' : 'Novo produto';
$pagina = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<section class="store-body">
  <div class="container narrow">
    <div class="section-head">
      <h1><?= $id > 0 ? 'Editar produto' : 'Cadastrar produto' ?></h1>
      <p><?= $id > 0 ? 'Altere os campos e salve as mudanças.' : 'Preencha os dados para criar um novo item da loja.' ?></p>
    </div>

    <?php if ($erros): ?>
      <div class="flash flash-erro">
        <strong>Corrija os itens abaixo:</strong>
        <ul><?php foreach ($erros as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" action="produto_form.php<?= $id > 0 ? '?id=' . $id : '' ?>" class="card checkout-form">

      <label>
        Nome do produto *
        <input type="text" name="nome" required maxlength="120"
               value="<?= esc($produto['nome']) ?>" placeholder="Ex.: Kit Essencial">
      </label>

      <div class="form-row">
        <label>
          Categoria *
          <select name="categoria_id" required>
            <option value="">— selecione —</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= (int) $c['id'] ?>"
                <?= (int) $produto['categoria_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= esc($c['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          Preço (R$) *
          <input type="text" name="preco" required inputmode="decimal"
                 value="<?= esc((string) ($produto['preco'] !== '' && $produto['preco'] !== null
                            ? number_format((float) $produto['preco'], 2, ',', '.') : '')) ?>"
                 placeholder="349,90">
        </label>
      </div>

      <div class="form-row">
        <label>
          Estoque
          <input type="number" name="estoque" min="0" value="<?= (int) $produto['estoque'] ?>">
        </label>
        <label>
          Imagem (caminho, opcional)
          <input type="text" name="imagem" maxlength="255"
                 value="<?= esc($produto['imagem']) ?>" placeholder="img/produto.jpg">
        </label>
      </div>

      <label>
        Descrição
        <textarea name="descricao" rows="4" maxlength="2000"
                  placeholder="Descreva o que o produto/kit acompanha…"><?= esc($produto['descricao']) ?></textarea>
      </label>

      <div class="check-row">
        <label class="check-inline">
          <input type="checkbox" name="destaque" value="1" <?= !empty($produto['destaque']) ? 'checked' : '' ?>>
          Marcar como <strong>destaque</strong>
        </label>
        <label class="check-inline">
          <input type="checkbox" name="ativo" value="1" <?= !empty($produto['ativo']) ? 'checked' : '' ?>>
          Visível na loja
        </label>
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">
          <?= $id > 0 ? 'Salvar alterações' : 'Cadastrar produto' ?>
        </button>
        <a class="btn btn-ghost" href="produtos.php">Cancelar</a>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
