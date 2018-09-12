<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;

use Wisnet\BehatPom\BehatScenario;
use Wisnet\BehatPom\BehatStep;

class BehatScenario {
    public $steps = [];
    
    public function __construct(BeforeScenarioScope $scope) {
        $scenario = $scope->getScenario();
        $this->title = $scenario->getTitle();
    }
    public function addStep(BehatStep $step){
        array_push($this->steps, $step);
    }
}