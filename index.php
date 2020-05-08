<?php 
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Promise\Promise;
use function AnkiED\dataArrayFromSheet;
use function AnkiED\dataToJson;
use function GuzzleHttp\Promise\all;

use AnkiED\AnkiEDConjugator;
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
  $load_js_after_these = [];
  $promise_german = new Promise();
  $promise_german->resolve(dataArrayFromSheet(realpath('./verbes.xlsx')));
  $promise_german->then(function($verbe_data) {
    $pomises = [];
    $client = new GuzzleHttp\Client(['timeout' => 20]);
    foreach($verbe_data as $key => $value) {
      $verbe = $value['Verb']['value'];
      $translation = $value['Translation']['value'];
      $pomises[] = AnkiEDConjugator::getGermanConjugationBab($client, $verbe, $translation);
    }
    all($pomises)->then(function($data) {
      krumo($data);
      print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
      return $data;
    })->wait();
  })->then(function() {
    $promise_french = new Promise();
    $promise_french->resolve(dataArrayFromSheet(realpath('./verbes.xlsx'),1));
    $promise_french->then(function($verbe_data) {
      $pomises = [];
      $client = new GuzzleHttp\Client(['timeout' => 20]);
      foreach($verbe_data as $key => $value) {
        $verbe = $value['verbe']['value'];
        $translation = $value['translation']['value'];
        $pomises[] = AnkiEDConjugator::getFrenchConjugationBab($client, $verbe, $translation);
      }
      all($pomises)->then(function($data) {
        krumo($data);
        print "<script> var conjugationFrenchData=".dataToJson($data).";</script>";
        print '<script src="dist/anki.js"></script>';
        return $data;
      })->wait();
    });
  });
?>
</body>
</html>