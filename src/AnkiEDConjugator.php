<?php 
namespace AnkiED;
use Sunra\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;

class AnkiEDConjugator {
  static function prepForVerbixFrench($str) {
    $unwanted_array = array(    
      'â'=>'a>',  
      'ç'=>'c,',  
      'é'=>'e/',  
      'î'=>'i>',  
      'ï'=>'i:',  
      'ô'=>'o>',  
      'œ'=>'oE',
    );
    $str = trim($str);
    $str = strtolower($str);
    $str = strtr( $str, $unwanted_array );
    return $str;
  }
  static function prepForVerbixGerman($str) {
    $unwanted_array = array(    
      'Ä'=>'a:','Ö'=>'o:','Ü'=>'u:','ß'=>'sZ','ä'=>'a:','ö'=>'o:','ü'=>'u:');
    $str = trim($str);
    $str = strtr( $str, $unwanted_array );
    $str = strtolower($str);
    return $str;
  }
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
      $slug = preg_replace('/\&/', 'and', $slug);
      $slug = preg_replace('/amp;/', '', $slug);
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
      $slug = preg_replace('/\&/', 'and', $slug);
      $slug = preg_replace('/amp;/', '', $slug);
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

  static function processVerbixConjugationPage($lang = null, $html = null, $verb = null, $translation = null) {
    $conjugations = array(
      'verb' => $verb,
      'translation' => $translation,
      'conjugations_found' => false,
      'language' => $lang
    );
    if($html) {
      $dom = HtmlDomParser::str_get_html($html);
      $elems = $dom->find('.columns-main > div');
      foreach($elems as $key => $value) {
        foreach ($value->find('.google-auto-placed') as $node){
          $node->outertext = '';
        }
        $conjugations['conjugations_found'] = true;
        $header = $value->find('> h3');
        $header = $header[0] ? $header[0]->innertext() : null;
  
        if($header) {
          $header_slug = AnkiEDConjugator::getSlug($lang, $header);
          $conjugations[$header_slug] = array(
            'category' => $header,
          );
  
          $tense_block = $value->find('.columns-sub');

          if(sizeof($tense_block)) {
            foreach($tense_block as $key => $block) {
              $tense_sub_block = $block->find('> div');
              foreach($tense_sub_block as $key => $tense_sub_block_block) {
                $tense_block_header = $tense_sub_block_block->find('> h4');
                $tense_block_header = $tense_block_header[0];
                $tense_block_header = $tense_block_header ? $tense_block_header->innertext() : null;
                if($tense_block_header) {
                  $tense_block_header_slug = AnkiEDConjugator::getSlug($lang, $tense_block_header);
                  $conjugations[$header_slug]['tenses'][$tense_block_header_slug] = array(
                    'tense' => $tense_block_header,
                    'tense_category' => $header,
                    'conjugations' => array()
                  );
                  $conj_items = $tense_sub_block_block->find('.verbtense');
                  foreach($conj_items as $key => $item) {
                    $person = $item->find('tr');
                    foreach($person as $key => $tense_person) {
                      $pronoun = $tense_person->find('.pronoun');
                      $conjugation = $tense_person->find('.normal');
                      if(!sizeof($conjugation)) {
                        $conjugation = $tense_person->find('.irregular');
                      }
                      $pronoun = $pronoun[0] ? $pronoun[0]->innertext() : null;
                      $conjugation = $conjugation[0] ? $conjugation[0]->innertext() : null;
                      $conjugation_item = array(
                        'tense_category' => $header,
                        'tense' => $tense_block_header,
                        'pronoun' => $pronoun,
                        'conjugation' => $conjugation
                      );
                      $conjugations[$header_slug]['tenses'][$tense_block_header_slug]['conjugations'][$pronoun ? AnkiEDConjugator::getSlug($lang, $pronoun) : $key] = $conjugation_item;
                    }
                  }
                }
              }
            } // END: add tense data 
          } else if(sizeof($value->find('.verbtense'))) {
            $conjugations[$header_slug]['tenses'][$header_slug] = array(
              'tense' => $header_slug,
              'tense_category' => $header,
              'conjugations' => array()
            );
            $conj_items = $value->find('.verbtense');
            foreach($conj_items as $key => $item) {
              $person = $item->find('tr');
              foreach($person as $key => $tense_person) {
                $pronoun = $tense_person->find('.pronoun');
                $conjugation = $tense_person->find('.normal');
                if(!sizeof($conjugation)) {
                  $conjugation = $tense_person->find('.irregular');
                }
                $pronoun = $pronoun[0] ? $pronoun[0]->innertext() : null;
                $conjugation = $conjugation[0] ? $conjugation[0]->innertext() : null;
                $conjugation_item = array(
                  'tense_category' => $header,
                  'tense' => $tense_block_header,
                  'pronoun' => $pronoun,
                  'conjugation' => $conjugation
                );
                $conjugations[$header_slug]['tenses'][$header_slug]['conjugations'][$pronoun ? AnkiEDConjugator::getSlug($lang, $pronoun) : $key] = $conjugation_item;
              }
            }
          } else {
            $dom = HtmlDomParser::str_get_html($value->innertext());
            foreach ($dom->find('h3') as $node){
              $node->outertext = '';
            }
            $conjugations[$header_slug]['content'] = $dom->innertext;
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
    }
    $promise = new Promise(function () use (&$promise) {
      $promise->resolve(false);
    });
    return $promise->then(function($value) {
      return $value;
    });
  }
  
  static function getGermanConjugationVerbix($client = null, $verb = null, $translation = null) {
    if($verb) {
      $verb_encode = AnkiEDConjugator::prepForVerbixGerman($verb);
      /** 
       * @see JonnyW\PhantomJs\Http\Request
       **/
      $request = $client->getMessageFactory()->createRequest("https://www.verbix.com/webverbix/German/$verb_encode.html", 'GET');
      /** 
       * @see JonnyW\PhantomJs\Http\Response 
       **/
      $response = $client->getMessageFactory()->createResponse();

      $promise = new Promise(function () use (&$promise, $client, $request, $response) {
        $promise->resolve($client->send($request, $response));
      });
      return $promise->then(function($response) use ( $verb, $translation ) {
        if($response->getStatus() === 200) {
          $html = $response->getContent();
          return AnkiEDConjugator::processVerbixConjugationPage("german", $html, $verb, $translation);
        }
        return false;
      });
      // return $response->then(function($response) use ( $verb, $translation ) {
      //   return AnkiEDConjugator::processBabConjugationPage('german', $response->getBody()->getContents(), $verb, $translation);
      // });
    }
    $promise = new Promise(function () use (&$promise) {
      $promise->resolve(false);
    });
    return $promise->then(function($value) {
      return $value;
    });
  }


  static function getFrenchConjugationVerbix($client = null, $verb = null, $translation = null) {
    if($verb) {
      $verb_encode = AnkiEDConjugator::prepForVerbixFrench($verb);
      /** 
       * @see JonnyW\PhantomJs\Http\Request
       **/
      $request = $client->getMessageFactory()->createRequest("https://www.verbix.com/webverbix/go.php?D1=3&T1=$verb_encode", 'GET');
      /** 
       * @see JonnyW\PhantomJs\Http\Response 
       **/
      $response = $client->getMessageFactory()->createResponse();

      $promise = new Promise(function () use (&$promise, $client, $request, $response) {
        $promise->resolve($client->send($request, $response));
      });
      return $promise->then(function($response) use ( $verb, $translation ) {
        if($response->getStatus() === 200) {
          $html = $response->getContent();
          return AnkiEDConjugator::processVerbixConjugationPage("french", $html, $verb, $translation);
        }
        return false;
      });
      // return $response->then(function($response) use ( $verb, $translation ) {
      //   return AnkiEDConjugator::processBabConjugationPage('german', $response->getBody()->getContents(), $verb, $translation);
      // });
    }
    $promise = new Promise(function () use (&$promise) {
      $promise->resolve(false);
    });
    return $promise->then(function($value) {
      return $value;
    });
  }
}