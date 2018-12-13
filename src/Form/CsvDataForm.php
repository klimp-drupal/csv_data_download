<?php

namespace Drupal\csv_data_download\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Driver\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\csv_data_download\CsvService;
use Drupal\csv_data_download\ArchiveService;
use Drupal\csv_data_download\Event\ArchiveDownloadedEvent;

/**
 * Implements the CsvDataForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class CsvDataForm extends FormBase {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * CSV service.
   *
   * @var \Drupal\csv_data_download\CsvService
   */
  protected $csvService;

  /**
   * Archive service.
   *
   * @var \Drupal\csv_data_download\ArchiveService
   */
  protected $archiveService;

  /**
   * Drupal file stream wrapper URI scheme.
   *
   * @var string
   */
  protected $scheme;

  /**
   * CsvDataForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   Current logged in user.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   * @param \Drupal\csv_data_download\CsvService $csvService
   *   Csv service.
   * @param \Drupal\csv_data_download\ArchiveService $archiveService
   *   Archive service.
   */
  public function __construct(
    Connection $database,
    AccountProxy $current_user,
    EventDispatcherInterface $event_dispatcher,
    LoggerChannelFactory $logger_factory,
    CsvService $csvService,
    ArchiveService $archiveService
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->eventDispatcher = $event_dispatcher;
    $this->loggerFactory = $logger_factory;

    $this->csvService = $csvService;
    $this->archiveService = $archiveService;

    $this->scheme = $this->config('csv_data_download.settings')->get('tmp_folder_scheme');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('event_dispatcher'),
      $container->get('logger.factory'),
      $container->get('csv_data_download.csv'),
      $container->get('csv_data_download.archive')
    );
  }

  /**
   * Returns form id.
   *
   * @return string
   *   Form id.
   */
  public function getFormId() {
    return 'csv_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $session = $this->getRequest()->getSession();

    // Attach the library when redirected from batch.
    if ($zip_filename = $session->get('zip_filename')) {
      $form['download_link_container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'download-link-container'],
        '#attached' => [
          'library' => ['csv_data_download/file_download_trigger'],
          'drupalSettings' => [
            'csvDataDownload' => [
              'downloadZip' => $zip_filename,
            ],
          ],
        ],
      ];
      $session->remove('zip_filename');
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('CSV Data Download'),
    ];

    return $form;
  }

  /**
   * Implements form validation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!file_prepare_directory($this->scheme, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
      $form_state->setErrorByName('submit', $this->t('Failed to create %directory.', ['%directory' => $this->scheme]));
    }
  }

  /**
   * Implements form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Calculate the submissions.
    $query = $this->database->select('webform_submission', 'ws');
    $query->condition('ws.webform_id', 'worldcup_vote');
    $count = $query->countQuery()->execute()->fetchField();

    $filename = 'webform_csv_data-' . time();
    $csv_destination = $this->csvService->getFileDestination($this->scheme, $filename);
    $fh = fopen($csv_destination, 'w');
    $this->csvService->setCsvHeader($fh);
    fclose($fh);

    $operations = [];
    for ($i = 0; $i < $count; $i++) {
      $operations[] = [
        [$this, 'getWebformSubmission'],
        [
          $i,
          $csv_destination,
          $form_state,
          $filename,
        ],
      ];
    }
    $batch = [
      'operations' => $operations,
      'finished' => [$this, 'getWebformSubmissionFinishedCallback'],
    ];

    batch_set($batch);

  }

  /**
   * Batch operation callback.
   *
   * @param int $index
   *   Batch operation index.
   * @param string $csv_destination
   *   CSV file destination.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $filename
   *   Filename.
   * @param array $context
   *   Context.
   */
  public function getWebformSubmission($index, $csv_destination, FormStateInterface $form_state, $filename, array &$context) {

    if (empty($context['results']['csv_destination'])) {
      $context['results']['csv_destination'] = $csv_destination;
      $context['results']['form_state'] = $form_state;
      $context['results']['filename'] = $filename;
    }

    // Get current submission id.
    $query = $this->database->select('webform_submission', 'ws');
    $query->condition('ws.webform_id', 'worldcup_vote');
    $query->range($index, 1);
    $query->orderBy('ws.sid');
    $query->fields('ws', ['sid']);
    $sid = $query->execute()->fetch();

    // Get data by the submission id.
    $query = $this->database->select('webform_submission_data', 'wsd');
    $query->join('webform_submission', 'ws', 'ws.sid = wsd.sid');
    $query->condition('wsd.webform_id', 'worldcup_vote');
    $query->condition('wsd.sid', $sid->sid);
    $query->fields('wsd', ['sid', 'name', 'value']);
    $query->fields('ws', ['langcode', 'created']);
    $query->leftJoin('node_field_data', 'nfd',
      "nfd.nid = wsd.value AND wsd.name = 'worldcup_vote' AND nfd.langcode = 'de'"
    );
    $query->fields('nfd', ['title']);
    $result = $query->execute();

    $row = [];
    while ($item = $result->fetch()) {

      // Follow up the row with necessary entity data.
      if (empty($row['langcode']) && empty($row['created'])) {
        $row['langcode'] = $item->langcode;
        $row['created'] = $item->created;
      }

      // Chosen country is not null only when we process 'worldcup_vote' value.
      if (!empty($item->title)) {
        $row['chosen_country'] = $item->title;
      }

      // Create regular row element.
      $row[$item->name] = $item->value;
    }

    // Record the last row. It should be done outside of the loop.
    if (!empty($row)) {
      $fh = fopen($csv_destination, 'a');
      $this->csvService->writeRow($fh, $row);
      fclose($fh);
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Flag if batch is successful.
   * @param array $results
   *   Results array.
   * @param array $operations
   *   Operations array.
   */
  public function getWebformSubmissionFinishedCallback($success, array $results, array $operations) {

    if ($success) {
      // Generate a password if needed.
      $password = FALSE;
      if ($this->config('csv_data_download.settings')->get('use_zip_password')) {
        $password = $this->archiveService->generatePassword();
      }

      try {
        // Create encrypted Zip Archive.
        $zip_path = $this->archiveService->createZipArchive($this->scheme, $results['filename'], $password);
        $this->getRequest()->getSession()->set('zip_filename', $results['filename']);
      }
      catch (\Exception $e) {
        $this->handleException($e);
      }

      if ($password) {
        // Dispatch ArchiveDownloadedEvent event.
        $event = new ArchiveDownloadedEvent($this->currentUser, $password);
        $this->eventDispatcher->dispatch(ArchiveDownloadedEvent::EVENT_NAME, $event);
      }
    }
    else {
      drupal_set_message('The error occurred. Check the log', 'error');
    }
  }

  /**
   * Helps to handle an exception.
   *
   * @param \Drupal\Driver\Exception\Exception $e
   *   An exception caught.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the form page.
   */
  protected function handleException(Exception $e) {
    drupal_set_message($e->getMessage(), 'error');
    $this->loggerFactory->get('csv_data_download')->error('@message', [
      '@message' => $e->getMessage(),
    ]);
    return $this->redirect('csv_data_download.download_form');
  }

}
