<?php

  require_once '../src/Client.php';

  try {

    $postnummersok = new PostnummerSok\Client();

    $postnummersok->setCustomerId(12345)
              ->setApiKey('0123456789abcdef0123456789abcdef');

    $request = [
      'postcode' => '11122',
      'country_code' => 'SE',
      'filter' => ['11120', '11122', '12345'],
      'radius' => '10',
      'unit' => 'km',
      'limit' => '10',
    ];

    $result = $postnummersok->Radius($request);

  } catch(Exception $e) {
    die('An error occured: '. $e->getMessage() . PHP_EOL . PHP_EOL
      . $postnummersok->getLastLog());
  }

  var_dump($result);
