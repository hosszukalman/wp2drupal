<?php

class Variables {

  public static function saveVariable($name, $value) {
    $dbhImport = Registry::get('dbhImport');
    $dbhImport->query('INSERT INTO variables VALUES (\''. $name . '\', \'' . serialize($value) . '\')');
  }

  public static function getVariable($name) {
    $dbhImport = Registry::get('dbhImport');
    $getVariableStatementt = $dbhImport->prepare('SELECT value FROM variables WHERE name = :name');

    $getVariableStatementt->execute(array(':name' => $name));
    $result = $getVariableStatementt->fetch(PDO::FETCH_ASSOC);

    return unserialize($result['value']);

  }

  public static function deleteVariable($name) {
    $dbhImport = Registry::get('dbhImport');
    $dbhImport->query('DELETE FROM variables WHERE name = \''. $name . '\'');
  }
}