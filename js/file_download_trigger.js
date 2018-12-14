/**
 * @file
 * Zip file downloading trigger.
 */
(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.zipDownloadHelper = {
    attach: function attach(context) {

      // Show the file download window only on full document being loaded
      if (context === document) {

        var request = new XMLHttpRequest();
        request.open('POST', '/zip_download/' + drupalSettings.csvDataDownload.downloadZip, true);
        request.responseType = 'blob';

        request.onload = function() {
          if(request.status === 200) {

            // Try to find out the filename from the content disposition `filename` value
            var disposition = request.getResponseHeader('content-disposition');
            var matches = /"([^"]*)"/.exec(disposition);
            var filename = (matches != null && matches[1] ? matches[1] : drupalSettings.csvDataDownload.downloadZip + '.zip');

            // The actual download
            var blob = new Blob([request.response], { type: request.getResponseHeader('Content-Type') });

            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          }

        };
        request.send();

      }

    }
  };

})(jQuery, Drupal, drupalSettings);
