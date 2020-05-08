<?php 
namespace AnkiED;
use Sunra\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;

class AnkiEDConjugator {
  static function prepForGermanBab($str) {
    $unwanted_array = array(    
      'Ä'=>'ae','Ë'=>'ee', 'Ï'=>'ie','Ö'=>'oe','Ü'=>'eu','ß'=>'ss','ä'=>'ae','ë'=>'ee','ï'=>'ie',    
      'ö'=>'oe','ü'=>'eu' );
    $str =  strtr( $str, $unwanted_array );
    $str = strtolower($str);
    return $str;
  }

  static function basicSlug($str) {
    $slug = $str;
    if($str) {
      $slug = trim($slug);
      $slug = iconv('UTF-8','ASCII//TRANSLIT',$slug);
      $slug = strtolower($slug);
      $slug = preg_replace('/\"/', '', $slug);
      $slug = preg_replace('/\s+/', '_', $slug);
    }
    return $slug;
  }

  static function frenchSlugString($str) {
    $slug = $str;
    if($str) {
      $slug = trim($slug);
      setlocale(LC_ALL, 'French_Standard');
      $slug = iconv('UTF-8','ASCII//TRANSLIT',$slug);
      $slug = strtolower($slug);
      $slug = preg_replace('/\'/', '', $slug);
      $slug = preg_replace('/\s+/', '_', $slug);
    }
    return $slug;
  }

  static function geramnSlugString($str) {
    $slug = $str;
    if($str) {
      $slug = trim($slug);
      setlocale(LC_ALL, 'de_DE');
      $slug = iconv('UTF-8','ASCII//TRANSLIT',$slug);
      $slug = strtolower($slug);
      $slug = preg_replace('/\"/', '', $slug);
      $slug = preg_replace('/\s+/', '_', $slug);
    }
    return $slug;
  }

  static function getSlug($lang, $str) {
    switch($lang) {
      case 'german':
        return AnkiEDConjugator::geramnSlugString($str);
      break;
      case 'french':
        return AnkiEDConjugator::frenchSlugString($str);
      break;
      default:
        return AnkiEDConjugator::basicSlug($str);
      break;
    }
  }

  static function processBabConjugationPage($lang = null, $html = null, $verb = null, $translation = null) {
    $conjugations = array(
      'verb' => $verb,
      'translation' => $translation,
      'conjugations_found' => false,
      'language' => $lang
    );
    if($html) {
      $dom = HtmlDomParser::str_get_html($html);
      $elems = $dom->find('.conj-tense-wrapper');
      foreach($elems as $key => $value) {
        $conjugations['conjugations_found'] = true;
        $header = $value->find('.result-block');
        $header = $header[0]->find('h3');
        $header = $header[0] ? $header[0]->innertext() : null;
  
        if($header) {
          $header_slug = AnkiEDConjugator::getSlug($lang, $header);
          $conjugations[$header_slug] = array(
            'category' => $header,
            'tenses' => array()
          );
  
          $tense_block = $value->find('.conj-tense-block');
          foreach($tense_block as $key => $block) {
            $tense_block_header = $block->find('.conj-tense-block-header');
            $tense_block_header = $tense_block_header[0];
            $tense_block_header = $tense_block_header ? $tense_block_header->innertext() : null;
  
            if($tense_block_header) {
              $tense_block_header_slug = AnkiEDConjugator::getSlug($lang, $tense_block_header);
              $conjugations[$header_slug]['tenses'][$tense_block_header_slug] = array(
                'tense' => $tense_block_header,
                'tense_category' => $header,
                'conjugations' => array()
              );
              $conj_items = $block->find('.conj-item');
              foreach($conj_items as $key => $item) {
                $person = $item->find('.conj-person');
                $person = $person[0] ? $person[0]->innertext() : null;
                $result = $item->find('.conj-result');
                $result = $result[0] ? $result[0]->innertext() : null;
                $conjugation_item = array(
                  'tense_category' => $header,
                  'tense' => $tense_block_header,
                  'person' => $person,
                  'result' => $result
                );
                $conjugations[$header_slug]['tenses'][$tense_block_header_slug]['conjugations'][$person ? AnkiEDConjugator::getSlug($lang, $person) : $key] = $conjugation_item;
              }
            }
          }
        }
      }
    }
    return $conjugations;
  }

  static function getFrenchConjugationBab($client = null, $verb = null, $translation = null) {
    if($verb) {
      $verb_encode = $verb;
      // $path = "https://www.die-konjugation.de/verb/$verb_encode.php";
      // $client = new Client(['base_uri' => 'https://de.bab.la/']);
      $response = $client->requestAsync('GET', "https://en.bab.la/conjugation/french/$verb_encode");
      return $response->then(function($response) use ( $verb, $translation ) {
        return AnkiEDConjugator::processBabConjugationPage('french',$response->getBody()->getContents(), $verb, $translation);
      });
      return $response;
    }
    $promise = new Promise(function () use (&$promise) {
      $promise->resolve([]);
    });
    return $promise->then(function($value) {
      return $value;
    });
  }

  static function getGermanConjugationBab($client = null, $verb = null, $translation = null) {
    if($verb) {
      $verb_encode = urlencode(AnkiEDConjugator::prepForGermanBab($verb));
      // $path = "https://www.die-konjugation.de/verb/$verb_encode.php";
      // $client = new Client(['base_uri' => 'https://de.bab.la/']);
      $response = $client->requestAsync('GET', "https://de.bab.la/konjugieren/deutsch/$verb_encode");
      return $response->then(function($response) use ( $verb, $translation ) {
        return AnkiEDConjugator::processBabConjugationPage('german', $response->getBody()->getContents(), $verb, $translation);
      });
      return $response;
    }
    $promise = new Promise(function () use (&$promise) {
      $promise->resolve(false);
    });
    return $promise->then(function($value) {
      return $value;
    });
  }
}