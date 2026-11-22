# 🎬 Roteiro de vídeo — Projeto Ecolrriga
### Versão trabalhada · duração alvo: 4 min 30 s

> **Como usar:** cada cena traz (1) o tempo exato, (2) o passo a passo do que
> aparece na tela, (3) a **narração pronta para ler** e (4) a edição/legendas.
> O texto da narração foi escrito para ser lido em voz alta, no ritmo de uma
> apresentação tranquila (~140 palavras por minuto).

**Requisitos técnicos que precisam aparecer (⭐ = obrigatório):**
- ⭐ Banco de dados relacional + **modelo lógico** documentado
- ⭐ **CRUD completo em 2 entidades** principais
- ⭐ **PHP + MySQL** (com comentários estruturados no código)

---

## 🎯 Antes de gravar — checklist

- [ ] Rode `serve-php.ps1` e confirme que <http://localhost:8000> abre
- [ ] Abra **5 abas antes de começar** (evita esperar carregar na gravação):
  1. `index.html` · 2. `loja.php` · 3. `admin/index.php` · 4. phpMyAdmin → banco `ecolrriga` · 5. `admin/produtos.php`
- [ ] Faça **uma compra de teste** para o carrinho começar vazio e você não errar o fluxo
- [ ] Deixe o produto nº 1 **sem edição pendente** (o formulário já abre preenchido)
- [ ] Notificações do Windows desligadas · navegador em tela cheia (F11) · 1920×1080
- [ ] Microfone testado · luz na frente (sem brilho na tela)

---

## CENA 1 — Abertura
**⏱ 0:00 – 0:20 (20 s) · Requisito: contexto**

**Na tela:**
1. Navegador abrindo `localhost:8000` — a landing page carrega com a animação de entrada.
2. Mouse entra e rola devagar do hero até a seção "Funcionalidades".
3. Pausa breve no card **"Irrigação Automatizada"**.

**Narração:**
> "Este é o **Ecolrriga** — um sistema de irrigação automática que mede a umidade do solo e rega a vegetação apenas quando é realmente necessário, economizando água.
>
> Neste vídeo eu vou mostrar o projeto completo: o sistema embarcado, o modelo 3D do case, a loja online e, em especial, a parte técnica — **o banco de dados relacional e o ciclo CRUD feito em PHP com MySQL**."

**Edição:** legenda inferior com o logo + "Projeto Sistemas Embarcados · 2026". Zoom leve no hero no primeiro segundo.

---

## CENA 2 — O sistema embarcado
**⏱ 0:20 – 0:55 (35 s) · Requisito: contexto do hardware**

**Na tela:**
1. Clicar em **"Projeto"** no menu → o carrossel aparece com o esquema elétrico.
2. Mouse **apontando** (sem clicar) na sequência: Painel Solar → Módulo de Carga → Bateria → Arduino.
3. Arrastar o carrossel para o slide 2 (foto do Fritzing) e voltar.
4. Focar no trecho sensor → relé → bomba.

**Narração:**
> "O hardware funciona assim: o **painel solar** carrega a bateria 18650 pelo módulo de carga TP4056, que alimenta o Arduino.
>
> O **sensor de umidade** fica enterrado no solo e envia um sinal analógico para o Arduino. Quando a umidade cabaixo do limite que programamos, o Arduino aciona o **relé**, que liga a **bomba d'água** — e o **LCD** mostra a umidade em tempo real para quem estiver por perto.
>
> Ou seja: ele irriga sozinho, só quando precisa."

**Edição:** seta animada acompanhando o fluxo de energia (painel → bateria → Arduino). Legenda: **"Sensor → Arduino → Relé → Bomba"**.

---

## CENA 3 — Visualizador 3D
**⏱ 0:55 – 1:20 (25 s) · Requisito: diferencial do projeto**

**Na tela:**
1. Rolar até o card **"Modelo 3D do Projeto"**.
2. Arrastar o modelo para girar (2–3 s).
3. Puxar o **slider de Explosão** até ~70% e voltar ao zero.
4. Clicar em 2 chips (ex.: Tampa e Arco) desligando e religando a peça.

**Narração:**
> "Este é o **case que imprimimos em 3D**, com as quatro peças: caixa, estantes, arco e tampa.
>
> O visualizador roda direto no navegador — dá para girar, **separar as peças com o slider de explosão** e ligar ou desligar cada uma individualmente para ver como tudo se encaixa."

**Edição:** sem cortes durante o arrasto (mostrar fluidez). Legenda: **"4 peças · STL · Three.js"**.

---

## CENA 4 — A loja online
**⏱ 1:20 – 2:00 (40 s) · Requisito: contexto do CRUD (a leitura acontece aqui)**

