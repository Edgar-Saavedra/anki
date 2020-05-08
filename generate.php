<?php 
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Promise\Promise;
use function AnkiED\dataArrayFromSheet;
use function AnkiED\dataToJson;
use function GuzzleHttp\Promise\all;

use AnkiED\AnkiEDConjugator;

function getBabGermanConjPromises($verbe_data) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);
  foreach($verbe_data as $key => $value) {
    $verbe = $value['Verb']['value'];
    $translation = $value['Translation']['value'];
    $pomises[] = AnkiEDConjugator::getGermanConjugationBab($client, $verbe, $translation);
  }
  return all($pomises)->then(function($data) {
    print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}


function getVerbixGermanConjPromises($verbe_data) {
  $pomises = [];
  $client = JonnyW\PhantomJs\Client::getInstance();
  foreach($verbe_data as $key => $value) {
    $verbe = $value['Verb']['value'];
    $translation = $value['Translation']['value'];
    $pomises[] = AnkiEDConjugator::getGermanConjugationVerbix($client, $verbe, $translation);
  }
  all($pomises)->then(function($data) {
    krumo($data);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}

function getVerbixFrenchConjPromises($verbe_data) {
  $pomises = [];
  $client = JonnyW\PhantomJs\Client::getInstance();
  $client->isLazy();
  $client->getEngine()->addOption('--load-images=false');
  $client->getEngine()->addOption('--ignore-ssl-errors=true');
  foreach($verbe_data as $key => $value) {
    $verbe = $value['verbe']['value'];
    $translation = $value['translation']['value'];
    if($verbe)
      $pomises[] = AnkiEDConjugator::getFrenchConjugationVerbix($client, $verbe, $translation);
  }
  all($pomises)->then(function($data) {
    krumo('all done! writting the french conjugations to file!');
    $fp = fopen('french-conjugations-verbix.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}

function getBabFrenchConjData() {
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
}

// $load_js_after_these = [];
// $promise_german = new Promise();
// $promise_german->resolve(dataArrayFromSheet(realpath('./verbes.xlsx')));
// $promise_german->then(function($verbe_data) {
//   // getBabGermanConjPromises($verbe_data);
//   getVerbixGermanConjPromises($verbe_data);
// })->then(function() {
//   // getBabFrenchConjData();
// });

$promise_french = new Promise();
$promise_french->resolve(dataArrayFromSheet(realpath('./verbes.xlsx'),1));
$promise_french->then(function($verbe_data) {
  getVerbixFrenchConjPromises($verbe_data);
});
?>