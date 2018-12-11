<?php

namespace Drupal\csv_data_download;

use Drupal\Core\File\FileSystem;
use Drupal\Component\Utility\Random;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ArchiveService.
 */
class ArchiveService {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a new ArchiveService object.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The File System service..
   */
  public function __construct(FileSystem $file_system/*, $usePassword*/) {
    $this->fileSystem = $file_system;
  }

  /**
   * Generates random password.
   *
   * @return string
   *   Randomly generated password.
   */
  public function generatePassword() {
    $random = new Random();
    return $random->name();
  }

  /**
   * Generates zip command string.
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   * @param string $password
   *   Password to encrypt zip.
   *
   * @return string
   *   Console zip command ready to be run.
   */
  protected function generateZipCommand($scheme, $filename, $password) {
    $to_zip = $this->fileSystem->realpath($scheme . $filename . '.csv');
    $zip_path_absolute = $this->fileSystem->realpath($scheme . $filename . '.zip');

    $command = 'zip -j';
    // Add password if required.
    if ($password) {
      $command .= 'P "' . $password . '"';
    }
    $command .= ' ' . $zip_path_absolute . ' ' . $to_zip;
    return $command;
  }

  /**
   * Runs zip command.
   *
   * @param string $command
   *   Command.
   *
   * @return mixed
   *   0 if success, error code otherwise.
   *
   * @throws \Exception
   */
  protected function generateZipArchive($command) {
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
      throw new \Exception('Exit code: ' . $return_var . implode('. ', $output));
    }
    return $return_var;
  }

  /**
   * Creates Zip Archive with a command line.
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   * @param string|bool $password
   *   Password to encrypt zip. FALSE if it is not needed.
   *
   * @return string
   *   Zip Archive URI. Scheme
   *
   * @throws \Exception
   */
  public function createZipArchive($scheme, $filename, $password = FALSE) {
    $command = $this->generateZipCommand($scheme, $filename, $password);
    $this->generateZipArchive($command);
    return $this->getFileDestination($scheme, $filename);
  }

  /**
   * Returns file scheme path.
   *
   * @param string $scheme
   *   Scheme.
   * @param string $filename
   *   Filename without an extension.
   *
   * @return string
   *   Full path to the file.
   */
  public function getFileDestination($scheme, $filename) {
    return $scheme . $filename . '.zip';
  }

  /**
   * Returns a file download window.
   *
   * @param string $zip_path
   *   File path to download.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Response object.
   */
  public function returnFileDownload($zip_path) {
    $headers = [
      'Content-Type' => 'application/zip',
      'Content-Disposition' => 'attachment; filename="' . $this->fileSystem->basename($zip_path) . '"',
    ];
    return new BinaryFileResponse($zip_path, 200, $headers);
  }

}
