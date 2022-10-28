<?php

/**
 * HE Platform Controller .
 * @file
 * Contains \Drupal\cambridge_he_platform\Controller\HePlatformController.
 */
namespace Drupal\cambridge_he_platform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mongodb\Client;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Response;


class HePlatformController extends ControllerBase {

  /**
  * {@inheritdoc}
  */
  public function HeChkboxList() {
    $conn = Database::getConnection();
    $chkbx_data = $_POST['result'];
    $status =0;
    foreach($chkbx_data as $row){    
        $isbn_exists = $conn->select('he_platform','hep')
        ->fields('hep',array('status'))->condition('isbn', $row, '=')
        ->execute()->fetchAssoc();
    if($isbn_exists['status'] == 1){
        $conn->update('he_platform')
        ->fields(array('status' => 0,))->condition('isbn', $row, '=')
        ->execute();
        $status = 1;
    }else {
        $conn->update('he_platform')
        ->fields(array('status' => 1,))->condition('isbn', $row, '=')
        ->execute();
        $status = 1;
     }       
    }  
      $response = new Response();
      $response->setContent($status);
      return $response;     
 }
}
