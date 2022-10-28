<?php

namespace Drupal\cambridge_he_platform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Provides the form for adding isbn.
 */
class CambridgeHEPlatformForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cambridge_he_platform_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [
      '#attributes' => ['enctype' => 'multipart/form-data'],
    ];

    $form['file_upload_details'] = [
      '#markup' => t('<b>Cambridge HE Platform</b>'),
    ];

    $validators = [
      'file_validate_extensions' => ['csv'],
    ];
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#name' => 'csv_file',
      '#title' => t('File *'),
      '#description' => t('CSV format only'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://content/csv_files/',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
    $form['actions']['#weight'] = 1;

    $conn = Database::getConnection();
    $list_data = $conn->select('he_platform', 'he')
      ->fields('he', ['isbn', 'type', 'title', 'url', 'status'])->execute();

    $html = "<div class='trackusers'>
    <table id='he_list' class='display'>
    <thead>
    <tr>
    <th></th>
    <th>ISBN</th>    
    <th>Description/Url</th>
    <th>Type</th>
    <th>Enabled</th>
    </tr>
    </thead><tbody>";

    foreach ($list_data as $list) {
      $html .= "<tr>
      <td>$list->isbn </td>
      <td><a href='".$list->url."' id='isbn_text'>" . $list->isbn . "</a></td>
      <td><a href='".$list->url."'>" . $list->title . "</a></td>
      <td>".$list->type."</td>
      <td id='" . $list->isbn . "'>" . $list->status . "</td>
      </tr>";
    }
    $html .= "</tbody></table></div>";
    $form['table'] = [
      '#markup' => $html,
      '#weight' => 4,
    ];

    $form['button_save'] = [
      '#type' => 'button',
      '#value' => 'Save',
      '#button_type' => 'primary',
      '#weight' => 7,
      '#attributes' => ['id' => 'button_save'],
      '#attributes' => ['class' => ['button_save']],
    ];
    return $form;

  }

 
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('csv_file') == NULL) {
      $form_state->setErrorByName('csv_file', $this->t('upload proper File.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $file = \Drupal::entityTypeManager()->getStorage('file')
    // Just FYI. The file id will be stored as an array.
      ->load($form_state->getValue('csv_file')[0]);

    $full_path = $file->get('uri')->value;
    $file_name = basename($full_path);

    try {
      $inputFileName = \Drupal::service('file_system')->realpath('public://content/csv_files/' . $file_name);
      $spreadsheet = IOFactory::load($inputFileName);
      $sheetData = $spreadsheet->getActiveSheet();

      $rows = [];
    foreach ($sheetData->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);
        $cells = [];
        foreach ($cellIterator as $cell) {
          $cells[] = $cell->getValue();
        }
        $rows[] = $cells;
    }
      // ====remove first item since it is the header row
      array_shift($rows);

    foreach ($rows as $row) {
        // Insert/Update uploaded csv data into custom database table.
        try {
          $conn = Database::getConnection();
          $field = $rows;

          $fields["isbn"] = $row[0];
          $fields["title"] = $row[1];
          $fields["type"] = $row[2];
          $fields["url"] = $row[3];

          $conn->merge('he_platform')
            ->key(['isbn' => $fields["isbn"]])
            ->fields([
              'isbn' => $fields["isbn"],              
              'title' => $fields["title"],
              'type' => $fields["type"],
              'url' => $fields["url"],
            ])
            ->execute();
         
        }
        catch (Exception $ex) {
          \Drupal::logger('cambridge_he_platform')->error($ex->getMessage());
        }
      }
      \Drupal::messenger()->addMessage('The isbn data has been imported successfully');
    }
    catch (Exception $e) {
      \Drupal::logger('type')->error($e->getMessage());
    }
  }

  /**
   * Custom ajax submit handler for the form. Returns results set.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    return $form['isbn_list'];
  }

}
