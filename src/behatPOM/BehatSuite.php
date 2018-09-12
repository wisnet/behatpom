<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

class BehatSuite {
    public $features = [];
    public function __construct(BeforeSuiteScope $scope) {
        $this->name = $scope->getSuite()->getName();
    }
    public function addFeature(BehatFeature $feature){
        array_push($this->features, $feature);
    }    
}