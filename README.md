# LiveConfig® Application Installer

This is an example how to create own installer scripts for the LiveConfig® Application Installer.

You need LiveConfig v1.9.0-r3583 or later.

1. create/adjust installer script

   First, grab an existing installer script (eg. the "wai-test-...php" script in this example) and adjust it to your own needs.
   At least you have to adjust the download URLs of the packages to install, and their SHA1 checksum.

2. create SHA1 checksum of your installer script

   eg. `sha1sum wai-test-4.2.2-1.php`

3. create compressed version of installer script

   `gzip -9c wai-test-4.2.2-1.php >wai-test-4.2.2-1.php.gz`

   LiveConfig expects all installer scripts to be compressed (and thus saved with .gz extension on the web server).

3. create/update repository file (JSON)

   Edit the repository file (in this example `repo.json`). At least, you have to adjust the file name of the installer script and its SHA1 checksum (as created in the previous step).

4. upload

   Upload the repository file, the icon and the *compressed* installer script (.gz) to any web server. All files must reside within the same directory.

5. add repository to LiveConfig

   To add your new repository to LiveConfig, issue the following SQL command with the LiveConfig database (either the default SQLite3 database at `/var/lib/liveconfig/liveconfig.db` or - if configured - a MySQL database).

   ```
   INSERT INTO APPREPOSOURCE (AS_URL) VALUES ("http://your.reposity.server/path/to/repo.json");
   ```
 
6. restart LiveConfig

   The new repository will automatically be loaded about 10 seconds after starting LiveConfig and then each 24 hours.


## updating an application

1. rename the installer script (we recommend to use a package name, package version and installer version, eg. for WordPress this would be like "wordpress-4.2.2-1.php")

2. update your installer script, calculate new SHA1 checksum, create .gz version of installer script

3. update `repo.json` (filename, SHA1 checksum). Additionally, you must increase the number in the `rev` (revision) field for each updated record (LiveConfig only updates database records with "older" revision numbers!)


## Troubleshooting

If something doesn't work as expected:

1. check the LiveConfig log file at `/var/log/liveconfig/liveconfig.log`.

2. check if your app icon was successfully downloaded - you should find it at `/var/lib/liveconfig/htdocs/`.

3. check if the installer script was successfully downloaded - you should find it at `/var/cache/liveconfig/installer/`.

4. check if the package to be installed was successfully downloaded - you should find it at `/var/cache/liveconfig/downloads/`.

5. check if any error occured during installation - have a look at `/var/www/<subscription>/logs/appinstall.log`.

