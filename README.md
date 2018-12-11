# csv_data_download
Drupal 8 module to download and process Webform data.

The module pulls Webform sumbission data out of the database, processes and formats it, puts together into a csv file, archives it and gives it back to the user.
 
Despite the module is based on a real task, it is more about a demonstration of Drupal 8 API relevant for the task. Thus, the implementation is a bit of an overkill for such a purpose.

Functionality the module includes:
- a form implementing batch API to download the data, create and return the file;
- configuration form along with the default configuration;
- controller handling file downloading;
- js library to initiate file downloading after batch has been finished;
- an event firing on a file downloading;
- an event subscriber implementation;
- 2 services to facilitate file creation and downloading covered by unit tests. Resulting zip-archive might be password-protected if it is set up in the configuration;
- routes, menu links and permissions;
