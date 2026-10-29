<?php
require __DIR__ . '/conexao.php';

function voltar(string $codigo): void
{
    header('Location: formulario.html?erro=' . urlencode($codigo));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formulario.html');
    exit;
}

$ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA',
        'PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

$nome      = trim($_POST['nome'] ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$senha     = $_POST['senha'] ?? '';
$confirma  = $_POST['confirmar_senha'] ?? '';
$telefone  = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
$genero    = $_POST['genero'] ?? '';
$nascText  = $_POST['data_nascimento'] ?? '';
$cidade    = trim($_POST['cidade'] ?? '');
$estado    = strtoupper(trim($_POST['estado'] ?? ''));
$endereco  = trim($_POST['endereco'] ?? '');

// Validações (o navegador valida, mas o servidor não pode confiar nisso).
$nascimento = DateTime::createFromFormat('!Y-m-d', $nascText);
$nascValida = $nascimento
    && $nascimento->format('Y-m-d') === $nascText
    && $nascimento <= new DateTime('today');

if (
    mb_strlen($nome) < 3 || mb_strlen($nome) > 120 ||
    strlen($telefone) < 10 || strlen($telefone) > 11 ||
    !in_array($genero, ['feminino', 'masculino', 'outro'], true) ||
    !$nascValida ||
    $cidade === '' || mb_strlen($cidade) > 100 ||
    !in_array($estado, $ufs, true) ||
    $endereco === '' || mb_strlen($endereco) > 200
) {
    voltar('campos');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    voltar('email');
}

if (strlen($senha) < 8) {
    voltar('senha');
}

if ($senha !== $confirma) {
    voltar('confere');
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = $conexao->prepare(
        'INSERT INTO usuarios
            (nome, email, senha, telefone, genero, data_nascimento, cidade, estado, endereco)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'sssssssss',
        $nome, $email, $hash, $telefone, $genero, $nascText, $cidade, $estado, $endereco
    );
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) { // e-mail já cadastrado (índice UNIQUE)
        voltar('existe');
    }
    error_log('Erro no cadastro: ' . $e->getMessage());
    voltar('erro');
}

header('Location: teladelogin.html?cadastro=ok');
exit;
