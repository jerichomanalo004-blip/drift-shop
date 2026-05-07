<?php
namespace Core;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function all($orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) $sql .= " ORDER BY $orderBy";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function where($column, $value, $operator = '=') {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE $column $operator ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save(array $data) {
        if ($this->validate($data) === false) throw new \Exception("Validation failed");
        if (isset($data[$this->primaryKey]) && $this->find($data[$this->primaryKey])) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    protected function insert(array $data) {
        $columns = array_intersect_key($data, array_flip($this->fillable));
        $cols = implode(',', array_keys($columns));
        $placeholders = ':' . implode(',:', array_keys($columns));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ($cols) VALUES ($placeholders)");
        return $stmt->execute($columns);
    }

    protected function update(array $data) {
        $id = $data[$this->primaryKey];
        unset($data[$this->primaryKey]);
        $sets = '';
        foreach (array_keys($data) as $col) $sets .= "$col = :$col, ";
        $sets = rtrim($sets, ', ');
        $stmt = $this->db->prepare("UPDATE {$this->table} SET $sets WHERE {$this->primaryKey} = :id");
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function validate(array $data) { return true; }
}