  </main>

  <!-- ================= FOOTER ================= -->
  <footer class="footer" id="rodape">
    <div class="container">
      <div class="footer-top">
        <a href="<?= $base ?>/index.html" class="brand light">
          <span class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 3c-1.8 3.2-6 4.6-6 9a6 6 0 0 0 12 0c0-4.4-4.2-5.8-6-9Z" fill="currentColor" opacity=".9"/>
              <path d="M12 20c-1.2-1.6-2-3.4-2.4-5.4" stroke="#003D31" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="brand-name">Ecolrriga</span>
        </a>

        <nav class="footer-links">
          <a href="<?= $base ?>/index.html">Início</a>
          <a href="<?= $base ?>/index.html#sobre">Sobre</a>
          <a href="<?= $base ?>/loja.php">Loja</a>
          <a href="<?= $base ?>/index.html#projeto">Projeto</a>
          <a href="<?= $base ?>/admin/index.php">Admin</a>
        </nav>
      </div>

      <div class="footer-bottom">
        <p>© 2026 Ecolrriga. Todos os direitos reservados.</p>
        <p class="footer-tags">Tecnologia • Sustentabilidade • Inovação</p>
      </div>
    </div>
  </footer>

  <script src="<?= $base ?>/js/main.js"></script>
  <script>
    // Fecha mensagens de alerta (flash)
    document.querySelectorAll('.flash-close').forEach(btn => {
      btn.addEventListener('click', () => btn.parentElement.remove());
    });
  </script>
</body>
</html>
