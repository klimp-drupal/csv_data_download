<?php

namespace Drupal\csv_data_download\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\csv_data_download\Event\ArchiveDownloadedEvent;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ArchiveDownloadedEventSubscriber.
 */
class ArchiveDownloadedEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * ArchiveDownloadedEventSubscriber constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    ConfigFactory $config_factory,
    LoggerChannelFactory $logger_factory
  ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ArchiveDownloadedEvent::EVENT_NAME => 'onArchiveDownloading',
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param \Drupal\csv_data_download\Event\ArchiveDownloadedEvent $event
   *   Event object.
   */
  public function onArchiveDownloading(ArchiveDownloadedEvent $event) {
    $this->mailManager->mail(
      'csv_data_download',
      'csv_data_download_key',
      $event->account->getEmail(),
      $this->languageManager->getCurrentLanguage()->getId(),
      ['message' => $this->t('Your decryption key is: @key', ['@key' => $event->password])],
      $this->configFactory->get('system.site')->get('mail'),
      TRUE
    );

    // Log the downloading.
    $this->loggerFactory->get('csv_data_download')->notice('@username has downloaded CSV user data', [
      '@username' => $event->account->getAccountName(),
    ]);
  }

}
