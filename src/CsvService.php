<?php

namespace Drupal\csv_data_download;

use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class CsvService.
 */
class CsvService {

  /**
   * Date manager.
   *
   * @var Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateManager;

  /**
   * Constructs a new CsvService object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_manager
   *   The Date manager Service.
   */
  public function __construct(DateFormatterInterface $date_manager) {
    $this->dateManager = $date_manager;
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
   *   Full file destination.
   */
  public function getFileDestination($scheme, $filename) {
    return $scheme . $filename . '.csv';
  }

  /**
   * Opens CSV file handler.
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   *
   * @return bool|resource
   *   File handler or FALSE.
   */
  public function openFileHandler($scheme, $filename) {
    $csv_destination = $this->getFileDestination($scheme, $filename);
    $fh = fopen($csv_destination, 'w');
    return $fh;
  }

  /**
   * Sets CSV header.
   *
   * @param resource $fh
   *   File handler.
   *
   * @return bool|int
   *   String length or FALSE.
   */
  public function setCsvHeader($fh) {
    $header_row = [
      'BRAND',
      'SOURCE',
      'GENDER',
      'LAST NAME',
      'FIRST NAME',
      'EMAIL',
      'LANGUAGE',
      'COLLECT DATE',
      'BIRTHDATE',
      'ADDRESS 1',
      'ADDRESS 2',
      'CITY',
      'ZIPCODE',
      'COUNTRY',
      'CHOSEN_COUNTRY',
    ];
    return fputcsv($fh, $header_row, ',');
  }

  /**
   * Writes a row to the file.
   *
   * @param resource $fh
   *   File handler.
   * @param array $row
   *   An array of values come from the DB.
   */
  public function writeRow($fh, array $row) {
    fputcsv($fh, $this->formatRow($row), ',');
  }

  /**
   * Formats a row.
   *
   * @param array $row
   *   Unformatted data.
   *
   * @return array
   *   Formatted data.
   */
  protected function formatRow(array $row) {
    return [
      'NESTLE',
      'CCSD World Cup 2018',
      $row['worldcup_gender'] == 'Male' ? '1' : '2',
      !empty($row['worldcup_surname']) ? $row['worldcup_surname'] : NULL,
      !empty($row['worldcup_name']) ? $row['worldcup_name'] : NULL,
      !empty($row['worldcup_email']) ? $row['worldcup_email'] : NULL,
      $row['langcode'],
      $this->dateManager->format((int) $row['created'], 'custom', 'd-m-Y H:i:s'),
      !empty($row['worldcup_birthdate']) ? $row['worldcup_birthdate'] : NULL,
      !empty($row['worldcup_address']) ? $row['worldcup_address'] : NULL,
      !empty($row['worldcup_address1']) ? $row['worldcup_address1'] : NULL,
      !empty($row['worldcup_city']) ? $row['worldcup_city'] : NULL,
      !empty($row['worldcup_postcode']) ? $row['worldcup_postcode'] : NULL,
      'CH',
      !empty($row['chosen_country']) ? $row['chosen_country'] : NULL,
    ];
  }

}
