<?php

class Posts extends Importer {

  function __construct() {
    parent::__construct();
  }

  public function deleteAll() {
    $this->deleteNodes();
  }

  private function deleteNodes() {
    $result = db_query("SELECT nid FROM {node} WHERE type = 'blog'");

    while ($row = db_fetch_array($result)) {
      $this->node_delete($row['nid']);
    }
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
      $node->uid = 1;
      $node->created = strtotime($post['post_date']);
      $node->status = ($post['post_status'] == 'publish');
      $node->title = $post['post_title'];
      $node->body = $post['post_content'];
      $node->format = 4; // New importer input filter
      $node->comment = 2;

      $this->addGeSHiFilter($node);
      
      $node->teaser = node_teaser($node->body, 4);

      node_save($node);
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
}