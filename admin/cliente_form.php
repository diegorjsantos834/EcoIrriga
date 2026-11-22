<?php
/**
 * ============================================================================
 *  admin/cliente_form.php — CREATE e UPDATE de CLIENTES
 * ----------------------------------------------------------------------------
 *  • Sem ?id= → INSERT (CREATE);
 *  • Com ?id= → UPDATE (UPDATE);
 *  Validação de campos + e-mail único (a chave UNIQUE gera erro no banco).
 * ============================================================================
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conexao();

$id      = (int) ($_GET['id'] ?? 0);
$erros   = [];
$cliente = ['nome' => '', 'email' => '', 'telefone' => '', 'endereco' => '', 'cidade' => ''];

// ---------- READ: carrega cliente para edição -------------------------------
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        flash('erro', 'Cliente não encontrado.');
        header('Location: clientes.php');
        exit;
    }
}

// ---------- CREATE / UPDATE (POST) -----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Leitura
    $cliente = [
        'nome'     => trim($_POST['nome'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'cidade'   => trim($_POST['cidade'] ?? ''),
    ];

    // 2) Validações
    if ($cliente['nome'] === '') {
        $erros[] = 'Informe o nome do cliente.';
    }
    if (!filter_var($cliente['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    // E-mail não pode estar duplicado (procura em outro registro)
    if (!$erros) {
        $sql = 'SELECT id FROM clientes WHERE email = ?' . ($id > 0 ? ' AND id <> ?' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($id > 0 ? [$cliente['email'], $id] : [$cliente['email']]);
        if ($stmt->fetch()) {
            $erros[] = 'Já existe um cliente cadastrado com este e-mail.';
        }
    }

    // 3) Gravação
    if (!$erros) {
        try {
            if ($id > 0) {
                // ---------------- UPDATE ----------------
                $stmt = $pdo->prepare(
                    'UPDATE clientes
                        SET nome = ?, email = ?, telefone = ?, endereco = ?, cidade = ?
                      WHERE id = ?'
                );
                $stmt->execute([
                    $cliente['nome'], $cliente['email'], $cliente['telefone'],
                    $cliente['endereco'], $cliente['cidade'], $id,
                ]);
                flash('ok', 'Cliente atualizado com sucesso.');
            } else {
                // ---------------- CREATE ----------------
                $stmt = $pdo->prepare(
                    'INSERT INTO clientes (nome, email, telefone, endereco, cidade)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $cliente['nome'], $cliente['email'], $cliente['telefone'],
                    $cliente['endereco'], $cliente['cidade'],
                ]);
                flash('ok', 'Cliente cadastrado com sucesso.');
            }

            header('Location: clientes.php');
            exit;

        } catch (PDOException $e) {
            $erros[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }

    $id = $id ?: (int) ($_GET['id'] ?? 0);
}

$titulo = $id > 0 ? 'Editar cliente' : 'Novo cliente';
$pagina = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<section class="store-body">
  <div class="container narrow">
    <div class="section-head">
      <h1><?= $id > 0 ? 'Editar cliente' : 'Cadastrar cliente' ?></h1>
      <p><?= $id > 0 ? 'Atualize os dados cadastrais.' : 'Preencha os dados para cadastrar um novo cliente.' ?></p>
    </div>

    <?php if ($erros): ?>
      <div class="flash flash-erro">
        <strong>Corrija os itens abaixo:</strong>
        <ul><?php foreach ($erros as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" action="cliente_form.php<?= $id > 0 ? '?id=' . $id : '' ?>" class="card checkout-form">

      <label>
        Nome completo *
        <input type="text" name="nome" required maxlength="120"
               value="<?= esc($cliente['nome']) ?>" placeholder="Maria da Silva">
      </label>

      <div class="form-row">
        <label>
          E-mail *
          <input type="email" name="email" required maxlength="160"
                 value="<?= esc($cliente['email']) ?>" placeholder="maria@email.com">
        </label>
        <label>
          Telefone
          <input type="text" name="telefone" maxlength="20"
                 value="<?= esc($cliente['telefone']) ?>" placeholder="(11) 99999-0000">
        </label>
      </div>

      <label>
        Endereço
        <input type="text" name="endereco" maxlength="200"
               value="<?= esc($cliente['endereco']) ?>" placeholder="Rua, número, complemento">
      </label>

      <label>
        Cidade / UF
        <input type="text" name="cidade" maxlength="80"
               value="<?= esc($cliente['cidade']) ?>" placeholder="São Paulo/SP">
      </label>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">
          <?= $id > 0 ? 'Salvar alterações' : 'Cadastrar cliente' ?>
        </button>
        <a class="btn btn-ghost" href="clientes.php">Cancelar</a>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
