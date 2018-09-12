<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
if (file_exists('/codeCoverage/startCoverage')) {
    $current_dir = __DIR__;
    $test_name = (isset($_COOKIE['test_name']) && !empty($_COOKIE['test_name'])) ? $_COOKIE['test_name'] : 'unknown_test_' . time();
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

    function end_coverage()    {
        global $test_name;
        global $current_dir;
        $coverageName = '/codeCoverage/results/' . $test_name . '-' . microtime(true);

        try {
            xdebug_stop_code_coverage(false);
            $codecoverageData = json_encode(xdebug_get_code_coverage());
    
            file_put_contents($coverageName . '.json', $codecoverageData);
        } catch (Exception $ex) {
            file_put_contents($coverageName . '.ex', $ex);
        }
    }

        class coverage_dumper
        {
            function __destruct()
            {
                try {
                    end_coverage();
                } catch (Exception $ex) {
                    echo str($ex);
                }
            }
        }

    $_coverage_dumper = new coverage_dumper();
}