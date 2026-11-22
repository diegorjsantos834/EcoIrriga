-- ============================================================================
--  Ecolrriga — Modelo de dados do sistema de loja
--  Banco relacional: MySQL (XAMPP)  |  Script de criação + dados de exemplo
-- ----------------------------------------------------------------------------
--  COMO USAR:
--    1) Abra o phpMyAdmin (http://localhost/phpmyadmin) ou o prompt mysql;
--    2) Importe este arquivo (ou copie e cole) — ele cria o banco `ecolrriga`
--       e popula com dados de demonstração.
--
-- ============================================================================
--  MODELO LÓGICO  (entidades, atributos e relacionamentos)
-- ----------------------------------------------------------------------------
--  ENTIDADES (tabelas) e seus atributos principais:
--
--  1. CATEGORIAS (id, nome, descricao)
--       Agrupa os produtos da loja (ex.: "Sistemas", "Kits").
--
--  2. PRODUTOS (id, categoria_id, nome, descricao, preco, estoque, imagem,
--               ativo, destaque, criado_em, atualizado_em)
--       É o item vendido na loja (sistema completo ou kit).
--
--  3. CLIENTES (id, nome, email, telefone, endereco, cidade, criado_em)
--       Quem compra na loja. Cadastro único pelo e-mail.
--
--  4. PEDIDOS (id, cliente_id, total, status, criado_em)
--       Uma compra realizada. O status acompanha o ciclo de vida do pedido.
--
--  5. ITENS_PEDIDO (id, pedido_id, produto_id, nome_produto, preco_unitario,
--                   quantidade)
--       Tabela de associação (N:N resolvida) entre PEDIDOS e PRODUTOS.
--       Guarda uma FOTO do nome e do preço no momento da compra, para o
--       histórico não mudar se o produto for alterado ou excluído depois.
--
--  RELACIONAMENTOS (cardinalidade):
--
--    CATEGORIAS 1 ──────── N PRODUTOS        (uma categoria tem vários produtos;
--                                             um produto pertence a 1 categoria)
--
--    CLIENTES   1 ──────── N PEDIDOS         (um cliente pode fazer vários
--                                             pedidos; um pedido é de 1 cliente)
--
--    PEDIDOS    1 ──────── N ITENS_PEDIDO    (um pedido tem vários itens;
--                                             um item pertence a 1 pedido)
--
--    PRODUTOS   1 ──────── N ITENS_PEDIDO    (um produto aparece em vários
--                                             pedidos; via tabela associativa)
--
--  INTEGRIDADE REFERENCIAL (chaves estrangeiras / FOREIGN KEY):
--    • produtos.categoria_id  → categorias.id    (exclusão bloqueada se houver
--                                                  produtos na categoria)
--    • pedidos.cliente_id     → clientes.id      (exclusão bloqueada se houver
--                                                  pedidos do cliente)
--    • itens_pedido.pedido_id → pedidos.id       (exclusão em cascata)
--    • itens_pedido.produto_id→ produtos.id      (ao excluir o produto, o
--                                                  histórico fica com NULL)
-- ============================================================================

-- Cria o banco (se ainda não existir) e o seleciona
CREATE DATABASE IF NOT EXISTS ecolrriga
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE ecolrriga;

-- Remove as tabelas na ordem inversa das dependências (filhas primeiro)
DROP TABLE IF EXISTS itens_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS categorias;

