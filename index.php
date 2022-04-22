<?php

  class Enel {

    public $curl;
    public $username = 'xxxxxxxxxxxxxxx';
    public $password = 'xxxxxxxxxxxxxxx';
    public $userNumber = 'xxxxxxxxxxxxxxx';
    public $pod = 'xxxxxxxxxxxxxxx';


    public function getData() {

      // 1. Initialize cURL

      $this->curl = curl_init();
      curl_setopt($this->curl, CURLOPT_REFERER, 'https://www.google.com/');
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($this->curl, CURLOPT_ENCODING, '');
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($this->curl, CURLOPT_TIMEOUT, 60);
      curl_setopt($this->curl, CURLOPT_COOKIEJAR, getcwd() . "/cookie.txt");
      curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36');


      // 2. Open Login page & Get 'sessionDataKey' hidden field value

      curl_setopt($this->curl, CURLOPT_URL, "https://www.enel.it/it/login");
      $html = curl_exec($this->curl);

      $sessionDataKey = $this->extractInputValue($html, 'sessionDataKey');


      // 3. Login on Enel endpoint & Get 'SAMLResponse' hidden field value

      $data = array(
        'username'        => $this->username,
        'password'        => $this->password,
        'sessionDataKey'  => $sessionDataKey,
        'tocommonauth'    => 'true',
      );

      curl_setopt($this->curl, CURLOPT_URL, "https://accounts.enel.com/samlsso");
      curl_setopt($this->curl, CURLOPT_POST, 1);
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
      $html = curl_exec($this->curl);

      $samlResponse = $this->extractInputValue($html, 'SAMLResponse');


      // 4. Post SAMLResponse to SAMLauth to finish login

      $data = array(
        'SAMLResponse' => $samlResponse
      );

      curl_setopt($this->curl, CURLOPT_URL, "https://www.enel.it/bin/samlauth");
      curl_setopt($this->curl, CURLOPT_POST, 1);
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
      $html = curl_exec($this->curl);


      // 4. Get POD Json data

      $data = array(
        'userNumber'    => $this->userNumber,
        'pod'           => $this->pod,
        'validityFrom'  => '18042022',
        'validityTo'    => '21042022'
      );

      $params = '?' . http_build_query($data);

      curl_setopt($this->curl, CURLOPT_URL, "https://www.enel.it/bin/areaclienti/auth/aggregateConsumption" . $params);
      curl_setopt($this->curl, CURLOPT_POST, false);
      curl_setopt($this->curl, CURLOPT_HTTPGET, true);
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

      $json = curl_exec($this->curl);

      $output = json_decode($json);

      if(json_last_error() !== JSON_ERROR_NONE)
        return false;

      return $output;

      // Close cURL
      curl_close($this->curl);
    }


    public function extractInputValue($html, $key) {

      libxml_use_internal_errors(true);
      $dom = new DOMDocument('1.0', 'utf-8');
      $dom->loadHTML('<?xml encoding="UTF-8">' . trim($html));
      $xpath = new DOMXpath($dom);
      $value = $xpath->query('//input[@name="' . $key . '"]')->item(0)->getAttribute('value');
      libxml_clear_errors();
      return $value;
    }
  }

  $enel = new Enel();
  $data = $enel->getData();
	
	print_r($data);
