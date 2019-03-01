<?php namespace PostnummerSok;

/**
*  PostnummerSök Client
*  @author info@postnummersok.se
*/

class Client {

  const VERSION  = '1.0.0';

  private $_customerId;
  private $_apiKey;
  private $_timeout = 20;
  private $_lastRequest;
  private $_lastResponse;
  private $_lastLog;

  public function setCustomerId(string $id) {
    $this->_customerId = $id;
    return $this;
  }

  public function setApiKey(string $key) {
    $this->_apiKey = $key;
    return $this;
  }

  public function setTimeout(int $seconds) {
    $this->_timeout = $seconds;
    return $this;
  }

  public function getLastLog() {
    return $this->_lastLog;
  }

  public function Lookup(array $request) {

    $result = $this->_call('POST', 'https://postnummersok.se/api/lookup', $request);

    return $result;
  }

  public function Closest(array $request) {

    $result = $this->_call('POST', 'https://postnummersok.se/api/closest', $request);

    return $result;
  }

  public function Distance(array $request) {

    $result = $this->_call('POST', 'https://postnummersok.se/api/distance', $request);

    return $result;
  }

  public function Radius(array $request) {

    $result = $this->_call('POST', 'https://postnummersok.se/api/radius', $request);

    return $result;
  }
  
  public function Status(array $request) {

    $result = $this->_call('POST', 'https://postnummersok.se/api/status');

    return $result;
  }

  private function _call(string $method, string $url, array $request = array()) {

    $this->_lastRequest = [];
    $this->_lastResponse = [];
    $this->_lastLog = '';

  // Set customer
    $request['customer_id'] = $this->_customerId;

  // Calculate checksum
    $flattened = '';
    foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($request)) as $value) {
      $flattened .= $value;
    }

    $request['checksum'] = sha1($flattened . $this->_apiKey);

  // JSON Serialize Data
    $request = json_encode($request, JSON_PRETTY_PRINT);

  // Set HTTP headers
    $headers = [
      'User-Agent' => 'PostnummerSok-PHP-Client/'.self::VERSION,
    ];

    if (empty($headers['Content-Type']) && !empty($request)) {
      $headers['Content-Type'] = 'application/json';
    }

    if (!empty($request) && empty($headers['Content-Length'])) {
      $headers['Content-Length'] = strlen($request);
    }

    if (empty($headers['Connection'])) {
      $headers['Connection'] = 'Close';
    }

    $parts = parse_url($url);

    if (empty($parts['port'])) {
      $parts['port'] = (!empty($parts['scheme']) && $parts['scheme'] == 'https') ? 443 : 80;
    }

    switch(@$parts['scheme']) {
      case 'https': $parts['scheme'] = 'ssl'; break;
      default: $parts['scheme'] = 'tcp'; break;
    }

  // Set HTTP body
    $out = $method ." ". $parts['path'] . ((isset($parts['query'])) ? '?' . $parts['query'] : '') ." HTTP/1.1\r\n" .
         "Host: ". $parts['host'] ."\r\n";

    foreach ($headers as $key => $value) {
      $out .= "$key: $value\r\n";
    }

  // Perform request
    $bodyFound = false;
    $responseHeaders = '';
    $responseBody = '';
    $microtimeStart = microtime(true);

    $this->_lastRequest = [
      'timestamp' => time(),
      'head' => $out,
      'body' => $request,
    ];

    if (!$socket = stream_socket_client(strtr('scheme://host:port', $parts), $errno, $errstr, $this->_timeout)) {
      throw new \Exception('Error calling URL ('. $url .'): '. $errstr);
    }

    stream_set_timeout($socket, $this->_timeout);

    fwrite($socket, $out . "\r\n");
    fwrite($socket, $request);

    while (!feof($socket)) {
      if ((microtime(true) - $microtimeStart) > $this->_timeout) {
       throw new \Exception('Timeout during retrieval');
       return false;
      }

      $line = fgets($socket);

      if ($line == "\r\n") {
       $bodyFound = true;
       continue;
      }

      if ($bodyFound) {
       $responseBody .= $line;
       continue;
      }

      $responseHeaders .= $line;
    }

    fclose($socket);

  // Decode chunked data
    if (preg_match('#Transfer-Encoding:\s?Chunked#i', $responseHeaders)) {
      $responseBody = $this->_decode_chunked_data($responseBody);
    }

  // Validate response
    preg_match('#HTTP/\d(\.\d)?\s(\d{3})#', $responseHeaders, $matches);
    $status_code = $matches[2];

    $this->_lastResponse = [
      'timestamp' => time(),
      'status_code' => $status_code,
      'head' => $responseHeaders,
      'duration' => round(microtime(true) - $microtimeStart, 3),
      'bytes' => strlen($responseHeaders . "\r\n" . $responseBody),
      'body' => $responseBody,
    ];

    $this->_lastLog = (
      "## [". date('Y-m-d H:i:s', $this->_lastRequest['timestamp']) ."] Request ##############################\r\n\r\n" .
      $this->_lastRequest['head']."\r\n" .
      $this->_lastRequest['body']."\r\n\r\n" .
      "## [". date('Y-m-d H:i:s', $this->_lastResponse['timestamp']) ."] Response — ". (float)$this->_lastResponse['bytes'] ." bytes transferred in ". (float)$this->_lastResponse['duration'] ." s ##############################\r\n\r\n" .
      $this->_lastResponse['head']."\r\n" .
      $this->_lastResponse['body']."\r\n\r\n"
    );

    if (empty($responseBody)) {
      throw new \Exception('No response from remote machine');
    }

  // Parse result
    if (!$json = @json_decode($responseBody, true)) {
      throw new \Exception('Invalid response from remote machine');
    }

    if (empty($json['status'])) {
      throw new \Exception('Invalid result status');
    }

    if (!empty($json['error'])) {
      throw new \Exception($json['error']);
    }

    if ($json['status'] == 'error') {
      throw new \Exception('The result returned an error');
    }

    return $json;
  }

  private function _decode_chunked_data($in) {

    $out = '';

    while($in != '') {
      $lf_pos = strpos($in, "\012");
      if($lf_pos === false) {
        $out .= $in;
        break;
      }
      $chunk_hex = trim(substr($in, 0, $lf_pos));
      $sc_pos = strpos($chunk_hex, ';');
      if($sc_pos !== false)
        $chunk_hex = substr($chunk_hex, 0, $sc_pos);
      if($chunk_hex == '') {
        $out .= substr($in, 0, $lf_pos);
        $in = substr($in, $lf_pos + 1);
        continue;
      }
      $chunk_len = hexdec($chunk_hex);
      if($chunk_len) {
        $out .= substr($in, $lf_pos + 1, $chunk_len);
        $in = substr($in, $lf_pos + 2 + $chunk_len);
      } else {
        $in = '';
      }
    }

    return $out;
  }
}
