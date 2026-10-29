# Login Noshi

Sistema de cadastro e login em PHP + MySQL, com visual dark minimalista.
Serve como base para qualquer página que precise de usuário logado: um blog, uma aplicação simples, um jogo com ranking e assim por diante.

## Sumário

1. [Estrutura do projeto](#estrutura-do-projeto)
2. [Como funciona](#como-funciona)
3. [Instalação e testes](#instalação-e-testes)
4. [Segurança já implementada](#segurança-já-implementada)
5. [Como integrar páginas depois do login](#como-integrar-páginas-depois-do-login)
6. [Ideia 1: Blog](#ideia-1-blog)
7. [Ideia 2: Aplicação simples (lista de tarefas)](#ideia-2-aplicação-simples-lista-de-tarefas)
8. [Ideia 3: Jogo com ranking](#ideia-3-jogo-com-ranking)
9. [Melhorias recomendadas](#melhorias-recomendadas)
10. [Problemas comuns](#problemas-comuns)

---

## Estrutura do projeto

```
login-noshi/
├── banco.sql            # Cria o banco e a tabela de usuários
├── conexao.php          # Conexão com o MySQL (usada por todos os PHP)
├── teste-conexao.php    # Testa a conexão (apague depois dos testes)
├── formulario.html      # Tela de cadastro
├── cadastro.php         # Recebe o cadastro, valida e grava no banco
├── teladelogin.html     # Tela de login
├── login.php            # Confere e-mail e senha, abre a sessão
├── painel.php           # Página protegida (só abre para quem está logado)
├── logout.php           # Encerra a sessão
└── style.css            # Estilo dark compartilhado pelas telas
```

## Como funciona

```
formulario.html ──POST──> cadastro.php ──> grava em `usuarios` ──> teladelogin.html
                                                                        │
teladelogin.html ──POST──> login.php ──> confere senha ──> painel.php   │
                                │                                       │
                                └── erro ───────────────────────────────┘
```

1. **Cadastro.** O formulário envia os dados por `POST` para o `cadastro.php`. O PHP valida tudo de novo no servidor, transforma a senha em hash com `password_hash()` e grava com *prepared statement*. Se der certo, redireciona para o login.
2. **Login.** O `login.php` busca o usuário pelo e-mail e confere a senha com `password_verify()`. Se estiver certa, abre uma sessão (`$_SESSION['usuario_id']`) e redireciona para o painel.
3. **Página protegida.** Todo arquivo PHP que começa checando `$_SESSION['usuario_id']` só abre para quem fez login. Esse é o ponto de integração para tudo que você quiser construir depois.
4. **Mensagens.** Erros e avisos voltam por parâmetros na URL (`?erro=1`, `?cadastro=ok`) e um pequeno script mostra o texto na tela.

## Instalação e testes

1. Instale o **XAMPP** (ou Laragon) e inicie o **Apache** e o **MySQL**.
2. Copie a pasta `login-noshi` para `C:\xampp\htdocs\`.
3. Em `http://localhost/phpmyadmin`, aba **Importar**, envie o `banco.sql`.
4. Abra `http://localhost/login-noshi/teste-conexao.php`. Deve aparecer "Conexão bem-sucedida!".
5. Abra `http://localhost/login-noshi/formulario.html`, crie uma conta e entre pela tela de login.

> Sempre acesse pelo endereço `http://localhost/...`. Abrir o HTML com dois cliques (`file://...`) não executa PHP.

Se o seu MySQL tiver usuário e senha diferentes de `root` sem senha, ajuste as variáveis no `conexao.php`.

## Segurança já implementada

| Item | Onde |
| --- | --- |
| Senha guardada como hash, nunca em texto puro | `cadastro.php` (`password_hash`) |
| Conferência da senha | `login.php` (`password_verify`) |
| Proteção contra SQL Injection | Prepared statements em todas as consultas |
| Validação no servidor, além da validação do navegador | `cadastro.php` |
| E-mail único no banco | Índice `UNIQUE` em `banco.sql` |
| Mesma mensagem para e-mail ou senha errados | `login.php` (não revela quais e-mails existem) |
| ID de sessão renovado após o login | `session_regenerate_id(true)` |
| Saída escapada contra XSS | `htmlspecialchars` em `painel.php` |
| Erros do banco vão para o log, não para a tela | `conexao.php` |

## Como integrar páginas depois do login

### Passo 1: criar um "porteiro" reutilizável

Crie o arquivo `auth.php`:

```php
<?php
session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: teladelogin.html?acesso=1');
    exit;
}

// Variáveis disponíveis em qualquer página que incluir este arquivo
$usuarioId   = (int) $_SESSION['usuario_id'];
$usuarioNome = $_SESSION['usuario_nome'];
```

### Passo 2: incluir o porteiro no topo de cada página protegida

```php
<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha página</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="box largo">
        <h1>Olá, <?= htmlspecialchars($usuarioNome, ENT_QUOTES, 'UTF-8') ?></h1>
        <!-- seu conteúdo aqui -->
    </main>
</body>
</html>
```

### Passo 3: ligar tudo ao painel

Em `painel.php`, adicione links para as novas páginas. O `painel.php` vira o menu principal do sistema.

### Regras de ouro

- **Toda página protegida precisa incluir `auth.php`**, inclusive as que só recebem dados (por exemplo, `salvar-algo.php`). Esconder o link não protege nada.
- **Sempre filtre os dados pelo usuário logado.** Em consultas, use `WHERE usuario_id = ?` com `$usuarioId`, nunca um ID vindo da URL ou do formulário.
- **Escape tudo que sai na tela** com `htmlspecialchars`.
- **Use prepared statements** em qualquer consulta que receba dados externos.
- Se as páginas ficarem em subpastas, ajuste os caminhos: `require __DIR__ . '/../auth.php';`.

---

## Ideia 1: Blog

### Tabela

```sql
USE formulario_noshi;

CREATE TABLE IF NOT EXISTS posts (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  INT UNSIGNED NOT NULL,
  titulo      VARCHAR(150) NOT NULL,
  conteudo    TEXT NOT NULL,
  criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Páginas sugeridas

| Arquivo | Função |
| --- | --- |
| `blog.php` | Lista os posts, do mais novo para o mais antigo |
| `novo-post.php` | Formulário + gravação do post |
| `post.php?id=3` | Mostra um post completo |
| `editar-post.php` / `excluir-post.php` | Só o autor pode usar |

### Exemplo: listar posts (`blog.php`)

```php
<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

$resultado = $conexao->query(
    'SELECT p.id, p.titulo, p.criado_em, u.nome AS autor
       FROM posts p
       JOIN usuarios u ON u.id = p.usuario_id
   ORDER BY p.criado_em DESC'
);
?>
<!-- dentro do <body> -->
<?php while ($post = $resultado->fetch_assoc()): ?>
    <article>
        <h2>
            <a href="post.php?id=<?= (int) $post['id'] ?>">
                <?= htmlspecialchars($post['titulo'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </h2>
        <p>Por <?= htmlspecialchars($post['autor'], ENT_QUOTES, 'UTF-8') ?>
           em <?= date('d/m/Y', strtotime($post['criado_em'])) ?></p>
    </article>
<?php endwhile; ?>
```

### Exemplo: só o autor pode excluir (`excluir-post.php`)

```php
<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: blog.php'); exit; }

$id = (int) ($_POST['id'] ?? 0);

// O "AND usuario_id = ?" garante que ninguém apague post de outra pessoa
$stmt = $conexao->prepare('DELETE FROM posts WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $id, $usuarioId);
$stmt->execute();

header('Location: blog.php');
exit;
```

> Exclusão deve ser sempre por `POST`, nunca por link simples, para não ser disparada sem querer ou por terceiros.

---

## Ideia 2: Aplicação simples (lista de tarefas)

### Tabela

```sql
USE formulario_noshi;

CREATE TABLE IF NOT EXISTS tarefas (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  INT UNSIGNED NOT NULL,
  descricao   VARCHAR(200) NOT NULL,
  concluida   TINYINT(1) NOT NULL DEFAULT 0,
  criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Páginas sugeridas

- `tarefas.php`: lista as tarefas do usuário e tem o campo para adicionar.
- `tarefa-salvar.php`: recebe a nova tarefa por `POST` e grava.
- `tarefa-alternar.php`: marca ou desmarca como concluída.
- `tarefa-excluir.php`: remove.

A regra central é a mesma do blog: **toda consulta termina com `AND usuario_id = ?`**. Assim cada pessoa só enxerga e altera as próprias tarefas.

```php
// Listar só as tarefas de quem está logado
$stmt = $conexao->prepare(
    'SELECT id, descricao, concluida FROM tarefas WHERE usuario_id = ? ORDER BY criado_em DESC'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$tarefas = $stmt->get_result();
```

A mesma estrutura serve para outros apps: lista de compras, controle de gastos, agenda, anotações, lista de clientes.

---

## Ideia 3: Jogo com ranking

O jogo roda no navegador (HTML + JavaScript) e o PHP só guarda a pontuação.

### Tabela

```sql
USE formulario_noshi;

CREATE TABLE IF NOT EXISTS pontuacoes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  INT UNSIGNED NOT NULL,
  jogo        VARCHAR(40) NOT NULL,
  pontos      INT UNSIGNED NOT NULL,
  criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_jogo_pontos (jogo, pontos),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Enviar a pontuação ao fim da partida (JavaScript)

```javascript
async function salvarPontuacao(pontos) {
    const resposta = await fetch('salvar-pontuacao.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ jogo: 'jogo-da-cobrinha', pontos })
    });
    return resposta.ok;
}
```

### Receber e gravar (`salvar-pontuacao.php`)

```php
<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

$dados  = json_decode(file_get_contents('php://input'), true);
$jogo   = substr((string) ($dados['jogo'] ?? ''), 0, 40);
$pontos = (int) ($dados['pontos'] ?? -1);

if ($jogo === '' || $pontos < 0 || $pontos > 1000000) {
    http_response_code(400);
    exit;
}

$stmt = $conexao->prepare('INSERT INTO pontuacoes (usuario_id, jogo, pontos) VALUES (?, ?, ?)');
$stmt->bind_param('isi', $usuarioId, $jogo, $pontos);
$stmt->execute();

http_response_code(204);
```

### Mostrar o ranking (top 10)

```sql
SELECT u.nome, MAX(p.pontos) AS melhor
  FROM pontuacoes p
  JOIN usuarios u ON u.id = p.usuario_id
 WHERE p.jogo = ?
 GROUP BY u.id, u.nome
 ORDER BY melhor DESC
 LIMIT 10;
```

> **Atenção:** como o jogo roda no navegador, qualquer pessoa pode enviar uma pontuação falsa pelo console. Para um projeto entre amigos tudo bem. Para um ranking sério, o servidor precisa validar a partida (por exemplo, calculando o resultado a partir das jogadas enviadas).

Jogos que combinam bem com essa estrutura: cobrinha, jogo da memória, quiz, forca, digitação rápida, campo minado.

---

## Melhorias recomendadas

Em ordem de prioridade para quem vai publicar na internet:

1. **Proteção CSRF.** Um site malicioso pode fazer o navegador do usuário enviar formulários sem ele perceber. A solução é gerar um token por sessão, colocá-lo em um campo oculto e conferir no PHP. Para isso, os formulários precisam virar `.php` (para imprimir o token).

   ```php
   // Ao abrir a sessão
   if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

   // No formulário:  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

   // No PHP que recebe:
   if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { http_response_code(403); exit; }
   ```

2. **Limite de tentativas de login.** Sem isso, alguém pode testar milhares de senhas. Guarde as falhas por e-mail e IP em uma tabela (`tentativas_login`) e bloqueie por alguns minutos depois de 5 erros.
3. **HTTPS.** Sem ele, e-mail e senha trafegam sem criptografia. Ao publicar, ative HTTPS e configure o cookie de sessão como `secure`, `httponly` e `samesite=Lax`.
4. **Credenciais fora do código.** Mova usuário e senha do banco para um arquivo fora da pasta pública (ou variáveis de ambiente) e crie um usuário MySQL com permissões só neste banco, em vez de usar `root`.
5. **Recuperação de senha.** Envio de link por e-mail com token de uso único e validade curta.
6. **Confirmação de e-mail**, para evitar cadastros com e-mails de outras pessoas.
7. **Papéis de usuário** (por exemplo, `admin` e `usuario`): uma coluna `papel` na tabela e uma checagem extra nas páginas de administração.
8. **Página de perfil**, para o usuário editar os dados e trocar a senha.
9. **Expiração da sessão por inatividade**, registrando o horário do último acesso em `$_SESSION`.
10. **Telefone com máscara** e **busca de endereço por CEP** (API ViaCEP) no formulário.
11. **Organização:** quando passar de 10 arquivos, separe em pastas (`/public`, `/includes`, `/app`) e mantenha só o que o navegador precisa acessar em `/public`.

## Problemas comuns

| Sintoma | Causa provável | Solução |
| --- | --- | --- |
| `Not Found` ao abrir um arquivo | Pasta fora de `htdocs` ou arquivo com outro nome | Confira o caminho `C:\xampp\htdocs\login-noshi\` e as extensões dos arquivos |
| O PHP aparece como texto na tela | Abriu pelo `file://` | Use `http://localhost/...` |
| `Não foi possível conectar ao banco de dados` | MySQL desligado ou credenciais erradas | Inicie o MySQL no XAMPP e confira o `conexao.php` |
| `Unknown database 'formulario_noshi'` | O `banco.sql` não foi importado | Importe o script no phpMyAdmin |
| "Já existe uma conta com esse e-mail" | E-mail já cadastrado | Use outro e-mail ou apague o registro no phpMyAdmin |
| Acentos quebrados (`Ã§`) | Charset diferente | Mantenha `utf8mb4` no banco e `set_charset('utf8mb4')` no `conexao.php` |
| Volta para o login ao abrir o painel | Sessão não iniciou | Confira se há `session_start()` antes de qualquer saída e se os cookies estão ativos |
