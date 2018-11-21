<?php

  require_once '../src/Client.php';

  try {

    $postnummersok = new PostnummerSok\Client();

    $postnummersok->setCustomerId(12345)
              ->setApiKey('0123456789abcdef0123456789abcdef');

    $request = [
      'from' => [
        'postcode' => '11122',
        'country_code' => 'SE',
      ],
      'to' => [
        'postcode' => '41879',
        'country_code' => 'SE',
      ],
      'unit' => 'km',
    ];

    $result = $postnummersok->Distance($request);

  } catch(Exception $e) {
    die('An error occured: '. $e->getMessage() . PHP_EOL . PHP_EOL
      . $postnummersok->getLastLog());
  }

  var_dump($result);
