<?php 
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Promise\Promise;
use function AnkiED\dataArrayFromSheet;
use function AnkiED\dataToJson;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
<?php
  $promise = new Promise();
  $promise->resolve(dataArrayFromSheet(realpath('./verbes.xlsx')));
  $promise->then(function($data) {
    $krumo = new Krumo();
    $krumo->dump($data);
    print "<script> var notesData=".dataToJson($data).";</script>";
    print '<script src="dist/anki.js"></script>';
  });
?>
</body>
</html>