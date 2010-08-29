<?php
/**
 * @todo Add comment_save function to skip user access
 * @todo Add node_delete function to skip user access
 */
// Add remote addr to skip errors
$_SERVER['REMOTE_ADDR'] = 'wp2drupal';

chdir('/Users/kalmanhosszu/Weblap/Drupal/Oldalak/kalman-hosszu/redesign/www/');

// Drupal start
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

abstract class Drupal {

}