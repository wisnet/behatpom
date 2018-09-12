<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
use  Behat\Behat\Hook\Scope\BeforeStepScope;

class BehatStep {
    public function __construct(BehatFeature $currentFeature = null ,
                                BeforeStepScope $scope = null) {
        $this->isBackgroundStep = false;
        if (isset($scope)
            &&
            isset($currentFeature)) {
            $step = $scope->getStep();

            $this->isBackgroundStep = $currentFeature->hasBackgroundStep($step->getLine());
            $this->setValues($step);
        }
    }
    public function setValues($step ){
        $this->text = $step->getText();
        $this->type = $step->getType();
        $this->line = $step->getLine();//id
        $this->comment = false;

        //Screenshot?
        $result = preg_match("#^I take a snapshot called (.*)$#i", $this->text);
        if ($result !== 0) {
            $result = preg_match("/'([^']*)'/", $this->text, $matches);
            if ($result !== 0) {
                $this->img = urlencode(str_replace('"',"",$matches[1]));
            }
        }

        //Comment
        $result = preg_match("#^comment(.*)$#i", $this->text);
        if ($result !== 0) {
            $this->comment = true;
            preg_match('/".*?"/', $this->text, $matches);
            $this->text = str_replace('"',"",$matches[0]);
        }
    }


}