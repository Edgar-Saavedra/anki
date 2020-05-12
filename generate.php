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


/**
 * Get GERMAN definitions
 */
function getDwdsGermanDefintionPromises($verbe_data) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);
  foreach($verbe_data as $key => $value) {
    $word = $value['word']['value'];
    $translation = $value['translation']['value'];
    if($word && $translation)
      $pomise = AnkiEDConjugator::germanDefinitionDwds($client, $word, $translation);
      if($key % 10 == 0) {
        $pomise->wait();
      }
      $pomises[AnkiEDConjugator::geramnSlugString($word)] = $pomise;
  }
  all($pomises)->then(function($data) {
    krumo('all done! writting the german defintions to file!');
    $fp = fopen('german-definitions-dwds.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}


/**
 * Get GERMAN conjugations
 */
function getVerbixGermanConjPromises($verbe_data) {
  $pomises = [];
  $client = JonnyW\PhantomJs\Client::getInstance();
  $client->isLazy();
  $client->getEngine()->addOption('--load-images=false');
  $client->getEngine()->addOption('--ignore-ssl-errors=true');
  foreach($verbe_data as $key => $value) {
    $verbe = $value['Verb']['value'];
    $translation = $value['Translation']['value'];
    if($verbe)
      $pomises[AnkiEDConjugator::geramnSlugString($verbe)] = AnkiEDConjugator::getGermanConjugationVerbix($client, $verbe, $translation);
  }
  all($pomises)->then(function($data) {
    krumo('all done! writting the german conjugations to file!');
    $fp = fopen('german-conjugations-verbix.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}

/**
 * Get FRENCH conjugations
 */
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
      $pomises[AnkiEDConjugator::frenchSlugString($verbe)] = AnkiEDConjugator::getFrenchConjugationVerbix($client, $verbe, $translation);
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

//getFrenchConjugationLarousse
function getLarousseFrenchConjPromises($verbe_data) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);
  foreach($verbe_data as $key => $value) {
    $verbe = $value['verbe']['value'];
    $translation = $value['translation']['value'];
    if($verbe && $translation) {
      $promise = AnkiEDConjugator::getFrenchConjugationLarousse($client, $verbe, $translation);
      if($key % 10 == 0) {
        $promise->wait();
      }
      $pomises[AnkiEDConjugator::frenchSlugString($verbe)] = $promise;
    }
  }
  all($pomises)->then(function($data) {
    krumo('all done! writting the french conjugations to file!');
    $fp = fopen('french-conjugations-larousse.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}

/**
 * Get FRENCH definitions
 */
function getLaRousseFrenchDefData($verbe_data) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);
  foreach($verbe_data as $key => $value) {
    $word = $value['word']['value'];
    $translation = $value['translation']['value'];
    if($word && $translation) {
      $pomise = AnkiEDConjugator::frenchDefinitionLarousse($client, $word, $translation);
      if($key % 10 == 0) {
        $pomise->wait();
      }
      $pomises[AnkiEDConjugator::frenchSlugString($word)] = $pomise;
    }
  }
  all($pomises)->then(function($data) {
    krumo('all done! writting the french defintions to file!');
    $fp = fopen('german-french-larousse.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}

//  german def and conj data
// $promise_german = new Promise();
// $promise_german->resolve(dataArrayFromSheet(realpath('./verbes.xlsx')));
// $promise_german->then(function($verbe_data) {
//   getVerbixGermanConjPromises($verbe_data);
// })->then(function() {
//   $promise_german_words = new Promise();
//   $promise_german_words->resolve(dataArrayFromSheet(realpath('./verbes.xlsx'),2));
//   $promise_german_words->then(function($word_data) {
//     getDwdsGermanDefintionPromises($word_data);
//   });
// });
//  END: german def and conj data


//  french def and conj data
$promise_french = new Promise();
$promise_french->resolve(dataArrayFromSheet(realpath('./verbes.xlsx'),1));
$promise_french->then(function($verbe_data) {
  getLarousseFrenchConjPromises($verbe_data);
})->then(function() {
  $promise_french_words = new Promise();
  $promise_french_words->resolve(dataArrayFromSheet(realpath('./verbes.xlsx'),3));
  $promise_french_words->then(function($word_data) {
    getLaRousseFrenchDefData($word_data);
  });
});
//  end: french def and conj data
?>