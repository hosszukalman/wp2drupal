<?php
/**
 *
 */
// Add remote addr to skip errors
$_SERVER['REMOTE_ADDR'] = 'wp2drupal';

chdir('/Users/kalmanhosszu/Weblap/Drupal/Oldalak/kalman-hosszu/redesign/www/');

// Drupal start
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

abstract class Drupal {

  /**
   * Override node_delete() core Drupal function.
   * Skip user access function during the importing.
   *
   * @param int $nid The nodeID
   */
  protected function node_delete($nid) {

    // Clear the cache before the load, so if multiple nodes are deleted, the
    // memory will not fill up with nodes (possibly) already removed.
    $node = node_load($nid, NULL, TRUE);


    db_query('DELETE FROM {node} WHERE nid = %d', $node->nid);
    db_query('DELETE FROM {node_revisions} WHERE nid = %d', $node->nid);

    // Call the node-specific callback (if any):
    node_invoke($node, 'delete');
    node_invoke_nodeapi($node, 'delete');

    // Clear the page and block caches.
    cache_clear_all();

    // Remove this node from the search index if needed.
    if (function_exists('search_wipe')) {
      search_wipe($node->nid, 'node');
    }
    watchdog('content', '@type: deleted %title.', array('@type' => $node->type, '%title' => $node->title));
    drupal_set_message(t('@type %title has been deleted.', array('@type' => node_get_types('name', $node), '%title' => $node->title)));

  }

  /**
   * Override comment_save() core Drupal function.
   * Skip user access function during the importing.
   *
   * @global stdClass $user
   * @param array $edit
   * @return int/bool The created commentID or FALSE.
   */
  protected function comment_save($edit) {
    global $user;
    if (!form_get_errors()) {
      $edit += array(
        'mail' => '',
        'homepage' => '',
        'name' => '',
        'status' => user_access('post comments without approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED,
      );
      if ($edit['cid']) {
        // Update the comment in the database.
        db_query("UPDATE {comments} SET status = %d, timestamp = %d, subject = '%s', comment = '%s', format = %d, uid = %d, name = '%s', mail = '%s', homepage = '%s' WHERE cid = %d", $edit['status'], $edit['timestamp'], $edit['subject'], $edit['comment'], $edit['format'], $edit['uid'], $edit['name'], $edit['mail'], $edit['homepage'], $edit['cid']);

        // Allow modules to respond to the updating of a comment.
        comment_invoke_comment($edit, 'update');

        // Add an entry to the watchdog log.
        watchdog('content', 'Comment: updated %subject.', array('%subject' => $edit['subject']), WATCHDOG_NOTICE, l(t('view'), 'node/'. $edit['nid'], array('fragment' => 'comment-'. $edit['cid'])));
      }
      else {
        // Add the comment to database.
        // Here we are building the thread field. See the documentation for
        // comment_render().
        if ($edit['pid'] == 0) {
          // This is a comment with no parent comment (depth 0): we start
          // by retrieving the maximum thread level.
          $max = db_result(db_query('SELECT MAX(thread) FROM {comments} WHERE nid = %d', $edit['nid']));

          // Strip the "/" from the end of the thread.
          $max = rtrim($max, '/');

          // Finally, build the thread field for this new comment.
          $thread = int2vancode(vancode2int($max) + 1) .'/';
        }
        else {
          // This is comment with a parent comment: we increase
          // the part of the thread value at the proper depth.

          // Get the parent comment:
          $parent = _comment_load($edit['pid']);

          // Strip the "/" from the end of the parent thread.
          $parent->thread = (string) rtrim((string) $parent->thread, '/');

          // Get the max value in _this_ thread.
          $max = db_result(db_query("SELECT MAX(thread) FROM {comments} WHERE thread LIKE '%s.%%' AND nid = %d", $parent->thread, $edit['nid']));

          if ($max == '') {
            // First child of this parent.
            $thread = $parent->thread .'.'. int2vancode(0) .'/';
          }
          else {
            // Strip the "/" at the end of the thread.
            $max = rtrim($max, '/');

            // We need to get the value at the correct depth.
            $parts = explode('.', $max);
            $parent_depth = count(explode('.', $parent->thread));
            $last = $parts[$parent_depth];

            // Finally, build the thread field for this new comment.
            $thread = $parent->thread .'.'. int2vancode(vancode2int($last) + 1) .'/';
          }
        }

        if (empty($edit['timestamp'])) {
          $edit['timestamp'] = time();
        }

        if ($edit['uid'] === $user->uid && isset($user->name)) { // '===' Need to modify anonymous users as well.
          $edit['name'] = $user->name;
        }

        db_query("INSERT INTO {comments} (nid, pid, uid, subject, comment, format, hostname, timestamp, status, thread, name, mail, homepage) VALUES (%d, %d, %d, '%s', '%s', %d, '%s', %d, %d, '%s', '%s', '%s', '%s')", $edit['nid'], $edit['pid'], $edit['uid'], $edit['subject'], $edit['comment'], $edit['format'], ip_address(), $edit['timestamp'], $edit['status'], $thread, $edit['name'], $edit['mail'], $edit['homepage']);
        $edit['cid'] = db_last_insert_id('comments', 'cid');

        // Tell the other modules a new comment has been submitted.
        comment_invoke_comment($edit, 'insert');

        // Add an entry to the watchdog log.
        watchdog('content', 'Comment: added %subject.', array('%subject' => $edit['subject']), WATCHDOG_NOTICE, l(t('view'), 'node/'. $edit['nid'], array('fragment' => 'comment-'. $edit['cid'])));
      }
      _comment_update_node_statistics($edit['nid']);

      // Clear the cache so an anonymous user can see his comment being added.
      cache_clear_all();

      // Explain the approval queue if necessary, and then
      // redirect the user to the node he's commenting on.
      if ($edit['status'] == COMMENT_NOT_PUBLISHED) {
        drupal_set_message(t('Your comment has been queued for moderation by site administrators and will be published after approval.'));
      }
      else {
        comment_invoke_comment($edit, 'publish');
      }
      return $edit['cid'];
    }
    else {
      return FALSE;
    }
  }
}