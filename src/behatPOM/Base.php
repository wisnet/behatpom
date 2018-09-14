<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Wisnet\BehatPom;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page;
use Wisnet\BehatPom\Utility;

class Base extends Page {
    protected $path = "";
    protected $elements = [];
    protected $extend;
    /*
     * Get the value from the field
     */
    public function getValueFromField($name) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'getValueFromField')) {
            return $this->extend->getValueFromField($this, $name);
        } else {
            $element = $this->getElement($name);
            return $element->getValue();
        }
    }	
    /*
     * Fill the field of $name with $value
     */
    public function fill($name, $value) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'fill')) {
            $this->extend->fill($this, $name, $value);
        } else {
            $element = $this->getElement($name);
            $element->setValue($value);       
        }
    }	
    /*
     * Click the button with $name
     */
    public function clickButton($name) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'clickButton')) {
            $this->extend->clickButton($this, $name);
        } else {
            $element = $this->getElement($name);
            $element->click();       
        }   

    }
    /*
     * Check or uncheck the checkbox
     */
    public function setCheckbox($name, $value) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'setCheckbox')) {
            $this->extend->setCheckbox($this, $name, $value);
        } else {
            $element = $this->getElement($name);
            if ($value) {
                $element->check();
            } else {
                $element->uncheck();
            }
        }   

    }    
    /*
     * Set the Option for the Select
     */
    public function selectOption($name, $value) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'selectOption')) {
            $this->extend->selectOption($this, $name, $value);
        } else {
            $element = $this->getElement($name);
            $element->selectOption($value);			   
        }   
    }	    
    public function linkExistsWithText($text) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'linkExistsWithText')) {
            return $this->extend->linkExistsWithText($this, $text);
        } else {
            return $this->hasElement($text);       
        }   

    }
    /*
     * Define XPath dynamically
     */
    public function setupXPath($linkname) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'initXPath')) {
            $this->extend->initXPath($this, $linkname);
        }   
    }
    public function clickLink($linkname) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'clickLink')) {
            $this->extend->clickLink($this, $linkname);
        }  else {
            $link = $this->getElement($linkname);
            $link->click();
        }   
    }
    public function isMessageDisplayed($arg) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'isMessageDisplayed')) {
            return $this->extend->isMessageDisplayed($this, $arg);
        }  else {
            return (strpos($this->getContent(),$arg) !== false);	       
        }   
    }
    /*
     * Upload file
     */
    public function attachFileToTheField($field, $file) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'attachFileToTheField')) {
            return $this->extend->attachFileToTheField($this, $field, $file);
        }  else {
            $element = $this->getElement($field);
            return $element->attachFile($file);
        }   
    }
    /*
     *  Is the browser at the expect url?
     */
    public function verifyPage() {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'verifyPage')) {
            return $this->extend->verifyPage($this);
        }  else {
            $baseUrl = $this->getParameter('base_url');
            if (substr($baseUrl, -1) === '/') {
                $baseUrl = substr($baseUrl, 0, -1);
            }

            $currentUrl = $this->getDriver()->getCurrentUrl();
            if (substr($currentUrl, -1) === '/') {
                $currentUrl = substr($currentUrl, 0, -1);
            }        
            $utility = new Utility();
        
            return $utility->isCurrentUrlEqualToExpectedUrl($currentUrl,
                                                            $baseUrl . $this->path);
        }
    }
      /*
     * Scroll field into view
     */
    public function scrollIntoView($name) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'scrollIntoView')) {
            return $this->extend->scrollIntoView($this, $name);
        }  else {
            $element = $this->elements[$name];
            $script = '(document.evaluate("'
                    . $element["xpath"]
                    . '", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue).scrollIntoView(true);';
            return $this->getDriver()->executeScript($script);
        }

    }
    
    /*
     * Provide element that should not be found 
     */
    public function waitForTheSpinner($name) {
        global $myParent;
        $myParent = $this;

        global $myName;
        $myName = $name;
        
        return $myParent->waitFor(100000, function() {
            global $myParent;
            global $myName;
            try {
                $xpath = $myParent->elements[$myName];
                                   
                $element = $myParent->find('xpath', $xpath['xpath']);
                if ($element) {
                    return false;
                }
                return true;
            } catch(Exception $e) {
                return true;//not found
            }
        });        
    }
    /*
     * Provide element that should be found 
     * Scroll the view until the element is visible
     */
    public function waitForElementToBeVisible($name) {
        //Alredy visible?
        $element = $this->getElement($name);
        if ($element->isVisible()) {
            return;
        }

        global $myParent;
        $myParent = $this;

        global $myName;
        $myName = $name;

        
        return $myParent->waitFor(100000, function() {
            global $myParent;
            global $myName;
            try {

                $driver = $this->getDriver();

                //the total size of page
                $scrollHeight = $driver->evaluateScript('document.body.scrollHeight');
        
                //the viewport
                $innerHeight = $driver->evaluateScript('window.innerHeight');        

                //initialize
                $startPos = 0;

                while (true) {
                    //Start at top

                    $driver->evaluateScript('window.scrollTo(0, ' . $startPos . ')');

                    $xpath = $myParent->elements[$myName];
                                   
                    $element = $myParent->find('xpath', $xpath['xpath']);

                    if (isset($element)
                        &&
                        $element->isVisible()) {
                        return true;
                    }

                    //are we at the bottom
                    $newPosition = $driver->evaluateScript('window.innerHeight + window.scrollY');
                    if ($newPosition >= $scrollHeight) {
                        return false;
                    }
                    
                    $startPos += $innerHeight;            
                }//while
                
                return false;
            } catch(Exception $e) {
                return false;//not found
            }
        });        
    }    
    /*
     * Verify some activity - this has to be defined in the Extend class
     */
    public function verifyTheActivity($name, $sensory, $gender, $wayin, $music, $debug) {
        try {
            return $this->extend->verifyTheActivity($this, $name, $sensory, $gender, $wayin, $music, $debug);
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /*
     * Return true if field exists on page
     */
    public function elementExists($name) {
        if (isset ($this->extend)
            &&
            method_exists($this->extend, 'elementExists')) {
            $this->extend->elementExits($this, $name);
        } else {
            return $this->hasElement($name);
        }
    }	    
    /*
     * Provide access to driver
     */
    public function _getDriver() {
        return $this->getDriver();
    }
    /*
     * Support the Extend class to add to Elements
     */
    public function _addToElements($key, $value) {
        $this->elements[$key] = $value;
    }
    /*
     * public access to elements
     */
    public function _elements($name) {
        return $this->elements[$name];
    }
}
