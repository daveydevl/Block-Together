<?php

require_once 'includes/Processor.php';
require_once 'includes/Datastore.php';

header('Content-type: application/json');

$json = json_decode(file_get_contents("php://input"), true);
if (is_null($json)) {
    echo json_encode(['method' => 'error', 'params' => ['Not a valid json']]);
    exit;
}

$api_key = null;

if (!empty($json['api_key']))
    $api_key = $json['api_key'];

echo json_encode(
    (new Processor(new Datastore, $api_key))->execute($json));

