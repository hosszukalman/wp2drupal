<?php
// Add remote addr to skip errors
$_SERVER['REMOTE_ADDR'] = 'wp2drupal';

chdir('/Users/kalmanhosszu/Weblap/Drupal/Oldalak/kalman-hosszu/redesign/www/');

// Drupal start
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

abstract class Drupal {

}