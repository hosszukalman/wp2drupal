<?php
/**
 * @todo Registy class to store DB connections.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

foreach (glob('lib/*.php') as $filename) {
  include_once $filename;
}

try {
  $dsn = 'mysql:dbname=hk_import;host=localhost';
  $user = 'hk_drupal';
  $password = 'sd6EnGpGLZ369bpW';
  $dbhImport = new PDO($dsn, $user, $password);

  $dsn = 'mysql:dbname=hk_wp;host=localhost';
  $user = 'hk_drupal';
  $password = 'sd6EnGpGLZ369bpW';
  $dbhWp = new PDO($dsn, $user, $password);

  Registry::set('dbhImport', $dbhImport);
  Registry::set('dbhWp', $dbhWp);

  if ($_SERVER['argc'] !== 3) {
    throw new Exception('use php import.php [class] [execute|deleteAll]');
  }

  $class = $_SERVER['argv'][1];
  if (!class_exists($class)) {
    throw new Exception($class . ' is not exists!');
  }

  $classRef = new ReflectionClass($class);
  $method = $_SERVER['argv'][2];
  if (!$classRef->hasMethod($method)) {
    throw new Exception($class . '::' . $method . ' is not exists!');
  }

  $importer = new $class;
  $importer->$method();
} catch (Exception $e) {
  echo $e->getMessage() . PHP_EOL;
}