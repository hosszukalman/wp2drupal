<?php

class Comments extends Importer {

  /**
   * @var PDOStatement
   */
  private $getDrupalCommentIdByWpIdStatement;

  /**
   * @var PDOStatement
   */
  private $getDruaplNodeIdByWpPostIdStatement;
  
  public function __construct() {
    parent::__construct();
    $this->getDrupalCommentIdByWpIdStatement = $this->dbhImport->prepare("SELECT cid FROM comments WHERE wp_comment_id = :wp_comment_id");
    $this->getDruaplNodeIdByWpPostIdStatement = $this->dbhImport->prepare("SELECT nid FROM posts WHERE post_id = :wp_post_id");
  }

  public function deleteAll() {
    $this->deleteComments();
    $this->deleteImportTable();
  }

  private function deleteComments() {
    module_load_include('inc', 'comment', 'comment.admin');

    $result = db_query("SELECT c.*, u.name AS registered_name, u.uid FROM {comments} c INNER JOIN {users} u ON u.uid = c.uid");

    while ($comment = db_fetch_object($result)) {
      // Delete comment and its replies.
      _comment_delete_thread($comment);
      _comment_update_node_statistics($comment->nid);
    }
    // Clear the cache so an anonymous user sees that his comment was deleted.
    cache_clear_all();
  }

  private function deleteImportTable() {
    $this->dbhImport->exec('TRUNCATE TABLE comments');
  }

  public function execute() {
    $this->saveCommentsToDrupal();
  }

  private function saveCommentsToDrupal() {
    foreach ($this->dbhWp->query('SELECT c.* FROM wp_comments c WHERE c.comment_approved = 1 ORDER BY c.comment_ID', PDO::FETCH_ASSOC) as $wpComment) {

      $edit = array(
        'pid' => ($wpComment['comment_parent']) ? $this->getDrupalCommentIdByWpId($wpComment['comment_parent']) : 0,
        'timestamp' => strtotime($wpComment['comment_date']),
        'uid' => ($wpComment['user_id'] == 1) ? 3 : 0,
        'nid' => $this->getDruaplNodeIdByWpPostId($wpComment['comment_post_ID']),
        'subject' => '',
        'comment' => $wpComment['comment_content'],
        'format' => 1,
        'status' => 0,
        'name' => $wpComment['comment_author'],
        'mail' => $wpComment['comment_author_email'],
        'homepage' => $wpComment['comment_author_url'],
        'hostname' => $wpComment['comment_author_IP'],
      );

      $cid = $this->comment_save($edit);

      $this->storeCommentIdToImportDb($wpComment['comment_ID'], $cid);
    }
  }

  private function getDrupalCommentIdByWpId($wpCommentId) {
    $this->getDrupalCommentIdByWpIdStatement->execute(array(':wp_comment_id' => $wpCommentId));
    $result = $this->getDrupalCommentIdByWpIdStatement->fetch(PDO::FETCH_ASSOC);

    if (!$result['cid']) {
      throw New Exception('No WP comment ID: ' . $wpCommentId);
    }

    return $result['cid'];
  }

  private function getDruaplNodeIdByWpPostId($wpPostId) {
    $this->getDruaplNodeIdByWpPostIdStatement->execute(array(':wp_post_id' => $wpPostId));
    $result = $this->getDruaplNodeIdByWpPostIdStatement->fetch(PDO::FETCH_ASSOC);

    if (!$result['nid']) {
      throw New Exception('No WP post ID: ' . $wpPostId);
    }

    return $result['nid'];
  }

  private function storeCommentIdToImportDb($wpCommentId, $cid) {
    $this->dbhImport->query('INSERT INTO comments VALUES ('. $wpCommentId . ', ' . $cid . ')');
  }

}