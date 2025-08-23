<?php

    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = '';
    $dbName = 'formulario-noshi';

    $conexao = new mysqli ($dbHost, $dbUsername,  $dbPassword, $dbName);


    if (!$conexao->connect_errno) {
        echo "Erro ao conectar com o banco de dados: ";
    }

   else
    {
         echo "Conexão bem-sucedida!";
    }
?>