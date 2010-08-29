<?php
ini_set('display_errors', 1);
error_reporting(E_ALL|E_STRICT);

foreach (glob('lib/*.php') as $filename) {
  include_once $filename;
}