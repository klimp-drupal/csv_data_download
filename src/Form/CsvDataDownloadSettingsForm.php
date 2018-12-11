<?php

namespace Drupal\csv_data_download\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CsvDataDownloadSettingsForm.
 */
class CsvDataDownloadSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'csv_data_download.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csv_data_download_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('csv_data_download.settings');
    $form['tmp_folder_scheme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tmp folder scheme'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('tmp_folder_scheme'),
      '#required' => TRUE,
    ];
    $form['tmp_files_max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tmp files max age'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('tmp_files_max_age'),
      '#required' => TRUE,
    ];
    $form['use_zip_password'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use zip password'),
      '#default_value' => $config->get('use_zip_password'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Numeric max age validation.
    if (!intval($form_state->getValue('tmp_files_max_age'))) {
      $form_state->setErrorByName('tmp_files_max_age', $this->t('The field needs to be a number'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('csv_data_download.settings')
      ->set('tmp_folder_scheme', $form_state->getValue('tmp_folder_scheme'))
      ->set('tmp_files_max_age', intval($form_state->getValue('tmp_files_max_age')))
      ->set('use_zip_password', $form_state->getValue('use_zip_password'))
      ->save();
  }

}
