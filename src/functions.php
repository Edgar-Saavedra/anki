<?php 
namespace AnkiED;
use PHPExcel_IOFactory;

function readSheet($file, $sheet = 0) {
  $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
  $excelObj = $excelReader->load($file);
  $worksheet = $excelObj->getSheet($sheet);
  return $worksheet->getRowIterator();
}

function rowsToArray($rows) {
  $data = array();
  $header = array();
  if($rows) {
    while($rows->valid()) {
      $current_row = $rows->current();
      $cells = $current_row->getCellIterator();
      $cell_data = array();
      while($cells->valid()) {
        $current_cell = $cells->current();
        $column_value = $current_cell->getColumn();

        if($current_cell->getRow() > 1) {
          if($header[$column_value]['value']) {
            $cell_data[$header[$column_value]['value']] = array(
              'value' => $current_cell->getValue(),
            );
          }
        } else {
          // setup headers
          $header[$column_value] = array(
            'value' => $current_cell->getValue()
          );
        }

        $cells->next();
      }

      if(sizeof($cell_data)) {
        $data[] = $cell_data;
      }

      $rows->next();
    }
  }
  return $data;
}

function dataArrayFromSheet($file, $sheet = 0) {
  return rowsToArray(readSheet($file,$sheet));
}

function dataToJson($data) {
  return json_encode($data);
}
