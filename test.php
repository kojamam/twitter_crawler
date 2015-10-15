<?php

require_once 'config.php';
$mongo = new Mongo();
$db = $mongo->selectDB(DB_NAME);
$dataCol = $db->selectCollection(DATA_COLLECTION_NAME);

$a = $dataCol->findOne(array('user_id' => 857770321));

var_dump($a);