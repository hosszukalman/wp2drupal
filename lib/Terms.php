<?php
/**
 * Import terms
 */
class Terms extends Importer {

  private $vid = NULL;

  function __construct() {

  }

  public function deleteAll() {
    $this->deleteTerms();
    $this->deleteVocabs();
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

  public function execute() {
    $this->createVocab();
    var_dump($this->vid);
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
}