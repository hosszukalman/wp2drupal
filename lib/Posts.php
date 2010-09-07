<?php

class Posts extends Importer {

  /**
   * @var PDOStatement
   */
  private $getWpTermsStatement;

  /**
   * @var PDOStatement
   */
  private $getWpKeywordsStatement;

  function __construct() {
    parent::__construct();

    $this->getWpTermsStatement = $this->dbhWp->prepare("SELECT t.name FROM `wp_posts` p
      INNER JOIN wp_term_relationships tr ON (p.ID = tr.object_id)
      INNER JOIN wp_term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'category')
      INNER JOIN wp_terms t ON (tt.term_id = t.term_id)
      WHERE p.post_type = 'post' AND p.ID = :post_id");

    $this->getWpKeywordsStatement = $this->dbhWp->prepare("SELECT t.name FROM `wp_posts` p
      INNER JOIN wp_term_relationships tr ON (p.ID = tr.object_id)
      INNER JOIN wp_term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'post_tag')
      INNER JOIN wp_terms t ON (tt.term_id = t.term_id)
      WHERE p.post_type = 'post' AND p.ID = :post_id");
  }

  public function deleteAll() {
    $this->deleteNodes();
    $this->deleteImportTable();
  }

  private function deleteNodes() {
    $result = db_query("SELECT nid FROM {node} WHERE type = 'blog'");

    while ($row = db_fetch_array($result)) {
      $this->node_delete($row['nid']);
    }
  }

  private function deleteImportTable() {
    $this->dbhImport->exec('TRUNCATE TABLE posts');
  }

  public function execute() {
    $this->savePosts();
  }

  private function savePosts() {
    $counter = 0;
    foreach ($this->dbhWp->query('SELECT p.* FROM wp_posts p WHERE p.post_type = \'post\' ORDER BY p.post_date', PDO::FETCH_ASSOC) as $post) {
      echo $counter++ . PHP_EOL;

      $node = new stdClass();
      $node->type = 'blog';
      $node->uid = 3; // hosszu.kalman
      $node->created = strtotime($post['post_date']);
      $node->status = ($post['post_status'] == 'publish');
      $node->title = $post['post_title'];
      $node->body = $post['post_content'];
      $node->format = 4; // New importer input filter
      $node->comment = 2;

      $this->addGeSHiFilter($node);
      
      $node->teaser = node_teaser($node->body, 4);

      $this->addTerms($node, $post['ID']);
      $this->addMetaKeywords($node, $post['ID']);

      node_save($node);

      $this->dbhImport->query('INSERT INTO posts VALUES ('. $post['ID'] . ', ' . $node->nid . ')');
    }
  }

  private function addGeSHiFilter(&$node) {
    $pattern = array(
      '<pre',
      '</pre',
    );

    $replacament = array(
      '<code',
      '</code',
    );

    $node->body = str_replace($pattern, $replacament, $node->body);
  }

  private function addTerms(&$node, $postId) {
    $terms = array();
    $this->getWpTermsStatement->execute(array(':post_id' => $postId));
    $result = $this->getWpTermsStatement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
      $terms[] = $row['name'];
    }

    $node->taxonomy['tags'][Variables::getVariable('vocab_id')] = implode(',', $terms);
  }

  private function addMetaKeywords(&$node, $postId) {
    $terms = array();
    $this->getWpKeywordsStatement->execute(array(':post_id' => $postId));
    $result = $this->getWpKeywordsStatement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
      $terms[] = $row['name'];
    }

    $node->nodewords['keywords']['value'] = implode(',', $terms);

  }
}