-- Execute este script no phpMyAdmin (aba "Importar" ou "SQL") ou pelo terminal:
-- mysql -u root -p < banco.sql

CREATE DATABASE IF NOT EXISTS formulario_noshi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE formulario_noshi;

CREATE TABLE IF NOT EXISTS usuarios (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome             VARCHAR(120) NOT NULL,
  email            VARCHAR(150) NOT NULL,
  senha            VARCHAR(255) NOT NULL,  -- guarda o hash, nunca a senha em texto
  telefone         VARCHAR(20)  NOT NULL,
  genero           ENUM('feminino','masculino','outro') NOT NULL,
  data_nascimento  DATE         NOT NULL,
  cidade           VARCHAR(100) NOT NULL,
  estado           CHAR(2)      NOT NULL,
  endereco         VARCHAR(200) NOT NULL,
  criado_em        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;
