<?php

require('client.php');

class Line implements Notification{

    const END_POINT = 'https://notify-api.line.me/api/notify';

    private string $accessToken;

    private string $message;
    // private string $imageThumbnail;
    // private string $imageFullsize;
    // private string $imageFile;
    // private int $stickerPackageId;
    // private int $stickerId;
    private bool $notificationDisabled = false;

    public function __construct()
    {
        $this->setAccessToken(getenv('line_access_token'));
    }

    private function setAccessToken(string $value)
    {
        $this->accessToken = $value;
    }

    public function setParameter(array $parameter)
    {
        $this->setMessage($parameter['message']);
    }

    private function setMessage(string $value)
    {
        $this->message = $value;
    }

    public function send()
    {
        $header = [
            'Content-type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->accessToken,
        ];

        $body = [
            'message'              => $this->message,
            // 'imageThumbnail'       => $this->imageThumbnail,
            // 'imageFullsize'        => $this->imageFullsize,
            // 'imageFile'            => $this->imageFile,
            // 'stickerPackageId'     => $this->stickerPackageId,
            // 'stickerId'            => $this->stickerId,
            'notificationDisabled' => $this->notificationDisabled,
        ];

        $this->sendPostRequest(self::END_POINT, $header, $body);
    }

    //TODO　共通化
    private function sendPostRequest(string $url,array $header,array $body):array
    {
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FAILONERROR, true);

        curl_setopt($request, CURLINFO_HEADER_OUT, 2);

        if(!empty($header)){
            curl_setopt($request, CURLOPT_HTTPHEADER, $header);
        }
        if(!empty($body)){
            // form形式のためhttp_build_query
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($body));
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