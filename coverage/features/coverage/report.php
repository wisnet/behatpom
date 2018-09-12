<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
require_once __DIR__ . "/../../vendor/autoload.php";

$coverages = glob("/codeCoverage/results/*.json");

#increase the memory in multiples of 128M in case of memory error
ini_set('memory_limit', '12800M');

$final_coverage = new SebastianBergmann\CodeCoverage\CodeCoverage;
$count = count($coverages);
$i = 0;

$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/controllers");
$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/modelRepositories");
$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/modelViews");
$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/models");
$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/services");
$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/utilities");
$final_coverage->filter()->addDirectoryToWhitelist("/Users/bartonhammond/projects/uwce/www/views");



foreach ($coverages as $coverage_file)
{
    $i++;
    echo "Processing coverage ($i/$count) from $coverage_file". PHP_EOL;
    $codecoverageData = json_decode(file_get_contents($coverage_file), JSON_OBJECT_AS_ARRAY);
    $test_name = str_ireplace(basename($coverage_file,".json"),"coverage-", "");
    $final_coverage->append($codecoverageData, $test_name);
}

echo "Generating final report..." . PHP_EOL;
$report = new \SebastianBergmann\CodeCoverage\Report\Html\Facade;
$report->process($final_coverage,"features/coverage/reports");
echo "Report generated succesfully". PHP_EOL;


?>