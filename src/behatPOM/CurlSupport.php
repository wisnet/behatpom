<?php
/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

namespace Wisnet\BehatPom;

class CurlSupport {
    protected $user;
    protected $authkey;
    
    function __construct($username, $authkey, $screenshot_test_id=NULL) {
		$this->user = $username;
		$this->authkey = $authkey;
	}
    
    function callApi($api_url, $method = 'GET', $params = false){
        $count = 0;
        while (TRUE) {
            try {
                $response = $this->_callApi($api_url, $method, $params);
                if ($count !== 0) {
                    echo "Finally succeeded!\n";
                }
                return $response;
            } catch (\Exception $e) {
                $count++;
                echo 'CurlSupport failed ' . $count . " times.  Trying again.\n";
                sleep(2);
            }
        }
        
    }
    function _callApi($api_url, $method = 'GET', $params = false){        
        
        $apiResult = new \stdClass();

        $process = curl_init();

        switch ($method){
        case "POST":
            curl_setopt($process, CURLOPT_POST, 1);

            if ($params){
                curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($process, CURLOPT_HTTPHEADER, array('User-Agent: php')); //important
            }
            break;
        case "PUT":
            curl_setopt($process, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($params){
                curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($process, CURLOPT_HTTPHEADER, array('User-Agent: php')); //important
            }
            break;
        case 'DELETE':
            curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        default:
            if ($params){
                $api_url = sprintf("%s?%s", $api_url, http_build_query($params));
            }
        }

        // Optional Authentication:
        curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($process, CURLOPT_USERPWD, $this->user . ":" . $this->authkey);

        curl_setopt($process, CURLOPT_URL, $api_url);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);

        $apiResult->content = curl_exec($process);
        $apiResult->httpResponse = curl_getinfo($process);	
        $apiResult->errorMessage =  curl_error($process);
        $apiResult->params = $params;

        curl_close($process);

        //print_r($apiResult);

        $paramsString = $params ? http_build_query($params) : '';
        $response = json_decode($apiResult->content);

        if ($apiResult->httpResponse['http_code'] != 200){
            $message = 'Error calling "' . $apiResult->httpResponse['url'] . '" ';
            $message .= (isset($paramsString) ? 'with params "'.$paramsString.'" ' : ' ');
            $message .= '. Returned HTTP status ' . $apiResult->httpResponse['http_code'] . ' ';
            $message .= (isset($apiResult->errorMessage) ? $apiResult->errorMessage : ' ');
            $message .= (isset($response->message) ? $response->message : ' ');
            echo $message . "\n";
            throw new \Exception($message);
        }
        else {
            $response = json_decode($apiResult->content);
            if (isset($response->status)){
                $message = 'Error calling "' . $apiResult->httpResponse['url'] . '"' .(isset($paramsString) ? 'with params "'.$paramsString.'"' : '') . '". ' . $response->message;
                echo $message . "\n";
                throw new \Exception($message);
            }
        }

        return $response;
    }

}