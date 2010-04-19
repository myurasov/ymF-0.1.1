<?php

namespace ymF\Model;

use ymF\Services\PDOService;
use ymF\Config;

class ModelQuery
{
  private $models_namespace = '';
  private $where_sql = '';
  private $limit_sql = '';

  /**
   * Constructor
   *
   * @param string|null $models_namespace Namespace for models
   */
  public function __construct($models_namespace = null)
  {
    // Set models namespace

    if (is_null($models_namespace))
    {
      $this->models_namespace = Config::get('models_namespace');
      $this->models_namespace = is_null($this->models_namespace)
        ? ''
        : Config::get('project_name') . '\\' . $this->models_namespace;
    }
    else
    {
      $this->models_namespace = $models_namespace;
    }
  }

  /**
   * Set WHERE clause
   *
   * @param string $where_sql
   * @return ModelBase
   */
  public function where($where_sql)
  {
    $this->where_sql = $where_sql;
    return $this;
  }

  /**
   * Set LIMIT clause
   *
   * @param string $limit_sql
   * @return ModelBase
  */
  public function limit($limit_sql)
  {
    $this->limit_sql = $limit_sql;
    return $this;
  }

  /**
   * Fetch models
   * 
   * @param string $class_name
   * @return array
   */
  public function fetch($class_name)
  {
    // Append namespace to class name
    $class_name = $this->models_namespace .
      '\\' . $class_name;

    // Get model parameters
    $instance = new $class_name();
    $table = $instance->getTable();
    $id_key = $instance->getIdKey();
    $data = $instance->get();

    // Create SQL query

    $sql = "SELECT %k FROM {$table}";

    if ($this->where_sql != '')
      $sql .= " WHERE " . $this->where_sql;

    if ($this->limit_sql != '')
      $sql .= " LIMIT " . $this->limit_sql;

    $pdo = PDOService::getPDO();
    $sql = PDOService::prepareSQL($sql, $data);
    $result = array();
    $result_i = 0;

    // Fetch models

    if ($stmt = $pdo->query($sql))
    {
      for ($i = 0; $i < $stmt->rowCount(); $i++)
      {
        if ($i > 0)
          $instance = new $class_name();

        $instance->fill($stmt->fetch(\PDO::FETCH_ASSOC));
        $result[] = $instance;
      }
    }

    return $result;
  }
}