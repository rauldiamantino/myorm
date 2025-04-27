<?php

namespace app;

use app\Database\Connection;
use app\Core\QueryBuilder;
use PDO;

abstract class Model
{
  protected PDO $pdo;
  protected string $table;

  public function __construct(array $config = [])
  {
    $this->pdo = Connection::getInstance($config);

    if (empty($this->table)) {
      // Tenta inferir o nome da tabela a partir do nome da classe
      $classNameParts = explode('\\', get_class($this));
      $className = end($classNameParts);

      // Converte CamelCase para snake_case (ex: User -> users)
      $this->table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
    }
  }

  public static function query(): QueryBuilder
  {
    $model = new static();

    return new QueryBuilder($model->pdo, $model->table);
  }

  public static function find(int $id): ?self
  {
    $model = new static();
    $data = $model->pdo->query('SELECT * FROM ' . $model->table . ' WHERE id = ' . $id)->fetch();

    if (empty($data)) {
      return null;
    }

    $instance = new static();
    foreach ($data as $key => $value):
      $instance->$key = $value;
    endforeach;

    return $instance;
  }

  public function save(): bool
  {
    $data = [];
    foreach ($this as $key => $value):

      if ($key == 'pdo' or $key == 'table' or $value == null) {
        continue;
      }

      $data[ $key ] = $value;
    endforeach;

    if (isset($this->id)) {
      $stmt = $this->pdo->prepare('UPDATE ' . $this->table . ' SET ' . implode(' = ?, ', array_keys($data)) . ' = ? WHERE id = ?');
      $stmt = $stmt->execute(array_merge(array_values($data), [ $this->id ]));

      return (bool) $stmt;
    }
    else {
      $columns = implode(', ', array_keys($data));
      $placeholders = implode(', ', array_fill(0, count($data), '?'));

      $stmt = $this->pdo->prepare('INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders .')');
      $stmt->execute(array_values($data));
      $this->id = $this->pdo->lastInsertId();

      return (bool) $this->id;
    }
  }

  public function delete(): bool
  {
    if (isset($this->id)) {
      $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table . ' WHERE id = ?');
      $stmt = $stmt->execute([$this->id]);

      return (bool) $stmt;
    }

    return false;
  }
}