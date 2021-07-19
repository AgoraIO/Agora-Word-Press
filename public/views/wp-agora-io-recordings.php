<?php 

 require __DIR__.'/../third-party/amazonAWSLibrary/aws-autoloader.php';
 use Aws\S3\S3Client;
 use Aws\S3\Exception\S3Exception;

 require __DIR__.'/../third-party/alibabaLibrary/autoload.php';
 use OSS\OssClient;
 use OSS\Core\OssException;

function getRecordingsList($atts) {

    $output = '';

    if(isset($atts['channel_id']) && isset($atts['recording_type'])){

        $channel_id = $atts['channel_id'];
        try{
            $channel = WP_Agora_Channel::get_instance($channel_id);

            if($channel == ''){
                return;
            }
        
            $channelSettings    = $channel->get_properties();
            $recordingSettings = $channelSettings['recording'];

            $bucket = $recordingSettings['bucket'];
            $accessKey = $recordingSettings['accessKey'];
            $secretKey = $recordingSettings['secretKey'];

            $bucketregion = $recordingSettings['region'];

            $recordings_regions = WP_Agora_Public::$recordings_regions;


            $allFiles = array();

            $keyname = $channel_id.'/'.$atts['recording_type'].'/';

            if($recordingSettings['vendor'] == 1){ //Amazon S3

                //$recordings_regions['aws'][$region];

                $bucketregion = str_replace("_","-",strtolower($recordings_regions['aws'][$bucketregion]));

                $s3 = S3Client::factory([
                    'version'     => 'latest',
                    //'region'      => 'us-east-1',
                    'region'      => $bucketregion,
                    'credentials' => [
                        'key'    => $accessKey,
                        'secret' => $secretKey,
                    ]
                ]);

                $result = $s3->listObjects(array('Bucket' => $bucket, 'Prefix' => $keyname));
                $files = $result->getPath('Contents');

                if($files!=""){
                    foreach ($files as $file) {
                        
                        $filename = $file['Key'];
                        $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
                        $amazonawsURL = 'http://%s.s3.amazonaws.com/%s';

                        if (array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] == "on") {
                            $amazonawsURL = 'https://%s.s3.amazonaws.com/%s';
                        }
                        if(($atts['recording_type'] == 'individual' && $fileExt == "mp4") || ($atts['recording_type'] == 'composite' && $fileExt == "m3u8")){
                            $allFiles[] = sprintf($amazonawsURL, $bucket, $filename); 
                        }
                    }
                }

            } else if($recordingSettings['vendor'] == 2){ //Alibaba Cloud

                $endpointregion = str_replace("_","-",strtolower($recordings_regions['alibaba'][$bucketregion]));

                //$endpoint = "oss-us-east-1.aliyuncs.com";
                $endpoint = "oss-".$endpointregion.".aliyuncs.com";

                try {
                    $ossClient = new OssClient($accessKey, $secretKey, $endpoint);
                
                    $options = array(
                        'prefix' => $keyname,
                    );
                    try {
                        $listObjectInfo = $ossClient->listObjects($bucket, $options);
                    } catch (OssException $e) {
                        printf(__FUNCTION__ . ": FAILED\n");
                        printf($e->getMessage() . "\n");
                        return;
                    }
                    
                    $objectList = $listObjectInfo->getObjectList(); // object list
                    
                    if (! empty($objectList)) {
                        foreach ($objectList as $objectInfo) {
                            $fileExt = pathinfo($objectInfo->getKey(), PATHINFO_EXTENSION);
                            if(($atts['recording_type'] == 'individual' && $fileExt == "mp4") || ($atts['recording_type'] == 'composite' && $fileExt == "m3u8")){
                                $allFiles[] = 'https://'.$bucket.'.'.$endpoint.'/'.$objectInfo->getKey();
                            }
                        }
                    }
                
                    
                } catch (OssException $e) {
                    print $e->getMessage();
                }
            }

            /*$uniqueKey = '121';

            $channel = WP_Agora_Channel::get_instance($atts['channel_id']);

            $channelSettings    = $channel->get_properties();
            $recordingSettings = $channelSettings['recording']; ?>
            <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
            <?php 
            // echo $recordingSettings['region'];
            // die;

            $bucket = $recordingSettings['bucket'];

            $s3 = S3Client::factory([
                'version'     => 'latest',
                'region'      => 'us-east-1',
                'credentials' => [
                    'key'    => $recordingSettings['accessKey'],
                    'secret' => $recordingSettings['secretKey'],
                ]
            ]);
            
            //$keyname = 'input/'.$uniqueKey.'/';
            $keyname = $uniqueKey.'/';
            
            $response = array();
            
            $result = $s3->listObjects(array('Bucket' => $bucket, 'Prefix' => $keyname));
            
            $files = $result->getPath('Contents');
            if($files!=""){
                foreach ($files as $file) {
                    
                    $filename = $file['Key'];
                    $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
                    $amazonawsURL = 'http://%s.s3.amazonaws.com/%s';

                    $cmd = $s3->getCommand('GetObject', [
                        'Bucket' => $bucket,
                        'Key' => $filename
                    ]);
            
                    $request = $s3->createPresignedRequest($cmd, '+20 minutes');
                    $presignedUrl = (string)$request->getUri();

                    if (array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] == "on") {
                        $amazonawsURL = 'https://%s.s3.amazonaws.com/%s';
                    }
                    if($fileExt == "m3u8"){
                        //     echo "<pre>";
                        //     print_r($file);
                        // die;

                        echo " filename ".$filename;

                

                            $response[] = 
                            // array(
                            // 	'url' => sprintf($amazonawsURL, $bucket, $filename)
                            // );
                            //sprintf($amazonawsURL, $bucket, $filename); 
                            $presignedUrl;
                    }
                }
            } 
            if(count($response)>0){ 
            $i=0;
            foreach($response as $file){ 
                echo $file;
                ?>
                <video id="video-<?php echo $i; ?>" controls></video>
                <script>
                if(Hls.isSupported())
                {
                    var video = document.getElementById('video-<?php echo $i; ?>');
                    var hls = new Hls();
                    hls.loadSource("<?php echo $file; ?>");
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED,function()
                    {
                        //video.play();
                    });
                }
                else if (video.canPlayType('application/vnd.apple.mpegurl'))
                {
                    video.src = "<?php echo $file; ?>";
                    video.addEventListener('canplay',function()
                    {
                        //video.play();
                    });
                }
                </script>
            <?php $i++; } }*/
            ?>
            <?php

            if(count($allFiles)>0){ 
                $i=0;
                $output.= '<div class="agora_io_video_recording_container">';

                if(isset($atts['recording_type']) && ($atts['recording_type'] == 'individual')){
                    $output.= '<div class="recording_tooltip">If your recording is not available right now, please try after some time as it may take some time to process ..</div>';
                }

                foreach($allFiles as $file){ 

                    $output.='<div class="agora_io_video_container"><video id="video-'.$i.'" controls></video></div>';

                    $output .= '<script>';
                    $output .= "var video = document.getElementById('video-".$i."');";
                    $output .= "if(Hls.isSupported()){";
                    $output .= "var hls = new Hls();";
                    $output .= "hls.loadSource('".$file."');";
                    $output .= "hls.attachMedia(video);";
                    $output .= "}";

                    $output .= "else if (video.canPlayType('application/vnd.apple.mpegurl')){";
                    $output .= "video.src = '".$file."';";
                    $output .= "}";
                    $output .= " else {";
                    $output.= "var source = document.createElement('source');";
                    $output.= "source.src = '".$file."';";
                    $output.= "source.type = 'video';";
                    $output.= "video.appendChild(source);";
                    $output .= "}";
                    $output .= '</script>';

                    // echo $file;
                    /*  ?>
                    <video id="video-<?php echo $i; ?>" controls></video>
                    <script>
                    if(Hls.isSupported())
                    {
                        var video = document.getElementById('video-<?php echo $i; ?>');
                        var hls = new Hls();
                        hls.loadSource("<?php echo $file; ?>");
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED,function()
                        {
                            //video.play();
                        });
                    }
                    else if (video.canPlayType('application/vnd.apple.mpegurl'))
                    {
                        video.src = "<?php echo $file; ?>";
                        video.addEventListener('canplay',function()
                        {
                            //video.play();
                        });
                    }
                    </script>
                    <?php */ 
                    $i++; 
                } 
                $output.= '</div>';
            }
        } catch(Exception $e) {
            print_r($e);
            return;
        }
    }
    return $output;
}
?>