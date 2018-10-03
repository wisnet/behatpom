<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;
use LightnCandy\LightnCandy;
//Table of content for index report
class Toc {
    public $items = [];
}
class TocItem {
    public $name;
}

class GridData {
    public $devices = [];
    public $pages = [];
}
class GridDevice {
    public $name;
    public $status;
    public $logContent;
}
class GridPage {
    public $name;
    public $images = [];
}
class GridImage {
    public $img;
}
class GridReport {
    private $first = true;
    private $gridFiles;
    private $matrix;
    private $buildDir;

    private $numberOfImages;
    
    public function __construct($buildDir) {
        $this->buildDir = $buildDir;
    }
    /*
     * read all the files ending w/ glob pattern
     */
    public function readFiles($globPattern) {
        $files = [];
        $glob = "$this->buildDir/$globPattern";
        foreach (glob($glob) as $filename) {
            array_push($files, $filename);
        }
        return $files;
    }
    /* 
     * the left most column contains the page name
     */
    public function buildLeftMostColumn($file) {
        $this->numberOfImages = 0;
        $handle = fopen($file, "r");
        if ($handle) {
            $this->matrix['device'] = [];
            while (($line = fgets($handle)) !== false) {
                $this->numberOfImages += 1;
                $parts = explode(" ", $line);
                $this->matrix[$parts[0]] = [];
            }
            fclose($handle);
        } else {
            eval(\Psy\sh());            
            // error opening the file.
        }         

    }
    public function readLogFile($device) {
        foreach($this->logFiles as $logFile) {
            $pathInfo = pathInfo($logFile);
            $exploded = explode('.',$pathInfo['filename']);
            if ($exploded[0] == $device) {
                $log = file_get_contents($logFile);
                return array (
                    'status' => $exploded[1],
                    'log' => $log
                    );
            }
        }
    }
       
    /*
     * for each device screenshot, add to matrix
     */
    public function buildColumns($file) {
        $pathInfo = pathInfo($file);
        $handle = fopen($file, "r");
        
        if ($handle) {
            array_push($this->matrix['device'], $pathInfo['filename']);
            $numberImages = 0;        
            while (($line = fgets($handle)) !== false) {
                $numberImages += 1;
                $parts = explode(" ", $line);
                $parts[1] = str_replace("\n", "", $parts[1]);
                array_push($this->matrix[$parts[0]], $parts[1]);
            }
            //supply failure image so that all row/columns are consistent
            if ($this->numberOfImages !=  $numberImages) {
                $pages = array_keys($this->matrix);
                for ($pos = $numberImages; $pos < $this->numberOfImages; $pos++) {
                    $page = $pages[$pos + 1];
                    array_push($this->matrix[$page], "screenshots/failure.png");
                }
            }

            fclose($handle);
        } else {
            eval(\Psy\sh());            
            // error opening the file.
        }         
        
    }
    public function arrayToObject($array) {
        $data = new GridData();
        
        //First biuld the headings
        foreach($array['device'] as $device) {
            $gridDevice = new GridDevice();
            $gridDevice->name = $device;
            $logStatus = $this->readLogFile($device);
            $gridDevice->status = $logStatus['status'];
            $gridDevice->logContent = $logStatus['log'];

            array_push($data->devices, $gridDevice);
        }
        //Remove devicd from set of keys
        $pages = array_keys($array);
        unset($pages[0]);

        foreach($pages as $page) {
            $gridPage = new GridPage();
            $gridPage->name = $page;
            array_push($data->pages, $gridPage);
            
            foreach($array[$page] as $img) {
                $gridImage = new GridImage();
                $gridImage->img = $img;
                array_push($gridPage->images, $gridImage);
            }
        }
        return $data;
    }
    public function makeTocItems($toc, $glob) {
        foreach (glob($glob) as $filename) {
            $fileParts = pathinfo($filename);
            $tocItem = new TocItem();
            $tocItem->name = $fileParts['filename'];
            array_push($toc->items, $tocItem);
        }
        return $toc;

    }
    /**
     * build the index.html file w/ the TOC being the device names
     */
    public function prepMainReport() {
        $toc = new Toc();
        //Get the existing grid files to get the device names
        $toc = $this->makeTocItems($toc, "$this->buildDir/Gal*.grid");
        $toc = $this->makeTocItems($toc, "$this->buildDir/Nex*.grid");        
        $toc = $this->makeTocItems($toc, "$this->buildDir/iPh*.grid");
        $toc = $this->makeTocItems($toc, "$this->buildDir/iPad*.grid");
        $toc = $this->makeTocItems($toc, "$this->buildDir/Mac*.grid");
        $toc = $this->makeTocItems($toc, "$this->buildDir/Win*.grid");                                
        
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
        file_put_contents("$this->buildDir/index.html", $string);                
    }        
    /**
     * Prepare a json file with all the imgages for each device
     */
    public function genGridReport() {
        $this->gridFiles = $this->readFiles('Nex*.grid');
        $this->gridFiles = array_merge($this->gridFiles, $this->readFiles('Gal*.grid'));
        $this->gridFiles = array_merge($this->gridFiles, $this->readFiles('Nex*.grid'));        
        $this->gridFiles = array_merge($this->gridFiles, $this->readFiles('iPho*.grid'));
        $this->gridFiles = array_merge($this->gridFiles, $this->readFiles('iPad*.grid'));
        $this->gridFiles = array_merge($this->gridFiles, $this->readFiles('Mac*.grid'));
        $this->gridFiles = array_merge($this->gridFiles, $this->readFiles('Win*.grid'));                        
        $this->logFiles = $this->readFiles('*.log');

        foreach($this->gridFiles as $key=>$file) {
            //Assume that first device is successfull w/ all images
            if ($this->first) {
                $this->first = false;
                $thead = $this->buildLeftMostColumn($file);
            }
            $this->buildColumns($file);
        }

        try {
            $templateFilename = getcwd() . "/vendor/wisnet/behatpom/src/templates/grid-report.hbs.html";
            $template = file_get_contents($templateFilename);

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

            $obj = $this->arrayToObject($this->matrix);
            $array = json_decode(json_encode($obj), true);
            
            $string = $renderer($array);
            file_put_contents("$this->buildDir/grid.html", $string);
        } catch (Exception $e) {
            eval(\Psy\sh());
        }
    }    
    /* build a array of arrays
     */
    public function buildMatrix() {
        $this->genGridReport();

        $this->prepMainReport();
    }
}
