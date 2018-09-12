<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;

class BehatFeature {
    public $scenarios = [];
    public $backgroundSteps = [];
    public $hasBackgroundSteps =false;
    
    public function __construct(BeforeFeatureScope $scope) {
        $feature = $scope->getFeature();
        $this->description = $feature->getDescription();
        $this->title = $feature->getTitle();

        $this->hasBackgroundSteps = $feature->hasBackground();

        if ($this->hasBackgroundSteps) {
            $background = $feature->getBackground();
            $steps = $background->getSteps();

            foreach($steps as $step) {
                $basicStep = new BehatStep();
                $basicStep->setValues($step);
                array_push($this->backgroundSteps, $basicStep);
            }
        }
    }
    public function addScenario(BehatScenario $scenario){
        array_push($this->scenarios, $scenario);
    }
    public function hasBackgroundStep($line) {
        if ($this->hasBackgroundSteps) {
            foreach($this->backgroundSteps as $back) {
                if ($line === $back->line) {
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }
}