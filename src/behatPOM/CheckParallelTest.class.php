<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
/** 
 * 
 * Check that neither ScreenShots or Automation tests are running
 * 
 */
class CheckParallelTest {

    protected $username = '';
    protected $authkey = '';
    protected $automation = "https://crossbrowsertesting.com/api/v3/selenium?format=json&num=1&active=true";
    protected $screenshots = "https://crossbrowsertesting.com/api/v3/screenshots/?format=json&num=10&active=true";
    protected $curlSupport;
    protected $ch;
    
    public function __construct($username, $authkey) {
        $this->username = $username;
        $this->authkey = $authkey;
        $this->curlSupport = new CurlSupport($username, $authkey);
    }

    public function waitWhileTestsAreRunning () {

        while (TRUE) {
            $autoResponse = $this->curlSupport->callApi($this->automation);
            $screenResponse = $this->curlSupport->callApi($this->screenshots);            
            if ($autoResponse->meta->record_count === 0
                &&
                $screenResponse->meta->record_count === 0) {
                return;
            } else {
            } sleep(2);
            
        }
    }
}
