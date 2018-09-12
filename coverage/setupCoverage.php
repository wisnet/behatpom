<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
$dir = '/codeCoverage';
$results = "$dir/results";
         
if ($argv[1] === 'setup') {
    touch($dir . '/startCoverage');
    mkdir($dir . '/results');
}
if ($argv[1] === 'teardown') {
    if (file_exists($dir . '/startCoverage')) {
        unlink($dir . '/startCoverage');
        $files = array_diff(scandir($results), array('.','..')); 
        foreach ($files as $file) { 
            unlink("$results/$file"); 
        } 
        rmdir($results);
    }
}

?>
 