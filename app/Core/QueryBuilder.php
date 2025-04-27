<?php

namespace app\Core;

use PDO;

class QueryBuilder
{
  private PDO $pdo;
  private string $table;
  private array $where = [];
  private ?string $orderBy = null;
  private ?int $limit = null;

  public function __construct(PDO $pdo, string $table)
  {
    $this->pdo = $pdo;
    $this->table = $table;
  }

  public function where(string $column, string $operator, $value): self
  {
    $this->where[] = [
      'column' => $column,
      'operator' => $operator,
      'value' => $value,
    ];

    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->orderBy = $column . ' ' . $direction;

    return $this;
  }

  public function limit(int $limit): self
  {
    $this->limit = $limit;

    return $this;
  }

  public function get(): array
  {
    $sql = 'SELECT * FROM ' . $this->table;

    if ($this->where) {
      $conditions = [];
      foreach ($this->where as $condition):
        $conditions[] = $condition['column'] . ' ' . $condition['operator'] . ' ?';
      endforeach;

      $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    if ($this->orderBy) {
      $sql .= ' ORDER BY ' . $this->orderBy;
    }

    if ($this->limit) {
      $sql .= ' LIMIT ' . $this->limit;
    }

    $stmt = $this->pdo->prepare($sql);
    $values = array_column($this->where, 'value');
    $stmt->execute($values);

    return $stmt->fetchAll();
  }

  public function find(int $id): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->table . ' WHERE id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    return $result ?: null;
  }

  public function insert(array $data): int
  {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $sql = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(array_values($data));

    return $this->pdo->lastInsertId();
  }

  public function update(int $id, array $data): bool
  {
    $setClauses = [];
    foreach (array_keys($data) as $column):
      $setClauses[] = "$column = ?";
    endforeach;

    $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $setClauses) . ' WHERE id = ?';
    $stmt = $this->pdo->prepare($sql);
    $values = array_values($data);
    $values[] = $id;

    return $stmt->execute($values);
  }

  public function delete(int $id): bool
  {
    $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table . ' WHERE id = ?');

    return $stmt->execute([$id]);
  }
}