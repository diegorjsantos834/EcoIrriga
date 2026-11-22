# Ecolrriga — Landing Page + Loja (HTML/CSS/JS + PHP/MySQL)

Landing page do projeto **Ecolrriga** (Sistemas Embarcados) em HTML/CSS/JS puro,
com **visualizador 3D do case** (Three.js) e uma **loja em PHP + MySQL** com
CRUD completo de produtos e clientes.

## Como rodar

Os STLs são carregados via `fetch`, então a página precisa ser servida por um servidor
local (abrir direto pelo `file://` não funciona — o navegador bloqueia por CORS).

### Opção 1 — servidor incluso (sem dependências)

```powershell
powershell -ExecutionPolicy Bypass -File serve.ps1 -Port 8000
```

Depois abra <http://localhost:8000>.

### Opção 2 — qualquer outro servidor

```bash
python -m http.server 8000     # Python
npx serve .                    # Node
```

### Opção 3 — servidor PHP com banco de dados (loja + painel)

A loja e o painel admin são páginas **PHP + MySQL** e precisam do XAMPP ligado:

```powershell
powershell -ExecutionPolicy Bypass -File serve-php.ps1
```

O script (1) liga o MySQL do XAMPP, (2) importa `db/ecolrriga.sql` e
(3) sobe o servidor em <http://localhost:8000>:

| Página | Endereço |
|--------|----------|
| Landing page | <http://localhost:8000/index.html> |
| Loja (vitrine) | <http://localhost:8000/loja.php> |
| Carrinho | <http://localhost:8000/carrinho.php> |
| Painel admin | <http://localhost:8000/admin/index.php> |

## Requisitos do trabalho: banco + CRUD

### Banco de dados (relacional)

Script completo em [`db/ecolrriga.sql`](db/ecolrriga.sql), com o **modelo lógico
documentado no próprio arquivo** (entidades, atributos, cardinalidades e chaves
estrangeiras) e comentários em todas as colunas.

| Entidade | Relacionamento |
|----------|----------------|
| `categorias` | 1 ─ N `produtos` |
| `produtos` | N ─ N `pedidos` (via `itens_pedido`) |
| `clientes` | 1 ─ N `pedidos` |
| `pedidos` | 1 ─ N `itens_pedido` |

Todas as queries usam **PDO com prepared statements** (segurança contra SQL
Injection) e a saída HTML passa por `esc()` (proteção contra XSS).

### Operações CRUD (2 entidades principais)

| Entidade | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| **Produtos** | `admin/produto_form.php` | `admin/produtos.php` | `admin/produto_form.php?id=` | `admin/produtos.php` (POST) |
| **Clientes** | `admin/cliente_form.php` | `admin/clientes.php` | `admin/cliente_form.php?id=` | `admin/clientes.php` (POST) |

Complementos: `pedidos` (leitura + atualização de status) e o checkout, que
grava **clientes + pedidos + itens_pedido dentro de uma transação** (ou tudo
grava, ou nada é gravado) e dá baixa no estoque.

## Estrutura

```
index.html          → landing page (hero, funcionalidades, previsão, 3D, sobre)
css/styles.css      → estilo com a paleta do design (#004E3E, #818C46, #DDDDBF, #E9A12E…)
css/loja.css        → estilo da loja e do painel admin
js/main.js          → menu mobile, link ativo no scroll, animações, gráfico (canvas)
js/viewer.js        → viewer 3D (Three.js + OrbitControls + STLLoader)
models/             → os 4 STLs do case
serve.ps1           → servidor estático (só a landing page)
serve-php.ps1       → servidor PHP + MySQL (loja e admin)

db/ecolrriga.sql    → modelo lógico comentado + script físico + dados de exemplo
config/conexao.php  → conexão PDO (credenciais centralizadas)
includes/funcoes.php→ sessão, esc(), brl(), flashes e carrinho
includes/header.php  → cabeçalho/navbar padrão das páginas PHP
includes/footer.php  → rodapé padrão das páginas PHP

loja.php            → vitrine (READ + busca e filtro por categoria)
produto.php         → ficha do produto (READ)
carrinho.php        → carrinho em sessão (adicionar/atualizar/remover)
checkout.php        → finaliza a compra (CREATE com transação)
pedido.php          → confirmação do pedido (READ com JOINs)

admin/index.php     → dashboard (consultas agregadas COUNT/SUM)
admin/produtos.php      → CRUD de produtos (listar/excluir/ativar)
admin/produto_form.php  → CRUD de produtos (criar/editar)
admin/clientes.php      → CRUD de clientes (listar/excluir)
admin/cliente_form.php  → CRUD de clientes (criar/editar)
admin/pedidos.php       → pedidos (ler + mudar status)
```

## O modelo 3D

Carrega as 4 peças e monta a sequência **caixa → estantes (dentro) → arco → tampa**:

| Peça     | Arquivo                | Cor      |
|----------|------------------------|----------|
| Caixa    | `Caixa Case SE.stl`    | #004E3E  |
| Estantes | `Estantes Case SE.stl` | #818C46  |
| Arco     | `Arco Case SE.stl`     | #9EB2A4  |
| Tampa    | `Tampa Case SE.stl`    | #E9A12E  |

Recursos: órbita com damping + rotação automática, sombras, **slider de explosão**,
**chips para ligar/desligar cada peça**, botões de resetar vista e tela cheia,
dots que acompanham a rotação e pausa da rotação enquanto o usuário arrasta.
