<?php
require_once('SQLQueryHandler.php');
require_once('RemoteFileHandler.php');

class YouTubeVideo {
    public $mIdentificationToken = "";
    public $mTitle = "";
}

class YouTubeVideos {

    private $mSQLQueryHandler = null;

    public function __construct($pSecurityLevel) {
        $this->mSQLQueryHandler = new SQLQueryHandler($pSecurityLevel);
    }

    public function getYouTubeVideo($pRecordIdentifier) {
        $lQueryResult = $this->mSQLQueryHandler->getYouTubeVideo($pRecordIdentifier);
        $lNewYouTubeVideo = new YouTubeVideo();
        $lNewYouTubeVideo->mIdentificationToken = $lQueryResult->identificationToken;
        $lNewYouTubeVideo->mTitle = $lQueryResult->title;
        return $lNewYouTubeVideo;
    }
}

class YouTubeVideoHandler {

    /* Private properties */
    private $mSecurityLevel = 0;
    private $mYouTubeVideos = null;
    private $mCurlIsInstalled = false;
    private $mYouTubeIsReachable = false;
    private $mRemoteFileHandler = null;

    /* Consolidated guides as associative array */
    public $guides = [];

    /* Constructor */
    public function __construct($pSecurityLevel) {
        $this->mSecurityLevel = (int)$pSecurityLevel;
        $this->mYouTubeVideos = new YouTubeVideos($pSecurityLevel);
        $this->mRemoteFileHandler = new RemoteFileHandler($pSecurityLevel);
        $this->mCurlIsInstalled = $this->curlIsInstalled();
        $this->mYouTubeIsReachable = $this->isYouTubeReachable();

        // Inicializar guías (ejemplo)
        $this->guides = [
            'JWTSecurity' => 1,
            'OWASPDependencyCheck' => 9,
            'MutillidaeLab1' => 245,
            'MutillidaeLab2' => 252,
            'AnalyzeSessionToken' => 315
        ];
    }

    /* Private methods */
    private function curlIsInstalled() {
        return function_exists("curl_init");
    }

    private function fetchVideoPropertiesFromYouTube($pVideoIdentificationToken) {
        $lYouTubeResponse = "";

        try {
            if ($this->mCurlIsInstalled) {
                $lCurl = curl_init();
                $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . urlencode($pVideoIdentificationToken) . "&format=json";
                curl_setopt($lCurl, CURLOPT_URL, $url);
                curl_setopt($lCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($lCurl, CURLOPT_CONNECTTIMEOUT, 5);
                $lYouTubeResponse = curl_exec($lCurl);
                
                if (curl_errno($lCurl)) {
                    $lYouTubeResponse = "";
                    error_log("cURL error: " . curl_error($lCurl));
                }

                curl_close($lCurl);
            }
        } catch (Exception $e) {
            $lYouTubeResponse = "";
        }

        return $lYouTubeResponse;
    }

    private function isYouTubeReachable() {
        $response = $this->fetchVideoPropertiesFromYouTube("DJaX4HN2gwQ");
        return !empty($response);
    }

    private function getYouTubeIsNotReachableAdvice() {
        return '<br/><span style="background-color: #ffff99;">Warning: Could not reach YouTube via network connection. Failed to embed video.</span><br/>';
    }

    private function getNoCurlAdviceBasedOnOperatingSystem() {
        $os = strtoupper(PHP_OS);
        $advice = "";

        if ($os === "LINUX") {
            $advice = "Use sudo apt-get install php-curl or the appropriate PHP version package (e.g., php7.4-curl).";
        } elseif ($os === "WINNT") {
            $advice = "Enable extension=php_curl.dll in php.ini and restart Apache.";
        }

        return '<br/><span style="background-color: #ffff99;">Warning: PHP Curl is not installed. '.$advice.'</span><br/>';
    }

    private function generateYouTubeFrameHTML($pVideoIdentificationToken) {
        $lUniqueId = uniqid();
        $pVideoIdentificationToken = htmlspecialchars($pVideoIdentificationToken, ENT_QUOTES, 'UTF-8');

        return '
            <script>
                var lYouTubeFrameCode'.$lUniqueId.' = \'<iframe width="640" height="480" src="https://www.youtube.com/embed/'.$pVideoIdentificationToken.'?autoplay=1" frameborder="0" allowfullscreen></iframe>\';
            </script>
            <span>
                <a href="#" id="btn-load-video'.$lUniqueId.'" onclick="document.getElementById(\'the-player'.$lUniqueId.'\').innerHTML=lYouTubeFrameCode'.$lUniqueId.';">
                    Load the video
                </a>
            </span>
            <div id="the-player'.$lUniqueId.'"></div>
        ';
    }

    /* Public methods */
    public function getYouTubeVideo($pVideo) {
        $lHTML = "";
        $lVideo = $this->mYouTubeVideos->getYouTubeVideo($pVideo);
        $lVideoIdentificationToken = htmlspecialchars($lVideo->mIdentificationToken, ENT_QUOTES, 'UTF-8');
        $lVideoTitle = htmlspecialchars($lVideo->mTitle, ENT_QUOTES, 'UTF-8');

        if (!$this->mCurlIsInstalled) {
            $lHTML .= $this->getNoCurlAdviceBasedOnOperatingSystem();
        }

        if (!$this->mYouTubeIsReachable) {
            $lHTML .= $this->getYouTubeIsNotReachableAdvice();
        }

        // Mostrar video como enlace si no se puede cargar iframe
        $lHTML .= '<br/><a href="https://www.youtube.com/watch?v='.$lVideoIdentificationToken.'" target="_blank">
                        <img style="margin-right: 5px;" src="images/youtube-play-icon-40-40.png" alt="YouTube" />
                        <span class="label">'.$lVideoTitle.'</span>
                    </a>';

        return $lHTML;
    }
}
?>