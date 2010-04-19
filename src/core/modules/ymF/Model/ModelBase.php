<?php

/**
 * Models base class
 * 
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF\Model;

use ymF\Exception;
use ymF\Storage\Storage;
use ymF\Services\PDOService;

abstract class ModelBase extends Storage
{
  /**
   * Datatabase table name
   * @var string
   */
  protected $table = '';

  /**
   * ID key name
   *
   * Persistent copy of object is stored
   * in the database under this key
   *
   * @var string
   */
  protected $id_key = 'id';

  // Changed data
  protected $changed = array();

  // Remember changes?
  private $remember_changes = true;

  /**
   * Constructor
   * 
   * @param array $data
   */
  public function __construct($data = array())
  {
    if (!is_array($data))
    {
      $data = array($this->id_key => $data);
    }

    $this->set($data, null);
  }

  /**
   * Set field or multiple fields
   *
   * @param string|array $name
   * @param mixed $value
   * @return Storage
   */
  public function set($name, $value = null)
  {
    if ($this->remember_changes && !is_array($name))
    {
      $this->changed[$name] = $value;
    }

    return parent::set($name, $value);
  }

  /**
   * Save to database
   *
   * @param boolean $force_all
   * @return ModelBase
   */
  public function save($force_all = false)
  {
    // Save all data
    if ($force_all)
      $this->changed = $this->data;

    if (count($this->changed) > 0)
    {
      $pdo = PDOService::getPDO();

      if (is_null($this->data[$this->id_key]))
      {
        $sql = PDOService::prepareSQL(
          "INSERT INTO {$this->table} (%k) VALUES (%v)",
          $this->changed
        );

        // Insert data
        $pdo->exec($sql);

        // Set id
        $this->set($this->id_key, $pdo->lastInsertId($this->id_key));
      }
      else
      {
        $sql = PDOService::prepareSQL(
          "UPDATE {$this->table} SET %k=v WHERE {$this->id_key}=%s",
          $this->changed,
          $this->data[$this->id_key]
        );

        // Update data
        $pdo->exec($sql);
      }

      $this->changed = array();
    }

    return $this;
  }

  /**
   * Load data from database
   *
   * @param integer $id
   * @return ModelBase
   */
  public function load($id = null)
  {
    if (!is_null($id))
      $this->data[$this->id_key] = $id;

    if (is_null($this->data[$this->id_key]))
      throw new Exception(get_called_class() . '::' . $this->id_key .
        ' is NULL', ymF\ERROR_MISC);

    $pdo = PDOService::getPDO();

    $sql = PDOService::prepareSQL(
      "SELECT %k FROM {$this->table} WHERE {$this->id_key}=%s",
      $this->data, $this->data[$this->id_key]
    );

    // Fetch data
    $this->data = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);

    // Reset changes
    $this->resetChanged();

    return $this;
  }

  /**
   * Fill instance with data, not remembering changes
   *
   * @param array|string $data
   * @param mixed $value
   */
  public function fill($data, $value = null)
  {
    $remember_changes = $this->remember_changes;
    $this->remember_changes = false;

    parent::set($data, $value);

    $this->remember_changes = $remember_changes;
    return $this;
  }

  /**
   * Reset list of changes fields
   */
  public function resetChanged()
  {
    $this->changed = array();
  }

  /**
   * Delete from database
   * Sets id field to null
   *
   * @param integer $id
   * @return ModelBase
   */
  public function delete($id = null)
  {
    if (!is_null($id))
      $this->data[$this->id_key] = $id;

    if (is_null($this->data[$this->id_key]))
      throw new Exception(get_called_class() . '::' . $this->id_key . ' is NULL', ymF\ERROR_MISC);

    $pdo = PDOService::getPDO();

    $sql = PDOService::prepareSQL(
      "DELETE FROM {$this->table} WHERE {$this->id_key}=%s",
      $this->data[$this->id_key]
    );

    // Fetch data
    $pdo->query($sql);

      // Reset changes
    $this->resetChanged();

    return $this;
  }

  /**
   * Get table name
   *
   * @return string
   */
  public function getTable()
  {
    return $this->table;
  }

  /**
   * Get id key name
   * 
   * @return string
   */
  public function getIdKey()
  {
    return $this->id_key;
  }

  /**
   * Create instance from, database
   *
   * @param integer $id
   * @return ModelBase
   */
  public static function fromDatabase($id)
  {
    $instance = new static();
    $instance->load($id);
    return $instance;
  }
}