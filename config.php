<?php

    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = '';
    $dbName = 'formulario-noshi2';

    $conexao = new mysqli ($dbHost, $dbUsername,  $dbPassword, $dbName);


    if (!$conexao->connect_errno) {
        echo "Erro";
    }

   else
    {
         echo "Conexão bem-sucedida!";
    }
?>