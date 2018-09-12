<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
class Utility {
    /*
     *  Compare urls and assume a placeholder has form {x}
     */
    function isCurrentUrlEqualToExpectedUrl($currentUrl,
                                            $expectedUrl) {
        return $this->arePartsEqual(parse_url($currentUrl),
                                    parse_url($expectedUrl));
    }        
    function arePartsEqual($curParts, $expParts) {
        foreach($curParts as $key => $value) {
    
            if ($curParts[$key] === $expParts[$key]){
                //ok
            } else {
                $eq = $this->arePiecesEqual($curParts[$key], $expParts[$key]);
                if (!$eq) {
                    return false;
                }
            }
        }
        return true;
    }
    function arePiecesEqual($cur, $exp) {
        $curPieces = explode('/', $cur);
        $expPieces = explode('/', $exp);

        foreach($curPieces as $key => $value) {
            if ($curPieces[$key] !== $expPieces[$key]) {
                $pos = strpos($expPieces[$key], '{');
                if ($pos === false) {
                    return false;  //this part is not a placeholder
                }
            }
        }
        return true;

    }
    /*
     * check if string $haystack  ends w/ $needle
     */
    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        
        return $length === 0 || 
                       (substr($haystack, -$length) === $needle);
    }
    /*
     * Returns true if needle is contained w/in $haystack otherwise false
     */
    function contains($haystack, $needle) {
        if (strpos($haystack, $needle) !== false) {
            return true;
        }
        return false;
    }
}