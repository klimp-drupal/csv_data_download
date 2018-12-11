<?php

namespace Drupal\Tests\csv_data_download\Unit;

use Drupal\Core\File\FileSystem;
use Drupal\Tests\UnitTestCase;
use Drupal\csv_data_download\ArchiveService;

/**
 * Tests the ArchiveService class.
 *
 * @coversDefaultClass \Drupal\csv_data_download\ArchiveService
 * @group csv_data_download
 *
 * @package Drupal\Tests\csv_data_download\Unit
 */
class ArchiveServiceTest extends UnitTestCase {

  /**
   * Tested class object.
   *
   * @var Drupal\csv_data_download\ArchiveService
   */
  protected $archiveService;

  /**
   * FileSystem prophecy to mock.
   *
   * @var object
   */
  protected $fileSystemProphecy;

  /**
   * Gets an accessible method using reflection.
   */
  public function getAccessibleMethod($class_name, $method_name) {
    $class = new \ReflectionClass($class_name);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * Mocks ArchiveService.
   *
   * @param array $stub_methods
   *   ArchiveService methods to stub.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   Mock object.
   */
  public function getArchiveServiceMock(array $stub_methods) {
    return $this->getMockBuilder(ArchiveService::class)
      ->setConstructorArgs([$this->fileSystemProphecy->reveal()])
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

    // Mock FileSystem DI.
    $file_system_prophecy = $this->prophesize(FileSystem::class);
    $file_system_prophecy->realpath('php://pii_data/filename.csv')->willReturn('/tmp/pii_data/filename.csv');
    $file_system_prophecy->realpath('php://pii_data/filename.zip')->willReturn('/tmp/pii_data/filename.zip');

    $file_system_prophecy->basename('temporary://pii_data/webform_csv_data-1542374348.zip')->willReturn('webform_csv_data-1542374348.zip');
    $file_system_prophecy->basename('php://pii_data/filename.zip')->willReturn('filename.zip');

    $this->fileSystemProphecy = $file_system_prophecy;
    $this->archiveService = new ArchiveService($file_system_prophecy->reveal());
  }

  /**
   * Tests ArchiveService::getPassword().
   *
   * @see ArchiveService::getPassword()
   */
  public function testGeneratePassword() {
    $password = $this->archiveService->generatePassword();
    $this->assertInternalType('string', $password);

    // Generate another password and compare with previous one
    // to be sure they are random.
    $this->assertNotEquals($password, $this->archiveService->generatePassword());
  }

  /**
   * Tests ArchiveService::generateZipCommand().
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   * @param string $password
   *   Password to open zip archive.
   * @param string $expected
   *   Expected command coming from the data provider.
   *
   * @throws \ReflectionException
   *
   * @dataProvider generateZipCommandDataProvider
   *
   * @see ArchiveService::generateZipCommand()
   */
  public function testGenerateZipCommand($scheme, $filename, $password, $expected) {
    $archive_service_mock = $this->getArchiveServiceMock(['generatePassword']);

    // ArchiveService::generateZipCommand is a protected method.
    // So we use ReflectionClass.
    $class = new \ReflectionClass($archive_service_mock);

    // Make ArchiveService::generateZipCommand accessible.
    $method = $class->getMethod('generateZipCommand');
    $method->setAccessible(TRUE);

    $command = $method->invokeArgs($archive_service_mock, [
      $scheme,
      $filename,
      $password,
    ]);
    $this->assertEquals($expected, $command);
  }

  /**
   * Data provider for testGenerateZipCommand().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - scheme
   *   - filename
   *   - password
   *   - expected command
   *
   * @see ArchiveServiceTest::testGenerateZipCommand()
   */
  public function generateZipCommandDataProvider() {
    return [
      [
        'php://pii_data/',
        'filename', '123',
        'zip -jP "123" /tmp/pii_data/filename.zip /tmp/pii_data/filename.csv',
      ],
      ['php://pii_data/',
        'filename',
        FALSE,
        'zip -j /tmp/pii_data/filename.zip /tmp/pii_data/filename.csv',
      ],
    ];
  }

  /**
   * Tests ArchiveService::generateZipArchive()
   *
   * Ensures an exception is being thrown if command doesn't exist.
   *
   * Handles error codes: 127, 12, 15
   *
   * @param string $command
   *   Command coming from a data provider.
   *
   * @throws \ReflectionException
   *
   * @expectedException \Exception
   *
   * @dataProvider generateZipArchiveDataProviderException()
   *
   * @see ArchiveService::generateZipArchive()
   */
  public function testGenerateZipArchive($command) {
    $class = new \ReflectionClass($this->archiveService);

    // Make ArchiveService::generateZipArchive accessible.
    $method = $class->getMethod('generateZipArchive');
    $method->setAccessible(TRUE);

    $method->invokeArgs($this->archiveService, [$command]);
  }

  /**
   * Data provider for testGenerateZipArchive().
   *
   * Provides the data causing Exception.
   *
   * @return array
   *   Nested arrays of values to check:
   *   - command
   *
   * @see ArchiveServiceTest::testGenerateZipArchive()
   */
  public function generateZipArchiveDataProviderException() {
    return [
      ['command_which_doesnt_exist'],
      ['zip -j /tmp/pii_data/filename.zip /tmp/pii_data/filename.csv'],
      ['zip -j /usr/filename.zip /dev/null'],
      ['zip -j /usr/filename.zip /etc/hosts'],
    ];
  }

  /**
   * Tests ArchiveService::createZipArchive().
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   * @param string $expected
   *   Zip path coming from data provider.
   *
   * @dataProvider createZipArchiveDataProvider
   *
   * @see ArchiveService::createZipArchive()
   */
  public function testCreateZipArchive($scheme, $filename, $expected) {
    $archive_service_mock = $this->getArchiveServiceMock(
      ['generateZipCommand', 'generateZipArchive']
    );

    // We want to test these methods get called.
    $archive_service_mock->expects($this->once())
      ->method('generateZipCommand');
    $archive_service_mock->expects($this->once())
      ->method('generateZipArchive');

    $zip_path = $archive_service_mock->createZipArchive($scheme, $filename);
    $this->assertEquals($expected, $zip_path);
  }

  /**
   * Data provider for testCreateZipArchive().
   *
   * Data provider for testGetFileDestination().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - scheme
   *   - filename
   *   - expected path
   *
   * @see ArchiveServiceTest::testCreateZipArchive()
   * @see ArchiveServiceTest::testGetFileDestination()
   */
  public function createZipArchiveDataProvider() {
    return [
      ['php://pii_data/', 'filename', 'php://pii_data/filename.zip'],
      ['temp://', 'filename', 'temp://filename.zip'],
    ];
  }

  /**
   * Tests ArchiveService::getFileDestination()
   *
   * @param string $scheme
   *   File scheme.
   * @param string $filename
   *   Filename without an extension.
   * @param string $expected
   *   Zip path coming from data provider.
   *
   * @dataProvider createZipArchiveDataProvider
   *
   * @see ArchiveService::getFileDestination()
   */
  public function testGetFileDestination($scheme, $filename, $expected) {
    $file_destination = $this->archiveService->getFileDestination($scheme, $filename);
    $this->assertEquals($expected, $file_destination);
  }

  /**
   * ArchiveService::returnFileDownload().
   *
   * Throws an exception as a file doesn't exist.
   *
   * @expectedException \Exception
   *
   * @see ArchiveService::returnFileDownload()
   */
  public function testReturnFileDownloadFileNotFound() {
    $response = $this->archiveService->returnFileDownload('php://pii_data/filename.zip');
  }

}
