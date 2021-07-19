<?php

/**
 * The Agora Cloud Recording API Client
 *
 * @link       agora.co
 * @since      1.0.0
 *
 * @package    wp-agora-io
 * @subpackage wp-agora-io/includes
 */
define('AGORA_MIN_RAND_VALUE', 10000000);
define('AGORA_MAX_RAND_VALUE', 4294967295);

error_reporting(E_ALL);
ini_set('display_errors', '1');

class AgoraCloudRecording {
    private $API_URL = 'https://api.agora.io/v1/apps/';
    private $settings = null;
    private $lang_context = 'agoraio';
    private $authAgoraSDK = '';

    public function __construct($settings, $parent) {
        $this->settings = $settings;
        $this->parent = $parent;

        $this->authAgoraSDK = $settings['customerID'].':'.$settings['customerCertificate'];
        $this->authAgoraSDK_B64 = base64_encode($this->authAgoraSDK);
        
        // expose public ajax endpoints:
        $ajaxAgoraCloudRecording = array($this, 'ajaxAgoraCloudRecording');
        add_action( 'wp_ajax_cloud_record', $ajaxAgoraCloudRecording );
        add_action( 'wp_ajax_nopriv_cloud_record', $ajaxAgoraCloudRecording );
    }

    public function ajaxAgoraCloudRecording() {
        if( !isset($_POST['sdk_action']) ) {
            $out = array('error' => 'Invalid SDK Parameters!');
            echo json_encode($out);
            wp_die();
        }

        $action = $_POST['sdk_action'];
        if ($action==='start-recording') {
            $out = $this->startRecording($_POST);
        } else if ($action==='stop-recording') {
            $out = $this->stopRecording($_POST);
        } else if ($action==='query-recording') {
            $out = $this->queryRecording($_POST);
        } else if ($action==='updateLayout') {
            $out = $this->updateLayout($_POST);
        } else {
            $out = array('error' => 'SDK Action not defined!');
        }

        if (is_wp_error( $out )) {
            $code = 500;
            if (isset($out->errors['API'])) {
                $errorOut = $out->errors['API'][0];
                if (property_exists($errorOut, 'code')) {
                    if(intval($errorOut->code)>=400) {
                        $code = $errorOut->code;
                    }
                }
            }
            header('HTTP/1.1 '.$code);
        } else {
            header('HTTP/1.1 200 OK');
        }

        header('Content-Type: application/json');
        echo json_encode($out);
        die();
    }

    private function acquire($data) {
        $endpoint = $this->settings['appId']."/cloud_recording/acquire";

        $channel = WP_Agora_Channel::get_instance($data['cid']);
        $channelSettings    = $channel->get_properties();
        $recordingSettings = $channelSettings['recording'];

        
        //To handle the file in mp4 format - For future reference
        if($recordingSettings['protoType'] == 'individual'){
            $clientRequest = new stdClass();
            $clientRequest->scene = 2;
        } else{
            $clientRequest = json_decode("{}");
        }
        

        //$clientRequest = json_decode("{}");
        
        $params = array(
            'cname' => $data['cname'],
            'uid' => $data['uid'],
            //'clientRequest' => json_decode("{}")
            'clientRequest' => $clientRequest
        );
        return $this->callAPI($endpoint, $params, 'POST');
    }

    private function updateLayout($data) {
        
        if (isset($data['resourceId']) && !empty($data['resourceId'])) {
           $resourceId = $data['resourceId'];
        } else {
            $resource = $this->acquire($data);
            $resourceId = $resource->resourceId;
        }

        if (!isset($data['recordingId']) ) {
            return new WP_Error( 'data', "Incomplete data", $data );
        }

        $sid = $data['recordingId'];

        $channel = WP_Agora_Channel::get_instance($data['cid']);
        $channelSettings    = $channel->get_properties();
        $recordingSettings = $channelSettings['recording'];
        
        if($recordingSettings['protoType'] != 'individual'){
            $endpointUL = $this->settings['appId'].'/cloud_recording/resourceid/' . $resourceId . '/sid/' . $sid. '/mode/mix/updateLayout';

            $mixedVideoLayout = 1; // best fit layout

            if($recordingSettings['recording_layout'] !=''){
                $mixedVideoLayout = $recordingSettings['recording_layout'];
            }

            $clientRequest = new stdClass();
            $clientRequest->mixedVideoLayout = (int)$mixedVideoLayout;

            //In case of vertical layout, specify large screen UID
            if($mixedVideoLayout == 2){
                $clientRequest->maxResolutionUid = $data['maxResolutionUid'];
            }

            $clientRequest->backgroundColor = "#000000";

            $params = array(
                'cname' => $data['cname'],
                'uid' => $data['uid'],
                'clientRequest' => $clientRequest
            );
            // header('HTTP/1.1 500 Internal Server Error');
            // die("<pre>QUERY:".print_r($endpoint, true)."</pre>");
            return $this->callAPI($endpointUL, $params, 'POST');
        } else {
            return true;
        }
    }

