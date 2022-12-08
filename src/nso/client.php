<?php

/**
 * Nintendo Switch Online
 */
class NSO
{
    const API_TOKEN_URL = 'https://accounts.nintendo.com/connect/1.0.0/api/token';
    const API_USER_MR_URL = 'https://api.accounts.nintendo.com/2.0.0/users/me';
    const ACCESS_TOKEN_URL = 'https://api-lp1.znc.srv.nintendo.net/v3/Account/Login';
    const FRIEND_LIST_URL = 'https://api-lp1.znc.srv.nintendo.net/v3/Friend/List';

    const NSO_APPLE_PAGE_URL = 'https://apps.apple.com/us/app/nintendo-switch-online/id1234806557';
    const NSO_LATEST_VERSION_TAG_NAME = 'p';
    const NSO_LATEST_VERSION_CLASS_NAME = 'whats-new__latest__version';

    // ref https://github.com/JoneWang/imink/wiki/imink-API-Documentation
    const F_GEN_URL = 'https://api.imink.app/f';
    const HASH_METHOD_NUMBER = 1;
    const USER_AGENT_FOR_F = 'NSO-FPN/1.0.0';

    const ACCESS_TOKEN_EXPIRED_IN = 7200;

    private string $accessTokenFilePath = 'nso/credentials/access_token.txt';

    private string $iosVersion = '16.0.0';

    private string $nsoAppVersion;

    private string $sessionToken;
    private string $naIdToken;
    private string $f;
    private string $timestamp;
    private string $requestId;
    private string $naAccessToken; // Nintendo Accountのためのアクセストークン
    private string $language;
    private string $naBirthday;
    private string $naCountry;
    private string $accessToken;

    public function __construct()
    {
        $this->setSessionToken(getenv('nso_session_token'));
        $this->fetchNsoAppVersion();
        $this->fetchAccessToken();
    }

    public function getFriendList()
    {
        $header = [
            'Host:api-lp1.znc.srv.nintendo.net',
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'Accept-Language: ja-JP;q=1.0',
            'User-Agent: Coral/'.$this->nsoAppVersion.' (com.nintendo.znca; build:2400; iOS '.$this->iosVersion.') Alamofire/5.6.1',
            'Authorization: Bearer '.$this->accessToken,
        ];

        $body = [];

        $response = $this->sendRequest(self::FRIEND_LIST_URL, $header, $body);

        return $response['result']['friends'];
    }

    private function fetchNsoAppVersion()
    {
        $html = file_get_contents(self::NSO_APPLE_PAGE_URL);

        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $pElements = $dom->getElementsByTagName(self::NSO_LATEST_VERSION_TAG_NAME);
        foreach ($pElements as $pElement) {
            if ($pElement->hasAttribute('class') && strpos($pElement->getAttribute('class'), self::NSO_LATEST_VERSION_CLASS_NAME) !== false) {
                $version = str_replace('Version  ', '', $pElement->textContent);
                $this->setNsoAppVersion($version);
            }
        }
    }

    private function fetchToken()
    {
        $header = [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'Accept-Language: ja-JP;q=1.0',
            'User-Agent: Coral/'.$this->nsoAppVersion.' (com.nintendo.znca; build:2400; iOS '.$this->iosVersion.') Alamofire/5.6.1',
            'Accept-Encoding: gzip, deflate',
        ];

        $body = [
            'client_id'     => '71b963c1b7b6d119',
            'session_token' => $this->sessionToken,
            'grant_type'    => 'urn:ietf:params:oauth:grant-type:jwt-bearer-session-token',
        ];

        $response = $this->sendRequest(self::API_TOKEN_URL, $header, $body);

        $this->setNaIdToken($response['id_token']);
        $this->setNaAccessToken($response['access_token']);
    }

    private function isAccessTokenActive():bool
    {
        $fileContent = file_get_contents($this->accessTokenFilePath);

        // ファイルの取得に失敗した場合はfalse
        if($fileContent === false){
            return false;
        }
        //ファイルの中身が空の場合
        if($fileContent === ''){
            return false;
        }
        // トークンが有効期限切れ（念の為、バッファを持たせて有効期限が切れる11分前に無効判定）
        $buffer = 660;
        if(time() - filemtime($this->accessTokenFilePath) > self::ACCESS_TOKEN_EXPIRED_IN - $buffer){
            return false;
        }

        return true;
    }

