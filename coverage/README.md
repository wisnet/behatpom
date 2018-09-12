# Code Coverage

# For background info see [http://tarunlalwani.com/post/php-code-coverage-web-selenium/](http://tarunlalwani.com/post/php-code-coverage-web-selenium/)

## setup
-  create `/codeCoverage` directory
-  change owner to your user e.g. `chown bartonhammond codeCoverage`
-  copy the two files in this directory to `/codeCoverage`
   -  `setupCoverage.php`
   -  `start_xdebug.php`
-  copy `features/coverage/report.php` (you may have to create directory structure)

In MAMP PRO application, under Languages, select PHP, then with the Default Version, select the version desired, then select "Make this version available on command line".  Then select the "Arrow" to the right of the "Default Version".  This will bring up the "phpX.X.X.ini" file.  Add the `auto_prepend_file` here and save by clicking "done"

## php.ini
-  update `/Applications/MAMP PRO/MAMP PRO.app/Contents/Resources/phpxxxxx.ini`

see   /Library/Application Support/appsolute/MAMP PRO/conf
```
 ; Automatically add files before or after any PHP document.
auto_prepend_file = /codeCoverage/start_xdebug.php
```

## MAMP PRO
-  under `languages` select `PHP`
-  click `extensions` for `Xdebug`

## Composer
-  copy parts of `composer.json` from this directory to new project

## to run coverage
-  `composer coverage`

## to see report
-  `composer coverage-report`

