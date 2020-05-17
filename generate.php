<?php 
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Promise\Promise;
use function AnkiED\dataArrayFromSheet;
use function AnkiED\dataToJson;
use function GuzzleHttp\Promise\all;
use GuzzleHttp\Psr7;

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
function getDwdsGermanDefintionPromises($word_data, $existing_data, $max = 10) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);

  $counter = 0;
  foreach($word_data as $key => $value) {
    if($counter < $max) {
      $word = $value['word']['value'];
      $translation = $value['translation']['value'];

      if($word && $translation) {
        $slug = AnkiEDConjugator::geramnSlugString($word);
        if(!$existing_data[$slug]) {
          $pomise = AnkiEDConjugator::germanDefinitionDwds($client, $word, $translation);
          if($key % 10 == 0) {
            $pomise->wait();
          }
          $counter++;
          $pomises[$slug] = $pomise;
        }
      }
    }
  }
  all($pomises)->then(function($data) use ($existing_data) {
    krumo('all done! writting the german defintions to file!');
    $fp = fopen('german-definitions-dwds.json', 'w');
    $existing = $existing_data;
    if(!is_array($existing)) {
      $existing = array();
    }
    $json_data = json_encode(array_merge($data, $existing));
    fwrite($fp, $json_data);
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
function getLarousseFrenchConjPromises($verbe_data, $existing_data) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);
  foreach($verbe_data as $key => $value) {
    $verbe = $value['verbe']['value'];
    $translation = $value['translation']['value'];
    if($verbe && $translation) {
      $slug = AnkiEDConjugator::frenchSlugString($verbe);
      if(!$existing_data[$slug]) {
        $promise = AnkiEDConjugator::getFrenchConjugationLarousse($client, $verbe, $translation);
        if($key % 10 == 0) {
          $promise->wait();
        }
        $pomises[$slug] = $promise;
      }
    }
  }
  all($pomises)->then(function($data) use ($existing_data){
    krumo('all done! writting the french conjugations to file!');
    $fp = fopen('french-conjugations-larousse.json', 'w');
    $json_data = json_encode(array_merge($data, $existing_data));
    fwrite($fp, $json_data);
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}

/**
 * Get FRENCH definitions
 */
function getLaRousseFrenchDefData($verbe_data, $existing_data) {
  $pomises = [];
  $client = new GuzzleHttp\Client(['timeout' => 20]);
  foreach($verbe_data as $key => $value) {
    $word = $value['word']['value'];
    $translation = $value['translation']['value'];
    if($word && $translation) {
      $slug = AnkiEDConjugator::frenchSlugString($word);
      if(!$existing_data[$slug]) {
        $pomise = AnkiEDConjugator::frenchDefinitionLarousse($client, $word, $translation);
        if($key % 10 == 0) {
          $pomise->wait();
        }
        $pomises[$slug] = $pomise;
      }
    }
  }
  all($pomises)->then(function($data) use ($existing_data) {
    krumo('all done! writting the french defintions to file!');
    $fp = fopen('german-french-larousse.json', 'w');
    $json_data = json_encode(array_merge($data, $existing_data));
    fwrite($fp, $json_data);
    fclose($fp);
    // print "<script> var conjugationGermanData=".dataToJson($data).";</script>";
    return $data;
  })->wait();
}


//  french def and conj data
$data = array(
  'word_data' => dataArrayFromSheet(realpath('./german-vocab.xlsx'),0),
  'existing_data' => array()
);

for($i = 0;$i < 90; $i++ ) {
  $string = file_get_contents(realpath('./german-definitions-dwds.json'));
  $data['existing_data'] = json_decode($string, true);
  $data['existing_data'] = $data['existing_data'] ? $data['existing_data'] : array();
  getDwdsGermanDefintionPromises($data['word_data'], $data['existing_data']);
}

//  german def and conj data
// $promise_german = new Promise();
// $promise_german->resolve(dataArrayFromSheet(realpath('./verbes.xlsx')));
// $promise_german->then(function($word_data) {
//   getDwdsGermanDefintionPromises($word_data);
// });
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


// $string = file_get_contents(realpath('./french-conjugations-larousse.json'));
// $json_a = json_decode($string, true);

// //  french def and conj data
// $promise_french = new Promise();
// $promise_french->resolve(array(
//   'verbe_data' => dataArrayFromSheet(realpath('./verbes.xlsx'),1),
//   'existing_data' => $json_a
// ));
// $promise_french->then(function($data) {
//   getLarousseFrenchConjPromises($data['verbe_data'], $data['existing_data']);
// })->then(function() {
//   $string = file_get_contents(realpath('./german-french-larousse.json'));
//   $json_a = json_decode($string, true);
//   $promise_french_words = new Promise();
//   $promise_french_words->resolve(array(
//     'word_data' => dataArrayFromSheet(realpath('./verbes.xlsx'),3),
//     'existing_data' => $json_a
//   ));
//   $promise_french_words->then(function($data) {
//     getLaRousseFrenchDefData($data['word_data'], $data['existing_data']);
//   });
// });
//  end: french def and conj data
?>