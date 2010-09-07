<?php
/**
 * Import terms
 */
class Terms extends Importer {

  private $vid = NULL;

  function __construct() {
    parent::__construct();
  }

  public function deleteAll() {
    $this->deleteTerms();
    $this->deleteVocabs();
    $this->deleteImportTable();
  }

  private function deleteTerms() {
    $result = db_query("SELECT tid FROM {term_data}");
    while ($tid = db_fetch_array($result)) {
      taxonomy_del_term($tid['tid']);
    }
  }

  private function deleteVocabs() {
    $result = db_query("SELECT vid FROM {vocabulary}");
    while ($vid = db_fetch_array($result)) {
      taxonomy_del_vocabulary($vid['vid']);
    }
  }

  private function deleteImportTable() {
    $this->dbhImport->exec('TRUNCATE TABLE terms');
  }

  public function execute() {
    $this->createVocab();
    $this->saveTerms();
  }

  private function createVocab() {
    $vocabulary = array(
      'name' => t('Blog categories'),
      'multiple' => 0,
      'required' => 1,
      'hierarchy' => 1,
      'relations' => 0,
      'module' => 'taxonomy',
      'weight' => 0,
      'tags' => 1,
      'nodes' => array('blog' => 1),
    );
    taxonomy_save_vocabulary($vocabulary);
    $this->vid = $vocabulary['vid'];
  }

  private function saveTerms() {
    $weight = 0;
    foreach ($this->dbhWp->query('SELECT t.* FROM wp_term_taxonomy tt INNER JOIN wp_terms t USING(term_id) WHERE taxonomy = \'category\'', PDO::FETCH_ASSOC) as $term) {

      $drupalTerm = array();

      $drupalTerm['vid'] = $this->vid;
      $drupalTerm['name'] = $term['name'];
      $drupalTerm['weight'] = $weight++;
      taxonomy_save_term($drupalTerm);
      
      $this->dbhImport->query('INSERT INTO terms VALUES ('. $term['term_id'] . ', ' . $drupalTerm['tid'] . ')');
    }
  }
}