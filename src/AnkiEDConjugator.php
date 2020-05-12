<?php 
namespace AnkiED;
use Sunra\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;


class AnkiEDConjugator {
  static $ALLOWED_TAGS = "<div><span><pre><p><br><hr><hgroup><h1><h2><h3><h4><h5><h6><ul><ol><li><dl><dt><dd><strong><em><b><i><u><img><a><abbr><address><blockquote>";
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
    $str = preg_replace('/\s+/', '', $str);
    return $str;
  }
  static function prepForVerbixGerman($str) {
    $unwanted_array = array('Ä'=>'a:','Ö'=>'o:','Ü'=>'u:','ß'=>'sZ','ä'=>'a:','ö'=>'o:','ü'=>'u:');
    $str = strtr( $str, $unwanted_array );
    $str = trim($str);
    $str = strtolower($str);
    $str = preg_replace('/\s+/', '', $str);
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
    $allowed_tags = AnkiEDConjugator::$ALLOWED_TAGS;
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
          if($node->parentNode)
          $node->parentNode->removeChild($node);
          $node->outertext = '';
        }
        foreach ($value->find('.adsbygoogle') as $node){
          if($node->parentNode)
          $node->parentNode->removeChild($node);
          $node->outertext = '';
        }
        foreach ($value->find('.advertising') as $node){
          if($node->parentNode)
          $node->parentNode->removeChild($node);
          $node->outertext = '';
        }
        foreach ($value->find('iframe') as $node){
          if($node->parentNode)
          $node->parentNode->removeChild($node);
          $node->outertext = '';
        }
        foreach ($value->find('script') as $node){
          if($node->parentNode)
          $node->parentNode->removeChild($node);
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
                    'tense' => strip_tags(trim($tense_block_header)),
                    'tense_category' => strip_tags(trim($header)),
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
                        'tense_category' => strip_tags(trim($header), $allowed_tags),
                        'tense' => strip_tags(trim($tense_block_header),$allowed_tags),
                        'pronoun' => strip_tags(trim($pronoun),$allowed_tags),
                        'conjugation' => strip_tags(trim($conjugation),$allowed_tags)
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
                  'tense_category' => strip_tags(trim($header), $allowed_tags),
                  'tense' => strip_tags(trim($tense_block_header),$allowed_tags),
                  'pronoun' => strip_tags(trim($pronoun),$allowed_tags),
                  'conjugation' => strip_tags(trim($conjugation),$allowed_tags)
                );
                $conjugations[$header_slug]['tenses'][$header_slug]['conjugations'][$pronoun ? AnkiEDConjugator::getSlug($lang, $pronoun) : $key] = $conjugation_item;
              }
            }
          } else {
            $dom = HtmlDomParser::str_get_html($value->innertext());
            foreach ($dom->find('h3') as $node){
              $node->outertext = '';
            }
            $conjugations[$header_slug]['content'] = strip_tags(trim($dom->innertext), $allowed_tags);
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
      $response = $client->getMessageFactory()->createResponse();
      $request = $client->getMessageFactory()->createRequest();
      $request->setMethod('GET');
      $request->setUrl("https://www.verbix.com/webverbix/German/$verb_encode.html");
      $promise = new Promise(function () use (&$promise, $client, $request, $response) {
        $promise->resolve($client->send($request, $response));
      });
      return $promise->then(function($response) use ( $verb, $translation, $verb_encode ) {
        if($response->getStatus() === 200) {
          krumo($verb. ' ---- status ---- '.$response->getStatus());
          $html = $response->getContent();
          $data = AnkiEDConjugator::processVerbixConjugationPage("german", $html, $verb, $translation);
          
          if($data['conjugations_found'] == false) {
            krumo($data['verb']. 'not found! but got a 200 check : https://de.bab.la/konjugieren/deutsch/'. $verb_encode. '.html');
          }
          return $data;
        }
        krumo('check : https://de.bab.la/konjugieren/deutsch/'. $verb_encode. '.html --- we got a status:' - $response->getStatus());
        return array(
          'verb' => $verb,
          'translation' => $translation,
          'conjugations_found' => false,
          'language' => "german",
          "not_in_verbix" => true
        );
      });
    }
    $promise = new Promise(function () use (&$promise, $verb, $translation, $verb_encode) {
      krumo($verb. 'not found! check : https://de.bab.la/konjugieren/deutsch/'. $verb_encode. '.html');
      $promise->resolve(array(
        'verb' => $verb,
        'translation' => $translation,
        'conjugations_found' => false,
        'language' => "german",
        "not_in_verbix" => true
      ));
    });
    return $promise->then(function($value) {
      return $value;
    });
  }

  static function stringCotains($needles, $haystack) {
    return count(array_intersect($needles, explode(" ", preg_replace("/[^A-Za-z0-9' -]/", "", $haystack))));
  }

  static function processLaroussePageDefinition($language, $html, $word, $translation) {
    $definition = array(
      'word' => $word,
      'translation' => $translation,
      'definition_found' => false,
      'language' => $language,
      'definition' => array(
        'word' => $word,
        'info' => array(),
        'value' => array(),
        'has_mp3' => false,
      ),
      'has_gender' => false,
      'not_found' => true,
    );
    if($html) {
      $dom = HtmlDomParser::str_get_html($html);
      $definition_block = $dom->find('#definition');
      if(sizeof($definition_block)) {
        $definition_block = $definition_block[0];
        $definitions = $definition_block->find('.DivisionDefinition');
        foreach($definitions as $key => $value) {
          $definition['definition']['value'][] = $value->innertext();
          $definition['definition_found'] = true;
        }
      }
      $header = $dom->find('.header-article');
      if(sizeof($header)) {
        $header = $header[0];
        $audio = $header->find('.AdresseDefinition [type="audio/mp3"]');
        $audio = sizeof($audio) ? $audio[0] : null;
        if($audio) {
          $mp3 = $audio->getAttribute('id');
          $info['value'] = 'https://www.larousse.fr/dictionnaires-prononciation/francais/tts/'.$mp3;
          $definition['definition']['has_mp3'] = true;
          $definition['definition']['info']['mp3'] = $info;
        }
        $grammar_info = $header->find('.CatgramDefinition');
        if(sizeof($grammar_info)) {
          $links = $grammar_info[0]->find('.lienconj');
          foreach($links as $key=>$value) {
            $href = $value->getAttribute('href');
            $value->setAttribute('href', 'https://www.larousse.fr/'.$href);
            $value->setAttribute('target', '_blank');
          }
          $grammar_info = $grammar_info[0]->innertext();
          $definition['definition']['info']['grammatical_info'] = array(
            'value' => $grammar_info
          );
          $type_info = array_map('trim',explode(' ',trim(strip_tags($grammar_info))));
          if(sizeof($type_info)) {
            if($type_info[0] = 'nom') {
              $genders = array(
                'masculin' => 'm',
                'féminin' => 'f'
              );
              foreach($genders as $key => $value) {
                if(in_array($key, $type_info)) {
                  $definition['has_gender'] = true;
                  $definition['gender'] = $value;
                }
              }
            }
          }
        }
      }
      $definition['not_found'] = false;
    } else {
      krumo('ATTENTION!: '.$word. ' -- no definition found!');
    }
    return $definition;
  }

  static function processDwdsPageDefinition($language, $html, $word, $translation) {
    $definition = array(
      'word' => $word,
      'translation' => $translation,
      'definition_found' => false,
      'language' => $language,
      'definition' => array(
        'word' => $word,
        'info' => array(),
        'value' => array(),
        'has_mp3' => false,
      ),
      'has_gender' => false,
      'not_found' => true,
    );
    if($html) {
      $dom = HtmlDomParser::str_get_html($html);
      $elems = $dom->find('.dwdswb-artikel');
      if(sizeof($elems)) {
        $word = $elems[0]->find('h1.dwdswb-ft-lemmaansatz');
        $word = $word[0] ? $word[0]->innertext() : null;

        $definition['definition']['word'] = $word;
        $defintion_info_block = $elems[0]->find('.dwdswb-ft-block');


        $definition['definition_found'] = false;
        $definition['information_found'] = false;
        foreach($defintion_info_block as $key=> $value) {
          $label = $value->find('.dwdswb-ft-blocklabel');
          $label_text = $label[0] ? $label[0]->innertext() : null;
          $text = $value->find('.dwdswb-ft-blocktext');
          $text_value = $text[0] ? $text[0]->innertext() : null;

          if($text_value && $label_text) {
            $definition['information_found'] = true;
            $info_label = AnkiEDConjugator::getSlug($language, trim($label_text))."_info";
            $info = array();
            $info['label'] = $label_text;
            $info['value'] = $text_value;
            if($info_label == 'aussprache__info') {
              $audio = $dom->find('[type="audio/mpeg"]');
              $audio = sizeof($audio) ? $audio[0] : null;
              if($audio) {
                $mp3 = $audio->getAttribute('src');
                $mp3 = preg_replace('/\/\//', 'https://', $mp3);
                $info['value'] = $mp3;
                $definition['definition']['has_mp3'] = true;
                $definition['definition']['info']['mp3'] = $info;
              }
            } else if($info_label == 'grammatik__info') {
              $definition['definition']['info']['grammatical_info'] = $info;
              $type_info = array_map('trim',explode('·',trim(strip_tags($text_value))));
              if(sizeof($type_info)) {
                $type_info = $type_info[0];
                if(strpos($type_info, 'Substantiv') == 0) {
                  $genders = array(
                    'Maskulinum' => 'm',
                    'Femininum' => 'f',
                    'Neutrum' => 'n'
                  );
                  foreach($genders as $key => $value) {
                    if(strpos($type_info, $key)) {
                      $definition['has_gender'] = true;
                      $definition['gender'] = $value;
                    }
                  }
                }
              }
            }
            else {
              $definition['definition']['info'][$info_label] = $info;
            }
          }
        }

        $bedeutung_def_container = $dom->find('#d-1-1');
        $bedeutung_def_container = $bedeutung_def_container[0] ? $bedeutung_def_container[0] : null;

        if($bedeutung_def_container) {
          $bedeutung_def = $bedeutung_def_container->find('.dwdswb-lesart-content .dwdswb-lesart-def .dwdswb-definitionen .dwdswb-definition');
          $bedeutung_def = $bedeutung_def[0] ? $bedeutung_def[0]->innertext() : null;
          if($bedeutung_def) {
            $definition['definition']['value'][0] = $bedeutung_def;
            $definition['definition_found'] = true;
          }
        }
        $definition['not_found'] = false;
      }
      else {
        krumo('ACHTUNG!: '.$word. ' -- no definition found!');
      }
    } else {
      krumo('ACHTUNG!: '.$word. ' -- no definition found!');
    }
    return $definition;
  }


  static function frenchDefinitionLarousse($client = null, $word = null, $translation = null) {
    if($word) {
      $word_encode = $word;
      $response = $client->requestAsync('GET', "https://www.larousse.fr/dictionnaires/francais/$word_encode");
      return $response->then(function($response) use ( $word, $translation, $word_encode ) {
        if($response->getStatusCode() == '200'){
          krumo($word. ' ---- status ---- '.'200 '."https://www.larousse.fr/dictionnaires/francais/$word_encode");
          $html = $response->getBody()->getContents();
          return AnkiEDConjugator::processLaroussePageDefinition('french', $html, $word, $translation, $word_encode);
        }
        krumo($word. ' ---- not found! Something is up. ---- '.$response->getStatusCode()." https://www.larousse.fr/dictionnaires/francais/$word_encode");
        return array(
          'word' => $word,
          'translation' => $translation,
          'definition_found' => false,
          'language' => 'french',
          'definition' => array(
            'word' => $word,
            'info' => array(),
            'value' => array(),
            'has_mp3' => false,
          ),
          'has_gender' => false,
          'not_found' => true,
        );
      });
    }
    $promise = new Promise(function () use ( $word, $translation, &$promise, $word_encode) {
      krumo($word. " ---- not found! Something is up. ----  https://www.dwds.de/wb/$word_encode");
      $promise->resolve(array(
        'word' => $word,
        'translation' => $translation,
        'definition_found' => false,
        'language' => 'french',
        'definition' => array(
          'word' => $word,
          'info' => array(),
          'value' => array(),
          'has_mp3' => false,
        ),
        'has_gender' => false,
        'not_found' => true,
      ));
    });
    return $promise->then(function($value) {
      return $value;
    });
  }

  //https://www.dwds.de/wb/W%C3%B6rterbuch
  static function germanDefinitionDwds($client = null, $word = null, $translation = null) {
    if($word) {
      $word_encode = urlencode($word);
      $response = $client->requestAsync('GET', "https://www.dwds.de/wb/$word_encode");
      return $response->then(function($response) use ( $word, $translation, $word_encode ) {
        if($response->getStatusCode() == '200'){
          krumo($word. ' ---- status ---- '.'200 '."https://www.dwds.de/wb/$word_encode");
          $html = $response->getBody()->getContents();
          return AnkiEDConjugator::processDwdsPageDefinition('german', $html, $word, $translation, $word_encode);
        }
        krumo($word. ' ---- not found! Something is up. ---- '.$response->getStatusCode()." https://www.dwds.de/wb/$word_encode");
        return array(
          'word' => $word,
          'translation' => $translation,
          'definition_found' => false,
          'language' => 'german',
          'definition' => array(
            'word' => $word,
            'info' => array(),
            'value' => array(),
            'has_mp3' => false,
          ),
          'has_gender' => false,
          'not_found' => true,
        );
      });
    }
    $promise = new Promise(function () use ( $word, $translation, &$promise, $word_encode) {
      krumo($word. " ---- not found! Something is up. ----  https://www.dwds.de/wb/$word_encode");
      $promise->resolve(array(
        'word' => $word,
        'translation' => $translation,
        'definition_found' => false,
        'language' => 'german',
        'definition' => array(
          'word' => $word,
          'info' => array(),
          'value' => array(),
          'has_mp3' => false,
        ),
        'has_gender' => false,
        'not_found' => true,
      ));
    });
    return $promise->then(function($value) {
      return $value;
    });
  }


  static function getFrenchConjugationVerbix($client = null, $verb = null, $translation = null) {
    if($verb) {
      $verb_encode = AnkiEDConjugator::prepForVerbixFrench($verb);
      $verb_encode = urlencode($verb_encode);
      $response = $client->getMessageFactory()->createResponse();
      $request = $client->getMessageFactory()->createRequest();
      $request->setMethod('GET');
      $request->setUrl("https://www.verbix.com/webverbix/go.php?D1=3&T1=$verb_encode");
      $promise = new Promise(function () use (&$promise, $client, $request, $response) {
        $promise->resolve($client->send($request, $response));
      });
      return $promise->then(function($response) use ( $verb, $translation ) {
        krumo($verb. ' ---- status ---- '.$response->getStatus());
        if($response->getStatus() === 200) {
          $html = $response->getContent();
          return AnkiEDConjugator::processVerbixConjugationPage("french", $html, $verb, $translation);
        }
        return array(
          'verb' => $verb,
          'translation' => $translation,
          'conjugations_found' => false,
          'language' => "french",
          "not_in_verbix" => true
        );
      });
      // return $response->then(function($response) use ( $verb, $translation ) {
      //   return AnkiEDConjugator::processBabConjugationPage('german', $response->getBody()->getContents(), $verb, $translation);
      // });
    }
    $promise = new Promise(function () use (&$promise, $verb, $translation) {
      $promise->resolve(array(
        'verb' => $verb,
        'translation' => $translation,
        'conjugations_found' => false,
        'language' => "french",
        "not_in_verbix" => true
      ));
    });
    return $promise->then(function($value) {
      return $value;
    });
  }

  static function getFrenchConjugationLarousse($client = null, $verb = null, $translation = null) {
    if($verb) {
      $verb_encode = $verb;
      $response = $client->requestAsync('GET', "https://www.larousse.fr/conjugaison/francais/$verb_encode");
      return $response->then(function($response) use ( $verb, $translation, $verb_encode ) {
        if($response->getStatusCode() == '200'){
          krumo($verb. ' ---- status ---- '.'200 '."https://www.larousse.fr/conjugaison/francais/$verb_encode");
          $html = $response->getBody()->getContents();
          return AnkiEDConjugator::processLaroussePageConjugation('french', $html, $verb, $translation);
        }
        krumo($verb. ' ---- not found! Something is up. ---- '.$response->getStatusCode()." https://www.larousse.fr/conjugaison/francais/$verb_encode");
        return array(
          'verb' => $verb,
          'translation' => $translation,
          'conjugations_found' => false,
          'language' => "french",
          'has_mp3' => false,
        );
      });
    }
    $promise = new Promise(function () use ( $verb, $translation, &$promise, $verb_encode) {
      krumo($verb. " ---- not found! Something is up. ----  https://www.dwds.de/wb/$verb_encode");
      $promise->resolve(array(
        'verb' => $verb,
        'translation' => $translation,
        'conjugations_found' => false,
        'language' => "french",
        'has_mp3' => false,
      ));
    });
    return $promise->then(function($value) {
      return $value;
    });
  }

  static function _larousseProcessConjugationCategory($parentEl, $conjugations, $lang) {
    if(sizeof($parentEl)) {
      $parentEl = $parentEl[0];
      $header = $parentEl->find('h2');
      if(sizeof($header)) {
        $header = $header[0];
        $header = $header->innerText();
        $header = strip_tags(trim($header));
        $header_slug = AnkiEDConjugator::getSlug($lang, $header);

        $tense_blocks = $parentEl->find('div article');
        foreach($tense_blocks as $key => $value) {
          $tense_block_header = $value->find('h3');
          $tense_block_header = $tense_block_header[0];
          $tense_block_header = $tense_block_header->innertext();
          $tense_block_header = strip_tags(trim($tense_block_header));
          $tense_block_header = str_replace('-','',$tense_block_header);
          $cojugations_pronouns = $value->find('ul li');

          $tense_block_header_slug = AnkiEDConjugator::getSlug($lang, $tense_block_header);
          $conjugations[$header_slug]['tenses'][$tense_block_header_slug] = array(
            'tense' => strip_tags(trim($tense_block_header)),
            'tense_category' => $header,
            'conjugations' => array()
          );
          foreach($cojugations_pronouns as $key => $item) {
            $pronoun = $item->find('.pronom');
            $conjugation = $item->find('.verbe');
            if(sizeof($pronoun) && sizeof($conjugation)) {
              $pronoun = $pronoun[0];
              $pronoun = strip_tags(trim($pronoun->innerText()));
              $conjugation = $conjugation[0];
              $conjugation = strip_tags(trim($conjugation->innerText()));
              $conjugation_item = array(
                'tense_category' => $header,
                'tense' => $tense_block_header,
                'pronoun' => $pronoun,
                'conjugation' => $conjugation
              );
              $conjugations['conjugations_found'] = true;
              $conjugations[$header_slug]['tenses'][$tense_block_header_slug]['conjugations'][$pronoun ? AnkiEDConjugator::getSlug($lang, str_replace(',','',$pronoun)) : $key] = $conjugation_item;
            }
          }
        }
      }
    }
    return $conjugations;
  }

  static function processLaroussePageConjugation($lang = null, $html = null, $verb = null, $translation = null) {
    $conjugations = array(
      'verb' => $verb,
      'translation' => $translation,
      'conjugations_found' => false,
      'language' => $lang,
      'has_mp3' => false,
    );
    if($html) {
      $dom = HtmlDomParser::str_get_html($html);
      $conjugations = AnkiEDConjugator::_larousseProcessConjugationCategory($dom->find('#indicatif'), $conjugations, $lang);
      $conjugations = AnkiEDConjugator::_larousseProcessConjugationCategory($dom->find('#subjonctif'), $conjugations, $lang);
      $conjugations = AnkiEDConjugator::_larousseProcessConjugationCategory($dom->find('#conditionnel'), $conjugations, $lang);
      $conjugations = AnkiEDConjugator::_larousseProcessConjugationCategory($dom->find('#imperatif'), $conjugations, $lang);
      $conjugations = AnkiEDConjugator::_larousseProcessConjugationCategory($dom->find('#infinitif'), $conjugations, $lang);
      $conjugations = AnkiEDConjugator::_larousseProcessConjugationCategory($dom->find('#participe'), $conjugations, $lang);
      $mp3 = $dom->find('.art-conj header audio');
      if(sizeof($mp3)) {
        $mp3 = $mp3[0];
        $mp3 = $mp3->getAttribute('id');
        if($mp3) {
          $mp3 = 'https://www.larousse.fr/dictionnaires-prononciation/francais/tts/'.$mp3;
          $conjugations['has_mp3'] = true;
          $conjugations['mp3'] = $mp3;
        }
      }
      $info = $dom->find('.art-conj header .aux');
      if(sizeof($info)) {
        $info = $info[0];
        foreach($info->find('a') as $key => $value) {
          $href = $value->getAttribute('href');
          $value->setAttribute('href', 'https://www.larousse.fr/'.$href);
          $value->setAttribute('target', '_blank');
        }
        $conjugations['aux_info'] = $info->innertext(); 
      }
      $def = $dom->find('.art-conj header .def');
      if(sizeof($def)) {
        $def = $def[0];
        foreach($def->find('a') as $key => $value) {
          $href = $value->getAttribute('href');
          $value->setAttribute('href', 'https://www.larousse.fr/'.$href);
          $value->setAttribute('target', '_blank');
        }
        $conjugations['definition'] = $def->innertext(); 
      }
    }
    return $conjugations;
  }

}