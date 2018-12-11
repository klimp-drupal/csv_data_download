<?php

namespace Drupal\csv_data_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\csv_data_download\ArchiveService;

/**
 * Controller for csv_data_download.zip_download.
 */
class CsvDataDownloadController extends ControllerBase {

  /**
   * Archive service.
   *
   * @var \Drupal\csv_data_download\ArchiveService
   */
  protected $archiveService;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('csv_data_download.archive')
    );
  }

  /**
   * CsvDataDownloadController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory service.
   * @param \Drupal\csv_data_download\ArchiveService $archiveService
   *   Archive service.
   */
  public function __construct(
    ConfigFactory $config_factory,
    ArchiveService $archiveService
  ) {
    $this->configFactory = $config_factory;
    $this->archiveService = $archiveService;
  }

  /**
   * Action callback for the csv_data_download.zip_download route.
   *
   * @param string $zip_filename
   *   Filename without an extension.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Binary file response.
   */
  public function responseZip($zip_filename) {
    $scheme = $this->configFactory->get('csv_data_download.settings')->get('tmp_folder_scheme');
    $zip_path = $this->archiveService->getFileDestination($scheme, $zip_filename);
    return $this->archiveService->returnFileDownload($zip_path);
  }

}
