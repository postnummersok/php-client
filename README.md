# PostnummerSök API Client for PHP

This is an API Client for communicating with PostnummerSök using PHP.

## Use With Composer

(We will just assume you have composer installed for your project)

1. Open a command-line interface and navigate to your project folder.

2. Run the following command in your command-line interface:

    composer require postnummersok/client:dev-master

Composer will now autoload Client.php when you create the class object:

```
    $postnummersok = new \PostnummerSok\Client();
```

## Use Without Composer

Manually include Client.php in your script before initiating the client.

```
    require_once 'path/to/Client.php';

    $postnummersok = new \PostnummerSok\Client();
```


## Example Code (More examples in the examples/ folder)

    require_once 'path/to/Client.php';

    try {

      $postnummersok = new PostnummerSok\Client();

      $postnummersok->setCustomerId(12345)
                ->setApiKey('0123456789abcdef0123456789abcdef');

      $request = [
        'postcode' => '11122',
        'country_code' => 'SE',
      ];

      $result = $postnummersok->PostCode($request);

    } catch(Exception $e) {
      die('An error occured: '. $e->getMessage() . PHP_EOL . PHP_EOL
        . $postnummersok->getLastLog());
    }
