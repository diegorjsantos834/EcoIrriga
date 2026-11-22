<?php
/**
 * ============================================================================
 *  includes/header.php — cabeçalho padrão de todas as páginas PHP
 * ----------------------------------------------------------------------------
 *  Variáveis esperadas (definidas pela página que inclui este arquivo):
 *    $titulo  → título da aba do navegador
 *    $pagina  → item de menu ativo: 'inicio' | 'loja' | 'admin'
 * ============================================================================
 */

require_once __DIR__ . '/funcoes.php';

$titulo  = $titulo  ?? 'Ecolrriga';
$pagina  = $pagina  ?? '';
$contadorCarrinho = carrinhoItens();

/**
 * Prefixo de caminho base: as páginas em /admin/ precisam voltar 1 pasta
 * para encontrar css/, js/ e as páginas da loja (ex.: ../loja.php).
 */
$base = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'admin') ? '..' : '.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($titulo) ?> — Ecolrriga</title>
  <meta name="description" content="Loja Ecolrriga: sistemas e kits de irrigação automática." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $base ?>/css/styles.css" />
  <link rel="stylesheet" href="<?= $base ?>/css/loja.css" />
</head>
<body>

  <!-- ================= NAVBAR ================= -->
  <header class="navbar scrolled" id="navbar">
    <div class="container nav-inner">
      <a href="<?= $base ?>/index.html" class="brand">
        <span class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3c-1.8 3.2-6 4.6-6 9a6 6 0 0 0 12 0c0-4.4-4.2-5.8-6-9Z" fill="currentColor" opacity=".9"/>
            <path d="M12 20c-1.2-1.6-2-3.4-2.4-5.4" stroke="#fff" stroke-width="1.4" stroke-linecap="round"/>
          </svg>
        </span>
        <span class="brand-name">Ecolrriga</span>
      </a>

      <nav class="nav-links" id="navLinks">
        <a href="<?= $base ?>/index.html"        class="nav-link <?= $pagina === 'inicio' ? 'active' : '' ?>">Início</a>
        <a href="<?= $base ?>/index.html#sobre"  class="nav-link">Sobre</a>
        <a href="<?= $base ?>/loja.php"          class="nav-link <?= $pagina === 'loja' ? 'active' : '' ?>">Loja</a>
        <a href="<?= $base ?>/index.html#projeto" class="nav-link">Projeto</a>
        <a href="<?= $base ?>/admin/index.php"   class="nav-link <?= $pagina === 'admin' ? 'active' : '' ?>">Admin</a>
      </nav>

      <div class="nav-actions">
        <a href="<?= $base ?>/carrinho.php" class="btn btn-github cart-btn" aria-label="Carrinho de compras">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.55L21 8H6"/>
            <circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>
          </svg>
          <span>Carrinho</span>
          <?php if ($contadorCarrinho > 0): ?>
            <b class="cart-count"><?= $contadorCarrinho ?></b>
          <?php endif; ?>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Abrir menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <main class="page-main">
    <div class="container">
      <?php foreach (pegarFlashes() as $f): ?>
        <div class="flash flash-<?= esc($f['tipo']) ?>" role="status">
          <?= esc($f['mensagem']) ?>
          <button type="button" class="flash-close" aria-label="Fechar">&times;</button>
        </div>
      <?php endforeach; ?>
    </div>