    private function fetchAccessToken()
    {
        if($this->isAccessTokenActive()){
            $this->setAccessToken(file_get_contents($this->accessTokenFilePath));
            return;
        }

        $this->fetchToken();
        $this->fetchUserInfo();
        $this->fetchF();

        $header = [
            'Content-Type: application/json',
            'Connection: keep-alive',
            'X-ProductVersion: '.$this->nsoAppVersion,
            'Accept: application/json',
            'User-Agent: Coral/'.$this->nsoAppVersion.' (com.nintendo.znca; build:2400; iOS '.$this->iosVersion.') Alamofire/5.6.1',
            'X-Platform: iOS',
        ];

        $body = [
            'parameter' => [
                'language'   => $this->language,
                'naBirthday' => $this->naBirthday,
                'naCountry'  => $this->naCountry,
                'naIdToken'  => $this->naIdToken,
                'requestId'  => $this->requestId,
                'timestamp'  => $this->timestamp,
                'f'          => $this->f,
            ]
        ];

        $response = $this->sendRequest(self::ACCESS_TOKEN_URL, $header, $body);

        $this->setAccessToken($response["result"]["webApiServerCredential"]["accessToken"]);

        file_put_contents($this->accessTokenFilePath, $this->accessToken);
    }

    private function fetchUserInfo(){
        $header = [
            'Content-Type: application/json',
            'Connection: keep-alive',
            'Accept: application/json',
            'Accept-Language: ja-JP;q=1.0',
            'User-Agent: Coral/'.$this->nsoAppVersion.' (com.nintendo.znca; build:2400; iOS '.$this->iosVersion.') NASDK/.'.$this->nsoAppVersion,
            'Authorization: Bearer '.$this->naAccessToken,
        ];

        $body = [];

        $response = $this->sendRequest(self::API_USER_MR_URL, $header, $body, false);

        $this->setLanguage($response['language']);
        $this->setNaBirthday($response['birthday']);
        $this->setNaCountry($response['country']);
    }

    private function fetchF()
    {
        $header = [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: '.self::USER_AGENT_FOR_F,
        ];

        $body = [
            'token'      => $this->naIdToken,
            'hashMethod' => self::HASH_METHOD_NUMBER,
        ];

        $response = $this->sendRequest(self::F_GEN_URL, $header, $body);

        $this->setF($response['f']);
        $this->setTimestamp($response['timestamp']);
        $this->setRequestId($response['request_id']);
    }

    private function setNsoAppVersion(string $value)
    {
        $this->nsoAppVersion = $value;
    }

    private function setNaIdToken(string $value)
    {
        $this->naIdToken = $value;
    }

    private function setSessionToken(string $value)
    {
        $this->sessionToken = $value;
    }

    private function setF(string $value)
    {
        $this->f = $value;
    }

    private function setTimestamp(string $value)
    {
        $this->timestamp = $value;
    }

    private function setRequestId(string $value)
    {
        $this->requestId = $value;
    }

    private function setNaAccessToken(string $value)
    {
        $this->naAccessToken = $value;
    }

    private function setLanguage(string $value){
        $this->language = $value;
    }

    private function setNaBirthday(string $value){
        $this->naBirthday = $value;
    }

    private function setNaCountry(string $value){
        $this->naCountry = $value;
    }

    private function setAccessToken(string $value)
    {
        $this->accessToken = $value;
    }

    private function sendRequest(string $url,array $header,array $body, bool $isPost=true):array
    {
        //TODO エラーハンドリング
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $url);
        if($isPost){
            curl_setopt($request, CURLOPT_POST, true);
        }
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FAILONERROR, true);
        if(!empty($header)){
            curl_setopt($request, CURLOPT_HTTPHEADER, $header);
        }
        if(!empty($body)){
            //json形式のためjson_encode
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($request);

        if ($response === false) {
            $msg = curl_error($request);
            throw new Exception($msg);
        }

        curl_close($request);

        return json_decode($response, true);
    }
}
