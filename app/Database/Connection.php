<?php

namespace app\Database;

use PDO;
use PDOException;

class Connection
{
  private static ?PDO $instance = null;

  public static function getInstance(array $config = []): PDO
  {
    if (self::$instance === null) {
      try {
        $host = $config['host'] ?? '';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $pdo_dsn = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4';

        $pdo_options = [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$instance = new PDO($pdo_dsn, $username, $password, $pdo_options);
      }
      catch (PDOException $e) {
        die('Erro na conexão com o banco de dados: ' . $e->getMessage());
      }
    }
    return self::$instance;
  }
}
