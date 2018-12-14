/**
 * @file
 * Zip file downloading trigger.
 */
(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.zipDownloadHelper = {
    attach: function attach(context) {

      // Show the file download window only on full document being loaded
      if (context === document) {
        window.location.replace('/zip_download/' + drupalSettings.csvDataDownload.downloadZip);
      }

    }
  };

})(jQuery, Drupal, drupalSettings);
