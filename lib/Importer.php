<?php
abstract class Importer extends Drupal {
  /**
   * @var PDO
   */
  protected $dbhImport;
  /**
   * @var PDO
   */
  protected $dbhWp;

  public function __construct() {
    // DBH
    $this->dbhImport = Registry::get('dbhImport');
    $this->dbhWp = Registry::get('dbhWp');

  }

  /**
   * Run full import
   */
  public abstract function execute();

  /**
   * Delete all imported data
   */
  public abstract function deleteAll();
}