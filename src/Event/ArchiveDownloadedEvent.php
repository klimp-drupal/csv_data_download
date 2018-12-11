<?php

namespace Drupal\csv_data_download\Event;

use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user logs in.
 */
class ArchiveDownloadedEvent extends Event {

  const EVENT_NAME = 'csv_data_download_archive_downloaded_event';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Password for zip archive.
   *
   * @var string
   */
  public $password;

  /**
   * ArchiveDownloadedEvent constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   Current user.
   * @param string $password
   *   Zip password.
   */
  public function __construct(AccountProxy $account, $password) {
    $this->account = $account;
    $this->password = $password;
  }

}