    private function queryRecording($data) {
        
        if (isset($data['resourceId']) && !empty($data['resourceId'])) {
           $resourceId = $data['resourceId'];
        } else {
            $resource = $this->acquire($data);
            $resourceId = $resource->resourceId;
        }

        if (!isset($data['recordingId']) ) {
            return new WP_Error( 'data', "Incomplete data", $data );
        }

        $sid = $data['recordingId'];
        $endpoint = $this->settings['appId'].'/cloud_recording/resourceid/' . $resourceId . '/sid/' . $sid. '/mode/mix/query';
        
        $params = array(
            'cname' => $data['cname'],
            'uid' => $data['uid'],
            'clientRequest' => json_decode("{}")
        );
        // header('HTTP/1.1 500 Internal Server Error');
        // die("<pre>QUERY:".print_r($endpoint, true)."</pre>");
        return $this->callAPI($endpoint, array(), 'GET');
    }

    private function startRecording($data) {

        $currentUserId = $data['uid'];
        $maxResolutionUid = $data['maxResolutionUid'];

        $data['uid'] = ''.rand(AGORA_MIN_RAND_VALUE, AGORA_MAX_RAND_VALUE);

        $resource = $this->acquire($data);
        //die("R:<pre>".print_r($resource, true)."</pre>");
        $resourceId = $resource->resourceId;
        
        $channel = WP_Agora_Channel::get_instance($data['cid']);
        $channelSettings    = $channel->get_properties();
        $recordingSettings = $channelSettings['recording'];
        if (!isset($recordingSettings['bucket'])) {
            return new WP_Error( 'data', "Storage Config not finished." );
        }

        $recordType = 'mix';
        if($recordingSettings['protoType'] == 'individual'){
            $recordType = 'individual';
        } else {
            $mixedVideoLayout = 1; // best fit layout
            if($recordingSettings['recording_layout'] !=''){
                $mixedVideoLayout = $recordingSettings['recording_layout'];
            }
        }

        // $sid = $data['sid'];
        $endpoint = $this->settings['appId'].'/cloud_recording/resourceid/' . $resourceId . '/mode/'.$recordType.'/start';

        $clientRequest = new stdClass();
        $clientRequest->recordingConfig = new stdClass();
        $clientRequest->recordingConfig->channelType = 1; // 1 = broadcast,  0=Communication

        if($recordingSettings['protoType'] != 'individual'){
            $clientRequest->recordingConfig->transcodingConfig = new stdClass();
            $clientRequest->recordingConfig->transcodingConfig->mixedVideoLayout = (int)$mixedVideoLayout; // best fit layout

            //In case of vertical layout, specify large screen UID
            if($mixedVideoLayout == 2){
                $clientRequest->recordingConfig->transcodingConfig->maxResolutionUid = $data['maxResolutionUid'];
            }

            $clientRequest->recordingConfig->transcodingConfig->backgroundColor = "#000000";
            $clientRequest->recordingConfig->transcodingConfig->width = 848;
            $clientRequest->recordingConfig->transcodingConfig->height = 480;
            $clientRequest->recordingConfig->transcodingConfig->bitrate = 930;
            $clientRequest->recordingConfig->transcodingConfig->fps = 30;
            /*
            //To handle the file in mp4 format - For future reference
            $clientRequest->recordingFileConfig = new stdClass();
            $clientRequest->recordingFileConfig->avFileType = ["hls", "mp4"];
            */
        } else{
            //$clientRequest->recordingConfig->combinationPolicy = 'postpone_transcoding';
            $clientRequest->recordingConfig->subscribeUidGroup = 0;
            // $clientRequest->recordingConfig->subscribeVideoUids = ["1"];
            // $clientRequest->recordingConfig->subscribeAudioUids = ["1"];
        }
        $clientRequest->storageConfig = new stdClass();
        $clientRequest->storageConfig->vendor = intval($recordingSettings['vendor']);
        $clientRequest->storageConfig->region = intval($recordingSettings['region']);
        $clientRequest->storageConfig->bucket = $recordingSettings['bucket'];
        $clientRequest->storageConfig->accessKey = $recordingSettings['accessKey'];
        $clientRequest->storageConfig->secretKey = $recordingSettings['secretKey'];

        $t=date('d-m-Y');
        $day = strtolower(date("d", strtotime($t)));
        $month = strtolower(date("m", strtotime($t)));
        $year = strtolower(date("Y", strtotime($t)));
        
        $fixedTitle = str_replace('-', '', $channel->title());
        $folderName = $month.$day.$year.preg_replace('/\s+/', '', $fixedTitle);
        $folderName = $data['cid'];
        
        //$clientRequest->storageConfig->fileNamePrefix = array( $folderName );
        $clientRequest->storageConfig->fileNamePrefix = array($folderName, $recordingSettings['protoType']);
        
        $newToken = $this->parent->generateNewToken($data['cid'], $data['uid'], 'RTC');
        // die("<pre>".print_r($newToken, true)."</pre>");
        $clientRequest->token = $newToken;

        
        //To handle the file in mp4 format - For future reference
        if($recordingSettings['protoType'] == 'individual'){
            $clientRequest->appsCollection = new stdClass();
            $clientRequest->appsCollection->combinationPolicy = 'postpone_transcoding';
        }
        
        $params = array(
            'cname' => $data['cname'],
            'uid' => $data['uid'],
            'clientRequest' => $clientRequest
        );

        // die("<pre>".print_r($endpoint, true)."</pre>");
        $out = $this->callAPI($endpoint, $params, 'POST');
        if (!is_wp_error( $out )) {
            $out->uid = $data['uid'];
        }
        return $out;
    }


