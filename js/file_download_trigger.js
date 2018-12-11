/**
 * @file
 * Zip file downloading trigger.
 */
(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.zipDownloadHelper = {
    attach: function attach(context) {
      $('#download-link-container', context).once('zipDownloadHelper').each(function () {
        var downloadLink=document.createElement('a');
        $(downloadLink).attr('href', '/zip_download/' + drupalSettings.csvDataDownload.downloadZip).hide();
        $(this).append(downloadLink);
        downloadLink.click();
        $(downloadLink).remove();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
