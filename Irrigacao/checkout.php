<?php
/**
 * ============================================================================
 *  checkout.php — FINALIZAÇÃO DA COMPRA
 * ----------------------------------------------------------------------------
 *  GET  → exibe formulário do cliente + resumo do carrinho.
 *  POST → valida os dados e, dentro de uma TRANSAÇÃO, grava no banco:
 *           1) CREATE/UPDATE na tabela CLIENTES (e-mail único);
 *           2) CREATE na tabela PEDIDOS;
 *           3) CREATE em ITENS_PEDIDO (com preço congelado);
 *           4) UPDATE do ESTOQUE dos produtos;
 *           5) limpa o carrinho e redireciona para pedido.php?id=…
 *
 *  Requisito atendido: CRIAÇÃO (Create) do ciclo CRUD em 2 entidades
 *  (clientes e pedidos) + tabela associativa itens_pedido.
 * ============================================================================
 */

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$pdo    = conexao();
$itens  = carrinhoDetalhes($pdo);

// Carrinho vazio → não há nada para comprar
if (!$itens) {
    flash('erro', 'Seu carrinho está vazio.');
    header('Location: loja.php');
    exit;
}

$total = 0;
foreach ($itens as $item) {
    $total += $item['produto']['preco'] * $item['quantidade'];
}

$erros  = [];
$velho  = ['nome' => '', 'email' => '', 'telefone' => '', 'endereco' => '', 'cidade' => ''];

// ---------- PROCESSAMENTO DO PEDIDO (POST) ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Leitura e validação dos campos
    $dados = [
        'nome'     => trim($_POST['nome']     ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'cidade'   => trim($_POST['cidade']   ?? ''),
    ];
    $velho = $dados;

    if ($dados['nome'] === '') {
        $erros[] = 'Informe seu nome completo.';
    }
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($dados['endereco'] === '') {
        $erros[] = 'Informe o endereço de entrega.';
    }
    if ($dados['cidade'] === '') {
        $erros[] = 'Informe a cidade/estado.';
    }

    // 2) Confere o estoque de todos os itens antes de gravar
    foreach ($itens as $item) {
        if ($item['quantidade'] > $item['produto']['estoque']) {
            $erros[] = 'Estoque insuficiente para “' . $item['produto']['nome'] . '”.';
        }
    }

    if (!$erros) {
        try {
            // ---- TRANSAÇÃO: ou tudo é gravado, ou nada é gravado ----
            $pdo->beginTransaction();

            // 2.1) CLIENTE — se o e-mail já existir, reaproveita o cadastro
            $stmt = $pdo->prepare('SELECT id FROM clientes WHERE email = ?');
            $stmt->execute([$dados['email']]);
            $clienteId = $stmt->fetchColumn();

            if ($clienteId) {
                // UPDATE (o cliente já estava cadastrado)
                $pdo->prepare(
                    'UPDATE clientes SET nome = ?, telefone = ?, endereco = ?, cidade = ?
                      WHERE id = ?'
                )->execute([$dados['nome'], $dados['telefone'], $dados['endereco'],
                            $dados['cidade'], $clienteId]);
            } else {
                // CREATE (novo cliente)
                $pdo->prepare(
                    'INSERT INTO clientes (nome, email, telefone, endereco, cidade)
                     VALUES (?, ?, ?, ?, ?)'
                )->execute([$dados['nome'], $dados['email'], $dados['telefone'],
                            $dados['endereco'], $dados['cidade']]);
                $clienteId = (int) $pdo->lastInsertId();
            }

            // 2.2) PEDIDO
            $pdo->prepare(
                'INSERT INTO pedidos (cliente_id, total, status) VALUES (?, ?, ?)'
            )->execute([$clienteId, $total, 'pendente']);
            $pedidoId = (int) $pdo->lastInsertId();

            // 2.3) ITENS DO PEDIDO (preço e nome congelados) + baixa de estoque
            $stmtItem = $pdo->prepare(
                'INSERT INTO itens_pedido
                     (pedido_id, produto_id, nome_produto, preco_unitario, quantidade)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtEstoque = $pdo->prepare(
                'UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND estoque >= ?'
            );

            foreach ($itens as $item) {
                $p = $item['produto'];
                $qtd = $item['quantidade'];

                $stmtItem->execute([$pedidoId, $p['id'], $p['nome'], $p['preco'], $qtd]);
                $stmtEstoque->execute([$qtd, $p['id'], $qtd]);
            }

            $pdo->commit();

            carrinhoLimpar();                                  // esvazia o carrinho
            flash('ok', 'Pedido #' . $pedidoId . ' realizado com sucesso!');
            header('Location: pedido.php?id=' . $pedidoId);     // confirmação
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();   // desfaz TUDO em caso de erro
            $erros[] = 'Não foi possível registrar o pedido: ' . $e->getMessage();
        }
    }
}

$titulo = 'Finalizar compra';
$pagina = 'loja';
include __DIR__ . '/includes/header.php';
?>

<section class="store-body">
  <div class="container">
    <div class="section-head">
      <h1>Finalizar compra</h1>
      <p>Preencha seus dados para registrar o pedido.</p>
    </div>

    <?php if ($erros): ?>
      <div class="flash flash-erro">
        <strong>Corrija os itens abaixo:</strong>
        <ul>
          <?php foreach ($erros as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="checkout-layout">
      <!-- FORMULÁRIO DO CLIENTE -->
      <form method="post" action="checkout.php" class="card checkout-form">
        <h2>Seus dados</h2>

        <label>
          Nome completo *
          <input type="text" name="nome" required maxlength="120"
                 value="<?= esc($velho['nome']) ?>" placeholder="Maria da Silva">
        </label>

        <div class="form-row">
          <label>
            E-mail *
            <input type="email" name="email" required maxlength="160"
                   value="<?= esc($velho['email']) ?>" placeholder="maria@email.com">
          </label>
          <label>
            Telefone
            <input type="text" name="telefone" maxlength="20"
                   value="<?= esc($velho['telefone']) ?>" placeholder="(11) 99999-0000">
          </label>
        </div>

        <label>
          Endereço de entrega *
          <input type="text" name="endereco" required maxlength="200"
                 value="<?= esc($velho['endereco']) ?>" placeholder="Rua, número, complemento">
        </label>

        <label>
          Cidade / UF *
          <input type="text" name="cidade" required maxlength="80"
                 value="<?= esc($velho['cidade']) ?>" placeholder="São Paulo/SP">
        </label>

        <button class="btn btn-primary btn-block" type="submit">
          Confirmar pedido — <?= brl($total) ?>
        </button>
      </form>

      <!-- RESUMO DO PEDIDO -->
      <aside class="cart-summary card">
        <h2>Resumo do pedido</h2>
        <?php foreach ($itens as $item): $p = $item['produto']; ?>
          <div class="summary-line">
            <span><?= (int) $item['quantidade'] ?>× <?= esc($p['nome']) ?></span>
            <strong><?= brl($p['preco'] * $item['quantidade']) ?></strong>
          </div>
        <?php endforeach; ?>
        <div class="summary-total">
          <span>Total</span>
          <strong><?= brl($total) ?></strong>
        </div>
        <a href="carrinho.php" class="back-link">← Editar carrinho</a>
      </aside>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