    private function stopRecording($data) {
        
        if (isset($data['resourceId']) && !empty($data['resourceId'])) {
           $resourceId = $data['resourceId'];
        } else {
            $resource = $this->acquire($data);
            $resourceId = $resource->resourceId;
        }

        if (!isset($data['cid']) || !isset($data['recordingId']) ) {
            return new WP_Error( 'data', "Incomplete data", $data );
        }

        $channel = WP_Agora_Channel::get_instance($data['cid']);
        $channelSettings    = $channel->get_properties();

        $recordType = 'mix';
        $recordingSettings = $channelSettings['recording'];
        if($recordingSettings['protoType'] == 'individual'){
            $recordType = 'individual';
        }

        $sid = $data['recordingId'];
        $endpoint = $this->settings['appId'].'/cloud_recording/resourceid/' . $resourceId . '/sid/' . $sid. '/mode/'.$recordType.'/stop';
        
        $params = array(
            'cname' => $data['cname'],
            'uid' => $data['uid'],
            'clientRequest' => json_decode("{}")
        );
        return $this->callAPI($endpoint, $params, 'POST');
    }


    /**
     * HTTP API Call
     *
     * Remote HTTP Call with cache usage through wp transients.
     **/
    private function callAPI($url=false, $params=array(), $method='GET') {
        if ($url) {
            $url = $this->API_URL . $url;

            $args = array(
                'timeout' => '15',
                'headers' => array(   
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $this->authAgoraSDK_B64
                )
            );

            if ($method==='GET') {
                if (!empty($params)) {
                    $query_params = http_build_query($params);
                    $url = sprintf("%s?%s", $url, $query_params);
                }
                // echo $url." - GET:<pre>".print_r($args, true)."</pre>";
                // die();
                $response = wp_remote_get( $url, $args );
            } else {
                $args['body'] = json_encode($params);
                // echo $url." - POST:<pre>".print_r($args, true)."</pre>";
                // die();
                $response = wp_remote_post( $url, $args );
            }
            
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                return new WP_Error('API', $error_message, $params);
            }

            if ($response) {
                $http_code = wp_remote_retrieve_response_code( $response );
                $body = wp_remote_retrieve_body( $response );

                if ($http_code >= 300) {
                    return new WP_Error('API', $body, $params);
                }

                return json_decode($body);
            } else {
                return new WP_Error( 'data', 'No response from server', $params );
            }
        }
        return false;
    }
}