-- ----------------------------------------------------------------------------
-- TABELA: categorias  (entidade 1 — "uma categoria tem vários produtos")
-- ----------------------------------------------------------------------------
CREATE TABLE categorias (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(80)  NOT NULL,              -- nome único da categoria
  descricao   VARCHAR(255) NULL,                 -- texto explicativo (opcional)

  PRIMARY KEY (id),
  UNIQUE KEY uq_categoria_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Entidade CATEGORIAS: agrupa os produtos da loja (1:N com produtos)';

-- ----------------------------------------------------------------------------
-- TABELA: produtos  (entidade 2 — item vendido: sistema ou kit)
-- ----------------------------------------------------------------------------
CREATE TABLE produtos (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  categoria_id  INT UNSIGNED    NOT NULL,         -- FK → categorias.id
  nome          VARCHAR(120)    NOT NULL,         -- nome comercial do produto
  descricao     TEXT            NULL,             -- descrição exibida na loja
  preco         DECIMAL(10,2)   NOT NULL,         -- preço em reais (R$)
  estoque       INT UNSIGNED    NOT NULL DEFAULT 0, -- unidades disponíveis
  imagem        VARCHAR(255)    NULL,             -- caminho da foto (opcional)
  ativo         TINYINT(1)      NOT NULL DEFAULT 1, -- 1 = visível na loja
  destaque      TINYINT(1)      NOT NULL DEFAULT 0, -- 1 = aparece em destaque
  criado_em     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_produto_categoria (categoria_id),
  CONSTRAINT fk_produto_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT                          -- não deixa excluir categoria
                                               -- que ainda tem produtos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Entidade PRODUTOS: sistema/kit vendido (N:1 com categorias)';

-- ----------------------------------------------------------------------------
-- TABELA: clientes  (entidade 3 — quem compra)
-- ----------------------------------------------------------------------------
CREATE TABLE clientes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome       VARCHAR(120) NOT NULL,               -- nome completo
  email      VARCHAR(160) NOT NULL,               -- e-mail (contato/login lógico)
  telefone   VARCHAR(20)  NULL,                   -- telefone/WhatsApp
  endereco   VARCHAR(200) NULL,                   -- rua e número
  cidade     VARCHAR(80)  NULL,                   -- cidade/estado
  criado_em  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_cliente_email (email)             -- e-mail único por cliente
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Entidade CLIENTES: comprador da loja (1:N com pedidos)';

-- ----------------------------------------------------------------------------
-- TABELA: pedidos  (entidade 4 — uma compra)
-- ----------------------------------------------------------------------------
CREATE TABLE pedidos (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  cliente_id  INT UNSIGNED  NOT NULL,             -- FK → clientes.id
  total       DECIMAL(10,2) NOT NULL,             -- soma dos itens (R$)
  status      ENUM('pendente','pago','enviado','entregue','cancelado')
                NOT NULL DEFAULT 'pendente',      -- ciclo de vida do pedido
  criado_em   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_pedido_cliente (cliente_id),
  CONSTRAINT fk_pedido_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT                           -- não deixa excluir cliente
                                                -- que já tem pedidos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Entidade PEDIDOS: compra realizada (N:1 com clientes)';

-- ----------------------------------------------------------------------------
-- TABELA: itens_pedido  (tabela associativa PEDIDOS N:N PRODUTOS)
-- ----------------------------------------------------------------------------
CREATE TABLE itens_pedido (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  pedido_id      INT UNSIGNED  NOT NULL,          -- FK → pedidos.id
  produto_id     INT UNSIGNED  NULL,              -- FK → produtos.id (pode ser NULL
                                                  --  se o produto for excluído)
  nome_produto   VARCHAR(120)  NOT NULL,          -- FOTO do nome na compra
  preco_unitario DECIMAL(10,2) NOT NULL,          -- FOTO do preço na compra
  quantidade     INT UNSIGNED  NOT NULL DEFAULT 1,

  PRIMARY KEY (id),
  KEY idx_item_pedido (pedido_id),
  KEY idx_item_produto (produto_id),
  CONSTRAINT fk_item_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,                           -- excluir pedido apaga itens
  CONSTRAINT fk_item_produto
    FOREIGN KEY (produto_id) REFERENCES produtos (id)
    ON UPDATE CASCADE
    ON DELETE SET NULL                           -- histórico fica intacto
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Tabela associativa entre PEDIDOS e PRODUTOS (N:N)';

-- ============================================================================
--  DADOS DE DEMONSTRAÇÃO (seed)
-- ============================================================================

-- Categorias
INSERT INTO categorias (nome, descricao) VALUES
  ('Sistemas', 'Sistema completo de irrigação automática, pronto para instalar'),
  ('Kits',     'Kits com peças e componentes para montar ou expandir o sistema');

-- Produtos (categoria 1 = Sistemas, categoria 2 = Kits)
INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, ativo, destaque) VALUES
  (1, 'Ecolrriga Sistema Completo',
      'Sistema pronto: Arduino Uno, sensor de umidade, relé, LCD I2C, bomba d''água, bateria 18650 e painel solar. Irriga sozinho conforme a umidade do solo.',
      349.90, 12, 1, 1),
  (1, 'Ecolrriga Sistema Solar Pro',
      'Versão profissional com painel de maior área, duplo sensor de umidade e controle de duas zonas de irrigação.',
      599.90, 5, 1, 1),
  (2, 'Kit Essencial',
      'Sensor de umidade + relé de 1 canal + bomba 5V. Ideal para quem já tem o Arduino e quer começar.',
      189.90, 20, 1, 1),
  (2, 'Kit Família',
      'Tudo do Kit Essencial + LCD 16x2 I2C + 2 sondas de reposição. Exibe a umidade em tempo real.',
      279.90, 15, 1, 0),
  (2, 'Kit Multi-zona',
      'Controla até 3 áreas de irrigação: 3 relés, 3 sensores e válvulas. Para quintais e hortas maiores.',
      459.90, 7, 1, 1),
  (2, 'Kit Reposição de Sensores',
      'Pacote com 2 sondas resistivas de umidade de reposição (as sondas têm vida útil limitada).',
      69.90, 40, 1, 0);

-- Clientes
INSERT INTO clientes (nome, email, telefone, endereco, cidade) VALUES
  ('Mariana Alves',   'mariana.alves@email.com',  '(11) 98888-1234', 'Rua das Flores, 120', 'São Paulo/SP'),
  ('Carlos Ferreira', 'carlos.ferreira@email.com','(21) 97777-5678', 'Av. Brasil, 45',      'Rio de Janeiro/RJ'),
  ('Juliana Santos',  'juliana.santos@email.com', '(31) 96666-9012', 'Rua Goiás, 780',      'Belo Horizonte/MG');

-- Pedidos (com itens e preço congelado no momento da compra)
INSERT INTO pedidos (cliente_id, total, status) VALUES
  (1, 539.80, 'entregue'),
  (2, 189.90, 'pago'),
  (3, 349.90, 'pendente');

INSERT INTO itens_pedido (pedido_id, produto_id, nome_produto, preco_unitario, quantidade) VALUES
  (1, 1, 'Ecolrriga Sistema Completo', 349.90, 1),
  (1, 3, 'Kit Essencial',              189.90, 1),
  (2, 3, 'Kit Essencial',              189.90, 1),
  (3, 1, 'Ecolrriga Sistema Completo', 349.90, 1);
