<?php
// Configurações do banco (ajuste para o seu ambiente).
// Em produção, use um usuário próprio com senha, nunca "root" sem senha.
$dbHost     = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName     = 'formulario_noshi';

// Faz o mysqli lançar exceções em vez de avisos soltos na tela.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    $conexao->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Erro de conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}
