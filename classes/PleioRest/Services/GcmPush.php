<?php
namespace PleioRest\Services;

class GcmPush implements PushInterface {
    public function __construct($key) {
        $this->key = $key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: key=' . $key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->ch = $ch;
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function push($subscription, $message) {
        $fields = array(
            'to' => array($subscription->token),
            'data' => $message
        );

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($this->ch);
    }
}