**Na tela:**
1. Clicar em **"Loja"** no menu → vitrine carrega com os 6 produtos.
2. Clicar no filtro **"Kits"** e depois em **"Todos"**.
3. Digitar **"sensor"** na busca → resultado filtrado → limpar.
4. Clicar em **"Adicionar"** no "Ecolrriga Sistema Completo".
5. Abrir o **Carrinho** → mostrar quantidade e total → clicar em **"Finalizar compra"**.
6. Preencher o formulário (nome, e-mail, endereço) → **"Confirmar pedido"**.
7. Parar na tela de **"Pedido confirmado!"**.

**Narração:**
> "O projeto tem uma loja feita em **PHP**. A vitrine **lê** os produtos direto do banco, com busca e filtro por categoria — tudo através de consultas parametrizadas.
>
> Ao adicionar ao carrinho e finalizar a compra, acontece o mais interessante: dentro de **uma transação**, o sistema grava o **cliente**, cria o **pedido** com os itens, **baixa o estoque** e limpa o carrinho. Se qualquer coisa falhar, **nada é gravado** — o banco nunca fica pela metade."

**Edição:** zoom no total do pedido; ao mencionar "transação", legenda em destaque: **"TRANSAÇÃO: cliente + pedido + itens + estoque"**.

---

## CENA 5 — ⭐ Banco de dados e modelo lógico
**⏱ 2:00 – 2:50 (50 s) · Requisito obrigatório**

**Na tela:**
1. Trocar para a aba do **phpMyAdmin** → abrir o banco `ecolrriga`.
2. Clicar em **categorias** → depois **produtos** → depois **clientes** → **pedidos** → **itens_pedido** (uma por vez, pausando ~2 s).
3. Em `produtos`, abrir a aba **"Estrutura"** e passar o mouse nas colunas `categoria_id` e `preco`.
4. Mostrar a aba **"Relações visuais"** (ou o print do modelo lógico) — o diagrama com as 5 tabelas.
5. Voltar ao VS Code / editor e abrir `db/ecolrriga.sql`, rolando lentamente o cabeçalho comentado.

**Narração:**
> "Agora a parte técnica. O banco é **relacional, em MySQL**, e o modelo lógico tem **cinco entidades**:
>
> • **categorias** e **produtos** — um produto pertence a uma categoria, relação **um para muitos**;
> • **clientes** e **pedidos** — um cliente pode fazer vários pedidos;
> • e **itens_pedido**, a **tabela associativa** que liga pedidos a produtos — porque um pedido leva vários produtos, e um produto aparece em vários pedidos.
>
> Os relacionamentos estão protegidos por **chaves estrangeiras**: por exemplo, não dá para excluir uma categoria que ainda tem produtos. E todo o modelo — entidades, atributos e cardinalidades — está **documentado em comentários no próprio script SQL**, no arquivo `ecolrriga.sql`."

**Edição:** ao citar cada entidade, legenda na tela: `categorias 1—N produtos` · `clientes 1—N pedidos` · `pedidos N—N produtos (itens_pedido)`. Zoom no cabeçalho comentado do arquivo `.sql`.

---

## CENA 6 — ⭐ CRUD de Produtos (a cena mais importante)
**⏱ 2:50 – 3:50 (60 s) · Requisito obrigatório**

**Na tela (siga a ordem exata):**
1. Abrir `admin/index.php` → mostrar os cards com as contagens (2 s).
2. Clicar em **"Gerenciar CRUD →"** de Produtos.
3. **CREATE** → **"+ Novo produto"** → preencher:
   - Nome: `Kit Teste Vídeo` · Categoria: `Kits` · Preço: `89,90` · Estoque: `10`
   - Descrição: `Kit criado durante a gravação.`
   - Marcar **"Visível na loja"** → **"Cadastrar produto"**
   → Aparece a mensagem "Produto criado com sucesso." e o item na lista.
4. **READ** → apontar a listagem: "todos os produtos vêm do banco, com JOIN na categoria".
5. **UPDATE** → clicar no **lápis** da linha recém-criada → mudar Preço para `99,90` → **"Salvar alterações"** → valor atualizado na tabela.
6. **DELETE** → clicar na **lixeira** → confirmar no pop-up → a linha some.
7. Terminar apontando a coluna **Ações** (olho, lápis, status, lixeira).

**Narração:**
> "Vamos ao **ciclo CRUD completo** — Create, Read, Update e Delete — na primeira entidade principal: **produtos**.
>
> **Create:** clico em 'novo produto', preencho o formulário — nome, categoria, preço e estoque — e salvo. O registro é inserido no banco e já aparece na listagem.
>
> **Read:** esta listagem aqui é uma consulta com *join* na tabela de categorias — é ela que traz os dados para a tela.
>
> **Update:** clico no lápis, altero o preço e salvo; o registro é atualizado no banco.
>
> E **Delete:** clico na lixeira, confirmo, e o produto é excluído de verdade.
>
> Todo o formulário **valida os dados antes de enviar** e o backend usa **PDO com prepared statements**, que é o que protege contra *SQL Injection*."

