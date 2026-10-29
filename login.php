<?php
session_start();
require __DIR__ . '/conexao.php';

function falhar(): void
{
    header('Location: teladelogin.html?erro=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: teladelogin.html');
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    falhar();
}

try {
    $stmt = $conexao->prepare('SELECT id, nome, senha FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('Erro no login: ' . $e->getMessage());
    falhar();
}

// Mesmo se o e-mail não existir, confere contra um hash falso:
// o tempo de resposta fica parecido e não revela quais e-mails estão cadastrados.
$hashFalso = '$2y$10$usesomesillystringforsalt.0000000000000000000000000000';
$hash = $usuario['senha'] ?? $hashFalso;
$confere = password_verify($senha, $hash);

if (!$usuario || !$confere) {
    falhar();
}

// Atualiza o hash se o algoritmo padrão do PHP mudar no futuro.
if (password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT)) {
    $novoHash = password_hash($senha, PASSWORD_DEFAULT);
    $up = $conexao->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
    $up->bind_param('si', $novoHash, $usuario['id']);
    $up->execute();
    $up->close();
}

session_regenerate_id(true); // evita fixação de sessão
$_SESSION['usuario_id']   = (int) $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];

header('Location: painel.php');
exit;
