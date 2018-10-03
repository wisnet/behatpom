---
id: reset
title: How to reset date
sidebar_label: Reset data
---

When starting a Feature, be sure to reset your data first.

## Thoughts on Test preparation

Tests should all start from some known condition.  We make it a practice that any data the Tests create should be removed too.  We identify the records that are being added and then use a script to remove all the data.  We reset that data at the *beginning* of the test.  That way, we start the test with the same, constant position.

## Use the "Given I reset `token` data"

When using the reset data option as shown below:

```gherkin
Feature: home
  In order to navigate the site
  As a visitor
  I want to test navigation

  Background:
    Given I reset "all" data
    Given I am a visitor
    When I go to the "Home" page
```

you can provide a keyword for the `token`.  The example above passed `all` as the value.


## Implement the function in the `ProjectContext`

Look at `features/bootstrap/ProjectContext.php` to add the implementation of the `reset` as this is project specific.  

Here's an example implentation for processing `user` and `all` resets.  This particular project was a **WordPress** project and we exposed a API for handling the request.  Note that if any other argument then `user` or `all` is passed in, that an **Exception** is thrown. Also note that we are using the `getMinkParameters` to get the `base_url` so that our `reset` will work w/ the correct website (e.g. 'dev', 'staging', 'local')g

```php
    /**
     * @Given I reset :arg1 data
     */
    public function iResetData($arg1)  {
        if ($arg1 == 'user'
            ||
            $arg1 == 'all') {

            $params = $this->getMinkParameters();
            $url = $params['base_url'] . "/wp-content/themes/jumpoff/data-reset/reset/$arg1";

            try {
                $curl = curl_init();            
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_SSL_VERIFYPEER => false,                    
                    CURLOPT_URL => $url,
                    CURLOPT_USERAGENT => 'cURL Request'
                ));
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                echo $resp;
                // Close request to clear up some resources
                curl_close($curl);
            } catch (Exception $e) {
                eval(\Psy\sh());
                throw $e;
            }
        } else {
            throw new Exception("invalid arg for iResetData: $arg1");
        }
    }
```

