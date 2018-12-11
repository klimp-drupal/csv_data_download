<?php

namespace Drupal\Tests\csv_data_download\Unit;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\csv_data_download\CsvService;

/**
 * Tests the CsvService class.
 *
 * @coversDefaultClass \Drupal\csv_data_download\CsvService
 * @group csv_data_download
 *
 * @package Drupal\Tests\csv_data_download\Unit
 */
class CsvServiceTest extends UnitTestCase {

  /**
   * Tested class object.
   *
   * @var Drupal\csv_data_download\CsvService
   */
  protected $csvService;

  /**
   * Prophecy to mock dateManager.
   *
   * @var object
   */
  protected $dateManagerProphecy;

  /**
   * Dummy file handler.
   *
   * @var resource
   */
  protected $fileHandler;

  /**
   * Get an accessible method using reflection.
   */
  public function getAccessibleMethod($class_name, $method_name) {
    $class = new \ReflectionClass($class_name);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * Mocks CsvService.
   *
   * @param array $stub_methods
   *   CsvService methods to stub.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   Mock object.
   */
  public function getCsvServiceMock(array $stub_methods) {
    return $this->getMockBuilder(CsvService::class)
      ->setConstructorArgs([$this->dateManagerProphecy->reveal()])
      ->setMethods($stub_methods)
      ->getMock();
  }

  /**
   * Creates new CsvService object.
   *
   * Opens test file handler.
   */
  public function setUp() {
    parent::setUp();

    // Mock $date_manager DI.
    $prophecy = $this->prophesize(DateFormatterInterface::class);

    // Predefine format() method returned values.
    $prophecy->format(1523864610, "custom", "d-m-Y H:i:s")->willReturn('16-04-2018 7:43:30');
    $prophecy->format(1542112133, "custom", "d-m-Y H:i:s")->willReturn('13-11-2018 12:28:53');

    $this->csvService = new CsvService($prophecy->reveal());
    $this->fileHandler = fopen('php://temp/pii_data/filename.csv', 'w');
    $this->dateManagerProphecy = $prophecy;
  }

  /**
   * Tests CsvService::getFileDestination()
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   * @param string $expected
   *   CSV file path coming from data provider.
   *
   * @dataProvider getFileDestinationDataProvider
   *
   * @see CsvService::getFileDestination()
   */
  public function testGetFileDestination($scheme, $filename, $expected) {
    $file_destination = $this->csvService->getFileDestination($scheme, $filename);
    $this->assertEquals($expected, $file_destination);
  }

  /**
   * Data provider for testGetFileDestination().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - scheme
   *   - filename
   *   - expected path
   *
   * @see CsvServiceTest::testGetFileDestination()
   */
  public function getFileDestinationDataProvider() {
    return [
      ['php://pii_data/', 'filename', 'php://pii_data/filename.csv'],
      ['temp://', 'filename', 'temp://filename.csv'],
    ];
  }

  /**
   * Tests CsvService::openFileHandler().
   *
   * @dataProvider openFileHandlerDataProvider
   *
   * @see CsvService::openFileHandler()
   */
  public function testOpenFileHandler($scheme, $filename) {
    $fh = $this->csvService->openFileHandler($scheme, $filename);
    $this->assertNotFalse($fh);
    $this->assertInternalType('resource', $fh);
    fclose($fh);
  }

  /**
   * Data provider for testOpenFileHandler().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - $scheme
   *   - $filename
   *
   * @see CsvServiceTest::testOpenFileHandler()
   */
  public function openFileHandlerDataProvider() {
    return [
      ['php://temp/pii_data/', 'filename'],
    ];
  }

  /**
   * Tests CsvService::setCsvHeader().
   *
   * @see CsvService::setCsvHeader()
   */
  public function testSetCsvHeader() {
    $length = $this->csvService->setCsvHeader($this->fileHandler);
    $this->assertEquals(145, $length);
  }

  /**
   * Tests CsvService::writeRow().
   *
   * @dataProvider formatRowDataProvider
   *
   * @see CsvService::writeRow()
   */
  public function testWriteRow($row, $expected) {
    $csv_service_mock = $this->getCsvServiceMock(
      ['formatRow']
    );

    // We want to test these methods get called.
    $csv_service_mock->expects($this->once())
      ->method('formatRow')
      ->willReturn([]);

    $csv_service_mock->writeRow($this->fileHandler, $row);
  }

  /**
   * Tests CsvService::formatRow()
   *
   * @dataProvider formatRowDataProvider
   *
   * @see CsvService::formatRow()
   */
  public function testFormatRow($row, $expected) {
    // Get a reflected, accessible version of the formatRow() method.
    $private_method = $this->getAccessibleMethod(
      'Drupal\csv_data_download\CsvService',
      'formatRow'
    );

    // Use the reflection to invoke on the object.
    $formatted = $private_method->invokeArgs($this->csvService, [$row]);

    $this->assertArrayEquals($expected, $formatted);
  }

  /**
   * Data provider for testWriteRow().
   *
   * Data provider for testFormatRow().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - $row
   *   - $expected
   *
   * @see CsvServiceTest::testWriteRow()
   * @see CsvServiceTest::testFormatRow()
   */
  public function formatRowDataProvider() {
    $set1 = [
      'tested' => [
        'langcode' => 'de',
        'created' => '1523864610',
        'worldcup_address' => 'address',
        'worldcup_address1' => 'address1',
        'worldcup_city' => 'city',
        'worldcup_email' => 'email@email.com',
        'worldcup_gender' => 'Male',
        'worldcup_name' => 'Name',
        'worldcup_postcode' => '23454-5324',
        'worldcup_surname' => 'Surname',
        'worldcup_telephone' => 'telephone',
        'worldcup_tos' => '1',
        'worldcup_vote' => '',
      ],
      'expected' => [
        'NESTLE',
        'CCSD World Cup 2018',
        '1',
        'Surname',
        'Name',
        'email@email.com',
        'de',
        '16-04-2018 7:43:30',
        NULL,
        'address',
        'address1',
        'city',
        '23454-5324',
        'CH',
        NULL,
      ],
    ];

    $set2 = [
      'tested' => [
        'langcode' => 'fr',
        'created' => '1542112133',
        'worldcup_address' => NULL,
        'worldcup_address1' => NULL,
        'worldcup_city' => NULL,
        'worldcup_email' => NULL,
        'worldcup_gender' => 'Female',
        'worldcup_name' => NULL,
        'worldcup_postcode' => NULL,
        'worldcup_surname' => NULL,
        'worldcup_telephone' => 'telephone',
        'worldcup_tos' => '1',
        'worldcup_vote' => '',
      ],
      'expected' => [
        'NESTLE',
        'CCSD World Cup 2018',
        '2',
        NULL,
        NULL,
        NULL,
        'fr',
        '13-11-2018 12:28:53',
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        'CH',
        NULL,
      ],
    ];

    return [
      [$set1['tested'], $set1['expected']],
      [$set2['tested'], $set2['expected']],
    ];
  }

  /**
   * Unset the CsvService object.
   *
   * Unset opened file handler.
   */
  public function tearDown() {
    unset($this->csvService);
    fclose($this->fileHandler);
  }

}
