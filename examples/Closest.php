<?php

  require_once '../src/Client.php';

  try {

    $postnummersok = new PostnummerSok\Client();

    $postnummersok->setCustomerId(12345)
              ->setApiKey('0123456789abcdef0123456789abcdef');

    $request = [
      'latitude' => '59.334036596614',
      'longitude' => '18.056213229317',
      'filter' => ['11120', '11122', '12345'],
      'limit' => '10',
      'unit' => 'km',
    ];

    $result = $postnummersok->Closest($request);

  } catch(Exception $e) {
    die('An error occured: '. $e->getMessage() . PHP_EOL . PHP_EOL
      . $postnummersok->getLastLog());
  }

  var_dump($result);
