<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
use Wisnet\BehatPom\BehatStep;

class BehatData {
    public $suites = [];
    public function addSuite(BehatSuite $suite){
        array_push($this->suites, $suite);
    }
}