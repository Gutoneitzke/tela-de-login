<?php
session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: teladelogin.html?acesso=1');
    exit;
}

$nome = htmlspecialchars($_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | GN</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="box">
        <h1>Olá, <?= $nome ?></h1>
        <p class="sub">Você está logado. Esta página só abre para quem fez login.</p>
        <a class="btn btn-secundario" href="logout.php">Sair da conta</a>
    </main>
</body>
</html>
