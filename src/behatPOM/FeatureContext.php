<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../phpunit/phpunit/src/Framework/Assert/Functions.php';

use LightnCandy\LightnCandy;

use WebDriver\Exception\UnknownError;

use Wisnet\BehatPom\BehatData;
use Wisnet\BehatPom\BehatSuite;
use Wisnet\BehatPom\BehatFeature;
use Wisnet\BehatPom\BehatScenario;
use Wisnet\BehatPom\BehatStep;

use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;

use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\AfterFeatureScope;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Behat\Hook\Scope\AfterStepScope;

use Behat\Behat\Context\Context;

use SensioLabs\Behat\PageObjectExtension\Context\PageObjectContext;

use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;

use Wisnet\BehatPom\Utility;

//Table of content
class Toc {
    public $items = [];
}
class TocItem {
    public $name;
}
class FeatureContext extends PageObjectContext implements MinkAwareContext {

    //first suite during teardown
    private static $first = true;

    private static $device;
    private static $directory;
    
    public static $data;
    public static $currentSuite;
    public static $currentFeature;
    public static $currentScenario;
    public static $currentStep;
    
    public static $output;
    public static $mink;
    
    protected static $seleniumTestId;
    protected static $runningOnCBT = false;

    protected $minkParameters;
    protected $scenario;
    
    protected $current;

    protected $firstPage = true;