**Edição:** um zoom (~150%) em cada botão no momento do clique. Legenda sequencial na parte inferior trocando a cada operação: **`1 · CREATE` → `2 · READ` → `3 · UPDATE` → `4 · DELETE`**.

---

## CENA 7 — ⭐ CRUD de Clientes (a segunda entidade)
**⏱ 3:50 – 4:20 (30 s) · Requisito obrigatório**

**Na tela:**
1. Menu → **"Clientes"** → apontar a listagem (nome, e-mail, pedidos, total gasto).
2. **CREATE** → **"+ Novo cliente"** → preencher nome, e-mail, telefone, endereço → salvar.
3. **UPDATE** → lápis → trocar o telefone → salvar.
4. **DELETE** → lixeira → confirmar.
5. Mostrar rapidinho o formulário de novo, agora vazio, para fechar a ideia de ciclo.

**Narração:**
> "O mesmo ciclo completo se repete na **segunda entidade: clientes**.
>
> Aqui eu **crio** um cliente, **leio** a listagem — que já mostra quantos pedidos cada um tem, através de uma agregação com *join* — **edito** os dados cadastrais e **excluo** o registro.
>
> Detalhe importante: se um cliente já tem pedidos, o banco **bloqueia a exclusão** pela chave estrangeira — é a integridade referencial garantindo que o histórico não se perca."

**Edição:** legenda **`CLIENTES · CRUD completo`**. Se der tempo, mostrar na tela o erro amigável ao tentar excluir um cliente com pedidos.

---

## CENA 8 — Pedidos, transação e integridade
**⏱ 4:20 – 4:45 (25 s) · Requisito: reforço**

**Na tela:**
1. Menu → **"Pedidos"** → expandir os itens de um pedido (clique no "1 item(ns)").
2. Mudar o **status** de `pendente` para `pago` → clicar **OK** → badge atualiza.
3. Voltar ao **dashboard** e parar no card de **Faturamento**.

**Narração:**
> "No painel de pedidos eu **leio** a tabela associativa — os itens de cada pedido — e **atualizo** o status conforme a compra avança.
>
> Repare que o **nome e o preço do produto ficam congelados dentro do pedido**: mesmo que o produto seja alterado ou excluído depois, o histórico continua fiel ao que foi comprado. É a integridade referencial em ação."

**Edição:** zoom no badge de status mudando de "pendente" para "pago". Legenda: **"Snapshot de preço + chave estrangeira"**.

---

## CENA 9 — Encerramento
**⏱ 4:45 – 5:05 (20 s)**

**Na tela:**
1. Voltar para `index.html` → rolar até o topo → parar no hero.
2. (Opcional) dividir a tela mostrando 4 miniaturas: home, loja, phpMyAdmin, painel.

**Narração:**
> "Então, resumindo o que foi apresentado: **modelo lógico e banco relacional em MySQL** com cinco entidades e chaves estrangeiras; **ciclo CRUD completo em duas entidades** — produtos e clientes — mais o registro de pedidos em **transação**; e tudo isso escrito em **PHP**, com os comentários do modelo documentados no próprio código.
>
> Este é o Ecolrriga — tecnologia a favor do verde. Obrigado."

**Edição:** legenda final com os 3 requisitos em checklist marcado:
`✔ Banco relacional + modelo lógico` · `✔ CRUD em 2 entidades` · `✔ PHP + MySQL`

---

## ✂️ Se precisar encurtar

| Versão | O que fazer |
|---|---|
| **2 min 30 s** | Corte as cenas 2 (hardware) e 3 (3D) para 10 s cada, e a cena 8 inteira. Narração da cena 1 já contextualiza o resto. |
| **1 min** | Mantenha só: Cenas 4 (loja/compra), 5 (banco) e 6 (CRUD produtos). É o mínimo que cobre os 3 requisitos. |
| **5 min** | Acrescente uma cena mostrando o código: abrir `checkout.php` e destacar o `beginTransaction()` / `commit()`, e abrir `conexao.php` mostrando o PDO. |

## 🎙️ Dicas de narração

- Grave a **voz primeiro** e depois a tela sincronizada — ou grave a tela e regrave a voz por cima (mais fácil acertar o ritmo).
- Fale **mais devagar** nas cenas 5 e 6: são as que o avaliador vai assistir com mais atenção.
- Deixe o mouse **pausado 1 segundo** sobre cada botão antes de clicar — quem assiste precisa ver a ação.
- Errou a fala? Faça uma pausa longa e repeta a frase inteira: você corta o silêncio depois.
- Evite "é tipo", "basicamente", "coisa assim" — leia o texto como está escrito.