    public function __construct($buildDir = null,
                                $osApiName = null,
                                $browserApiName = null
    ) {
        //When running against CBT, the yml is created w/ parameters for the Context
        //The parameters include a directory to contain the generated report
        //But when running locally, we have to create that directory ourselves
        if (!isset($buildDir)) {
            //Each time we run, save results under the BUILD date/time
            date_default_timezone_set('America/Chicago');
            $buildDir = date('Y-m-d-H-i');

            //Keep all the results under '/output' so that
            //.gitsignore works
            $directory = getcwd() . '/features/results/' . $buildDir;

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            if (!file_exists($directory . '/screenshots')) {
                mkdir($directory . '/screenshots', 0777, true);
            }
            self::$directory = $directory;
            self::$device = 'chrome';
        } else {
            self::$directory = getcwd() . "/features/results/$buildDir";
            self::$device = $osApiName . $browserApiName;
            
            self::$device = str_replace('-', '_', self::$device);
            self::$device = str_replace(' ', '_', self::$device);
            self::$device = str_replace('.', '_', self::$device);
        }

    }
    public function scenarioHasTag($tagName) {
        if (null === $this->scenario) {
            throw new \RuntimeException(
                "$scenario is missing"
            );
        }
        return $this->scenario->hasTag($tagName);
    }
    /**
     * If running on CBT, get the sessionID
     */
    public function checkIfRunningOnCbt()    {
        $session = self::$mink->getSession('selenium2');
        $driver = $session->getDriver();
        $webDriverSession = $driver->getWebDriverSession();
        $url = $webDriverSession->getURL();
        $utility = new Utility();
        return $utility->contains($url, 'crossbrowsertesting');
    }
    /**
     * page full capture for https://github.com/facebook/php-webdriver
     * @When I take a snapshot called :arg1
     * takeFullScreenshot($driver, $screenshot_name)
     */
    public function iTakeASnapshotCalled($screenshot_name)  {
        $driver = $this->getMink()->getSession()->getDriver();
        
        //current position
        $initialPos =  $driver->evaluateScript('window.pageYOffset');

        $total_height = $driver->evaluateScript('return Math.max.apply(null, [document.body.clientHeight, document.body.scrollHeight, document.documentElement.scrollHeight, document.documentElement.clientHeight])');

        $viewport_width = $driver->evaluateScript('return document.documentElement.clientWidth');
        $viewport_height = $driver->evaluateScript('return document.documentElement.clientHeight');

        $driver->evaluateScript('window.scrollTo(0, 0)');
        $full_capture = imagecreatetruecolor($viewport_width, $total_height);
        
        $repeat_y = ceil($total_height / $viewport_height);

        $file_name = self::$directory . "/screenshots/" . self::$device  . "$screenshot_name.png";
        $tmp_name = self::$directory . "/screenshots/" . self::$device  . "tmp$screenshot_name.png";

        $x_pos = 0;

        $session = self::$mink->getSession('selenium2');
        $driver = $session->getDriver();
        $webDriverSession = $driver->getWebDriverSession();
        $capabilities = $webDriverSession->capabilities();
        
        for ($y = 0; $y < $repeat_y; $y++) {
            $y_pos = $y * $viewport_height;
            $driver->evaluateScript("window.scrollTo($x_pos, $y_pos)");
            $scroll_top = $driver->evaluateScript("return window.pageYOffset");


            //Image is complete if safari or internet explorer
            if ($capabilities['browserName'] == 'safari'
                ||
                $capabilities['browserName'] == 'internet explorer') {                
                file_put_contents($file_name,  $driver->getScreenshot());
                break;
            } else {
                file_put_contents($tmp_name,  $driver->getScreenshot());
            }
                
            $command = 'identify ' . $tmp_name;
            $result = shell_exec($command);
            $exploded = explode(' ', $result);
                
            $size = "$viewport_width" . "x" . "$viewport_height";
            $sizeNext = $exploded[2];
                
            
            if ($size != $sizeNext) {
                $command = 'convert ' . $tmp_name . ' -resize ' . $size . '\! ' .  $tmp_name;
                shell_exec($command);
            }

            $tmp_image = imagecreatefrompng($tmp_name);
            imagecopy($full_capture,       //destination
                      $tmp_image,          //source
                      0,                   //destination x
                      $scroll_top,         //destination y
                      0,                   //source x
                      0,                   //source y
                      $viewport_width,     //source width
                      $viewport_height);   //source height
            


            imagepng($full_capture, $file_name);
                
            imagedestroy($tmp_image);
            
        }
        
        if ($capabilities['browserName'] != 'safari'
            &&
            $capabilities['browserName'] != 'internet explorer') {
            imagepng($full_capture, $file_name);
            imagedestroy($full_capture);
        }

        //Go back to top so that if scrolling down, the drawer disappears
        $driver->evaluateScript('window.scrollTo(0, 0)');
        
        //needs time to setup
        sleep(1);
        
        //put back to initial position
        $driver->evaluateScript("window.scrollTo(0, $initialPos)");

        //need a little time to reset
        sleep(1);        
    }
    /**
     * @When I take a xxxsnapshot called :arg1
     */
    public function iTakeASnapshotCalled___Old($arg1) {
        if (!$this->scenarioHasTag('snapshot')) {
            return;
        }
        $driver = $this->getMink()->getSession()->getDriver();

        //current position
        $initialPos =  $driver->evaluateScript('window.pageYOffset');

        //the total size of page
        $scrollHeight = $driver->evaluateScript('document.body.scrollHeight');

        //the viewport
        $innerHeight = $driver->evaluateScript('window.innerHeight');        

        //initialize
        $startPos = 0;
        $section = 0;

        while (true) {
            //Start at top

            $driver->evaluateScript('window.scrollTo(0, ' . $startPos . ')');
            
            //each section has it's own #
            $file = urlencode($arg1 . "_$section");

            $fullPath = self::$directory . "/screenshots/" . self::$device  . "$file.png";

            file_put_contents($fullPath,  $driver->getScreenshot());
            
            //are we at the bottom
            if (self::$device == 'Win10IE11' //the screensghot is complete
                ||
                (($startPos + $innerHeight) >= $scrollHeight)) {
                break;
            }

            $startPos += $innerHeight;            
            $section += 1;
        }//while
        //Go back to top so that if scrolling down, the drawer disappears
        $driver->evaluateScript('window.scrollTo(0, 0)');
        
        //needs time to setup
        sleep(1);
        
        //put back to initial position
        $driver->evaluateScript("window.scrollTo(0, $initialPos)");

        //need a little time to reset
        sleep(1);
    }
    /**
     * @BeforeScenario @sizeMobile
     */
    public function resizeWindow()
    {
        self::$mink->getSession('selenium2')->resizeWindow(375, 667);
    }
    /**
     * Sets Mink instance.
     *
     * @param Mink $mink Mink session manager
     */    
    public function setMink(Mink $mink)
    {
        self::$mink = $mink;        
    }
    /**
     * Returns Mink instance.
     *
     * @return Mink
     */
    public function getMink()
    {
        if (null === self::$mink) {
            throw new \RuntimeException(
                'Mink instance has not been set on Mink context class. ' . 
                'Have you enabled the Mink Extension?'
            );
        }
        return self::$mink;
    }
    /**
     * Sets parameters provided for Mink.
     *
     * @param array $parameters
     */
    public function setMinkParameters(array $parameters)
    {
        $this->minkParameters = $parameters;
    }
    /**
     * Return minkParameters
     */
    public function getMinkParameters() {
        return $this->minkParameters;
    }
    /**
     * @Given I am a visitor
     */
    public function iAmAVisitor()
    {
        return true;
    }
    /**
     * expects format e.g. '+1 month'
     */
    public function getMonthDayYear($format, $period) {
        $date = date($format, strtotime($period));
        return $date;
    }
    /**
     * @Given I reset the :arg1 database
     */
    public function iResetTheDatabase2($arg1)    {
        try {
            $baseUrl = $this->minkParameters['base_url'];
            if (substr($baseUrl, -1) === '/') {
                $baseUrl = substr($baseUrl, 0, -1);
            }
        
            if ($arg1 === 'admin') {
                $arrContextOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false));  
                $url = $baseUrl . "/staging-reset/BWH";
                file_get_contents($url, false, stream_context_create($arrContextOptions));
            } else if ($arg1 === 'discount') {
                $arrContextOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false));  
                $url = $baseUrl . "/staging-reset-discount/BWH";
                file_get_contents($url, false, stream_context_create($arrContextOptions));
            
            } else if ($arg1 === 'student') {
                $arrContextOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false));
                $url = $baseUrl . "/staging-student-reset";
                file_get_contents($url, false, stream_context_create($arrContextOptions));
            } else {
                throw new Exception("invalid arg for resetting database: $arg1");
            }
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @Then I should pause
     */
    public function iShouldPause()
    {
        \Psy\Shell::debug(get_defined_vars(),$this);
    }
    /**
     * @Given I wait :arg1 second
     */
    public function iWaitSecond($arg1)
    {
        sleep($arg1);
    }    
    /**
     * @Then I should go back
     */
    public function iShouldGoBack()
    {
        try {
            $session = $this->getMink()->getSession();
            $session->back();
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }    

    
    /**
     * @When I go to the :arg1 page
     */
    public function iGoToThePage($arg1) {

        try {
            $this->current = $this->getPage($arg1);
            sleep(1);            
            $this->current->open();
            sleep(1);
            
            try {
                //self::$mink->getSession('selenium2')->getDriver()->maximizeWindow();
            } catch(UnknownError $e) {
                //ignore - some drivers don't support
            }
            
            
        } catch (Exception $e) {
            eval(\Psy\sh());
            throw $e;
        }
    }
    /**
     * @Then I should see :arg1 link
     */
    public function iShouldSeeLink($arg1)  {
        assertNotNull($this->current->linkExistsWithText($arg1));
    }
    /**
     * @Given I click the :arg1 link
     */
    public function iClickTheLink($arg1)  {
        try {
            $this->current->setupXPath($arg1);

            //make sure it's visible
            $this->iWaitForTheElementToBeVisible($arg1);
                
            $this->current->clickLink($arg1);
        } catch(UnknownError $unknown1) {
            //could be that the Wordpress navigation is overlapping
            //account for border/menus like w/ WordPress
            try {
                $this->iScrollTheWindowBy("-100");
                $this->current->clickLink($arg1);
            } catch(UnknownError $unknown2) {
                //possibly the bottom wordpress menu is
                //on top
                $this->iScrollTheWindowBy("200");
                $this->current->clickLink($arg1);
            }
        } catch (Exception $e) {
            eval(\Psy\sh());            
            throw $e;
        }
    }

    /**
     * @Then I should see the :arg1 page
     */
    public function iShouldSeeThePage($arg1)  {
        try {
            sleep(2);//let the page load
            $this->current = $this->getPage($arg1);        
            sleep(1);
            //return assertTrue($this->current->verifyPage());
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;            
        }
    }

    /**
     * @When I fill the :arg1 with :arg2
     */
    public function iFillTheWith($arg1, $arg2)  {
        try {
            $this->current->fill($arg1, $arg2);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @Given I get the value from :arg1
     */
    public function iGetTheValueFrom($arg1)
    {
        try {
            return $this->current->getValueFromField($arg1);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @When I click the :arg1 button
     */
    public function iClickTheButton($arg1)  {
        try {
            //dynamic xpath?
            $this->current->setupXPath($arg1);
            //make sure it's visible
            $this->iWaitForTheElementToBeVisible($arg1);

            $this->current->clickButton($arg1);
        } catch(UnknownError $unknown1) {            
            //could be that the Wordpress navigation is overlapping
            //account for border/menus like w/ WordPress
            try {
                $this->iScrollTheWindowBy("-100");
                $this->current->clickButton($arg1);
            } catch(UnknownError $unknown2) {
                //possibly the bottom wordpress menu is
                //on top
                $this->iScrollTheWindowBy("200");
                $this->current->clickButton($arg1);
            }            
        } catch(Exception $e) {
            eval(\Psy\sh());
            throw $e;
        }
    }
    /**
     * @When I select the :arg1 option of :arg2
     */
    public function iSelectTheOptionOf($arg1, $arg2)
    {
        try {
            $this->current->selectOption($arg1, $arg2);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @Then I should see the message :arg1
     */
    public function iShouldSeeTheMessage($arg1)
    {
        sleep(2);
        try {
            assertTrue($this->current->isMessageDisplayed($arg1));
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @When I fill the date :arg1 with format :arg2 for time :arg3
     */
    public function iFillTheDateWithFormatForTime($arg1, $arg2, $arg3)
    {
        try {
            $date = $this->getMonthDayYear($arg2, $arg3);
            $this->iFillTheWith($arg1, $date);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }

    /**
     * @Then I set the checkbox :arg1 to :arg2
     */
    public function iSetTheCheckboxTo($arg1, $arg2)
    {
        try {
            $this->current->setCheckbox($arg1, $arg2);
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }

    }
    /**
     * @Then the :arg1 field should be equal to :arg2
     */
    public function theFieldShouldBeEqualTo($arg1, $arg2)
    {
        try {
            $actual = $this->iGetTheValueFrom($arg1);
            assertEquals($arg2, $actual);
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
        
    }
    /**
     * @Then I attach the file :file to the :field field
     */
    public function iAttachTheFileToTheField($file, $field)
    {
        try {
            $fileFullPath = getcwd() . $file;
            $this->current->attachFileToTheField($field, $fileFullPath);
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @Then I scroll to top
     */
    public function iScrollToTop() 
    {
        try {
            $this->getMink()->getSession()->getDriver()->executeScript('window.scrollTo(0,0);');  
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }
    /**
     * @Then I scroll to bottom
     */
    public function iScrollToBottom() 
    {
        try {
            $this->getMink()->getSession()->getDriver()->executeScript('window.scrollTo(0,document.body.scrollHeight)');
        } catch (Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }    
    /**
     * @When I scroll :selector into view
     *
     *
     * @throws \Exception
     */
    public function scrollIntoView($name) {
        try {
            $this->current->scrollIntoView($name);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }

    }
    /**
     * @Then I scroll the modal :arg1 by :arg2
     */
    public function iScrollTheModalBy($arg1, $arg2)
    {
        try {
            $script = "document.getElementById('" . $arg1 . "').scrollTo(0," . $arg2 . ")";            
            $this->getMink()->getSession()->getDriver()->executeScript($script);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }    
    /**
     * @Then I scroll the window by :selector
     */
    public function iScrollTheWindowBy($adjustment)
    {
        $script = "window.scrollBy(0,$adjustment)";
        $this->getMink()->getSession()->getDriver()->executeScript($script);
    }            
    /**
     * @When I wait for the :arg1 spinner
     *
     *
     * @throws \Exception
     */
    public function iWaitForTheSpinner($name) {
        try {
            $this->current->waitForTheSpinner($name);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }    
    /**
     * @When I wait for the :arg1 element to be visible
     *
     *
     * @throws \Exception
     */
    public function iWaitForTheElementToBeVisible($name) {
        try {
            $this->current->waitForElementToBeVisible($name);
        } catch(Exception $e) {
            \Psy\Shell::debug(get_defined_vars(),$this);
            throw $e;
        }
    }    
        
    /**
     * @Given comment :arg1
     */
    public function comment($arg1)
    {
        echo "----------\n $arg1 \n----------\n";
    }

    /**
     * @Then the :arg1 element exists
     */
    public function theElementExists($arg1)
    {
        assertTrue($this->current->elementExists($arg1));
    }
    /**
     * @Then the :arg1 element does not exist
     */
    public function theElementDoesNotExist($arg1)
    {
        assertTrue(!$this->current->elementExists($arg1));
    }    
    /** @BeforeSuite */
    public static function setup(BeforeSuiteScope $scope)  {        
        self::$data = new BehatData();
        self::$currentSuite = new BehatSuite($scope);
        self::$data->addSuite(self::$currentSuite);
    }
    public static function prepDevice() {
        $template = file_get_contents(getcwd() . '/vendor/wisnet/behatpom/src/templates/device.hbs.html');

        $phpStr = LightnCandy::compile($template, array(
            'flags' => 0
            | LightnCandy::FLAG_BESTPERFORMANCE
            | LightnCandy::FLAG_ERROR_EXCEPTION
            | LightnCandy::FLAG_RUNTIMEPARTIAL
            | LightnCandy::FLAG_HANDLEBARS
        ));
            
        // set compiled PHP code into $phpStr
        $renderer = LightnCandy::prepare($phpStr);

        self::$data->device = self::$device;        
        $array = json_decode(json_encode(self::$data), true);

        $string = $renderer($array);
        file_put_contents(self::$directory . "/" . self::$device . ".html", $string);        
    }

    /**
     * Grid has the img and device name
     */
    public static function prepGrids() {
        $template = file_get_contents(getcwd() . '/vendor/wisnet/behatpom/src/templates/test.hbs.grid');

        $phpStr = LightnCandy::compile($template, array(
            'flags' => 0
            | LightnCandy::FLAG_BESTPERFORMANCE
            | LightnCandy::FLAG_ERROR_EXCEPTION
            | LightnCandy::FLAG_RUNTIMEPARTIAL
            | LightnCandy::FLAG_HANDLEBARS
            | LightnCandy::FLAG_SPVARS
        ));
            
        // set compiled PHP code into $phpStr
        $renderer = LightnCandy::prepare($phpStr);

        
        self::$data->device = self::$device;
        $array = json_decode(json_encode(self::$data), true);
        
        $string = $renderer($array);
        file_put_contents(self::$directory . "/" . self::$device . ".grid", $string);                
    }
    /**
     * 
     */
    public static function prepMainReport() {
        $toc = new Toc();
        //Get the existing grid files to get the device names
        $glob = self::$directory . "/*.grid";
        foreach (glob($glob) as $filename) {
            $fileParts = pathinfo($filename);
            $tocItem = new TocItem();
            $tocItem->name = $fileParts['filename'];
            array_push($toc->items, $tocItem);
        }
        
        $template = file_get_contents(getcwd() . '/vendor/wisnet/behatpom/src/templates/index.hbs.html');

        $phpStr = LightnCandy::compile($template, array(
            'flags' => 0
            | LightnCandy::FLAG_BESTPERFORMANCE
            | LightnCandy::FLAG_ERROR_EXCEPTION
            | LightnCandy::FLAG_RUNTIMEPARTIAL
            | LightnCandy::FLAG_HANDLEBARS
            | LightnCandy::FLAG_SPVARS
        ));
            
        // set compiled PHP code into $phpStr
        $renderer = LightnCandy::prepare($phpStr);
        
        $array = json_decode(json_encode($toc), true);
        
        $string = $renderer($array);
        file_put_contents(self::$directory . "/" . "index.html", $string);                
    }    
    /** @AfterSuite */
    public static function teardown(AfterSuiteScope $scope)  {
        FeatureContext::prepGrids();

        FeatureContext::prepDevice();

        FeatureContext::prepMainReport();

        copy(getcwd() . '/vendor/wisnet/behatpom/src/assests/failure.png', self::$directory . '/screenshots/failure.png');
        copy(getcwd() . '/vendor/wisnet/behatpom/src/assests/jquery.stickytable.min.js', self::$directory . '/jquery.stickytable.min.js');
        copy(getcwd() . '/vendor/wisnet/behatpom/src/assests/jquery.stickytable.min.css', self::$directory . '/jquery.stickytable.min.css');      
        copy(getcwd() . '/vendor/wisnet/behatpom/src/assests/grid-site.min.js', self::$directory . '/grid-site.min.js');

        
        copy(getcwd() . '/vendor/wisnet/behatpom/src/assests/grid-main.css', self::$directory . '/grid-main.css');            
        copy(getcwd() . '/vendor/wisnet/behatpom/src/assests/mod.css', self::$directory . '/mod.css');
        
        //make template
        copy(getcwd() . '//vendor/wisnet/behatpom/src/assests/index.main.php', self::$directory .  '/index.php');            

    }
    /** @BeforeFeature */
    public static function setupFeature(BeforeFeatureScope $scope)  {
        self::$currentFeature = new BehatFeature($scope);
    }
    /** @AfterFeature */
    public static function teardownFeature(AfterFeatureScope $scope)  {
        self::$currentSuite->addFeature(self::$currentFeature);
    }
    /** @BeforeScenario */
    public function before(BeforeScenarioScope $scope) {
        self::$currentScenario = new BehatScenario($scope);
        $this->scenario = $scope->getScenario();
    }
    /** @AfterScenario */
    public function after(AfterScenarioScope $scope) {
        self::$currentFeature->addScenario(self::$currentScenario);
    }
    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope) {
        self::$currentStep = new BehatStep(self::$currentFeature,
                                           $scope);
    }
    /** @AfterStep */
    public function afterStep(AfterStepScope $scope) {
        //Need to process a page before having a session
        if ($this->firstPage) {
            $this->firstPage = false;
            self::$runningOnCBT = $this->checkIfRunningOnCbt();
        }

        if (!$this->scenarioHasTag('snapshot')) {
            return;
        }        
        $screenshotsDir = self::$directory . '/screenshots/';

        if (isset(self::$currentStep->img)) {
            self::$currentStep->device = self::$device;
        }

        self::$currentScenario->addStep(self::$currentStep);
    }       
}
