<?php

namespace SkypeForBusiness;

/**
 * Base class for config and common methods.
 */
class Base {
  /*
   * Variables
   */

  /*
   * Server configuration
   */
  protected static $ucwa_autodiscover = "http://lyncdiscover.example.com";
  protected static $ucwa_fqdn = "";
  protected static $ucwa_baseserver = "";
  protected static $ucwa_path_oauth = "";
  protected static $ucwa_path_user = "";
  protected static $ucwa_path_xframe = "";
  protected static $ucwa_path_application = "";
  protected static $ucwa_path_application_fq = "";
  protected static $ucwa_path_conversation = "";
  protected static $ucwa_path_meetings = "";
  protected static $ucwa_path_events = "";
  protected static $ucwa_grant_type = "password";
  protected static $ucwa_use_ms_origin = TRUE;

  /*
   *  Storage
   */
  protected static $ucwa_accesstoken = "";
  protected static $ucwa_operationid = "";
  protected static $ucwa_user = "";
  protected static $ucwa_pass = "";

  /*
   *  Base cURL config.
   */
  protected static $curl_base_config = array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYPEER => FALSE,
    CURLOPT_SSL_VERIFYHOST => FALSE,
  );

  public function __construct(Array $config = []) {
    // Override the static properties defining the class config.
    foreach ($config as $key => $setting) {
      if (isset(self::$$key)) {
        self::$$key = $setting;
      }
      if ($key == 'proxy') {
        self::$curl_base_config = self::$curl_base_config + array(CURLOPT_PROXY => $setting);
      }
    }
  }

  /*
   *  Helper methods
   */

  /**
   * Function _error()
   *
   * Logging feature.
   */
  protected static function _error($text, $debug) {
    $file = fopen('ucwa.log', 'a');
    fwrite($file, date("d-m-Y H:i:s") . ' | ' . $text . ' | ' . var_export($debug, TRUE) . "\r\n");
    fclose($file);
  }

  /**
   * Function_generateUUID().
   *
   * Generates an unique ID.
   *
   * @return string
   *   The generated UUID.
   */
  protected static function _generateUUID() {
    return str_replace(".", "", uniqid(md5(time()), TRUE));
  }

  /*
   *  (array) getUCWAData
   *  ######################################
   *
   *  Returns important information for
   *  connecting UCWA_init with UCWA_use,
   *  if they aren't running on the same
   *  instance.
   */
  public static function getUCWAData() {
    return array(
      "accesstoken" => self::$ucwa_accesstoken,
      "baseserver" => self::$ucwa_baseserver,
      "path_application" => self::$ucwa_path_application,
      "path_xframe" => self::$ucwa_path_xframe,
    );
  }

  protected static function getDefaultHeaders(array $additional_headers = array()) {
    $headers = array(
      "Accept: application/json"
    );
    if (self::$ucwa_use_ms_origin) {
      $headers[] = "X-Ms-Origin: " . self::$ucwa_fqdn;
    }
    return array_merge($headers, $additional_headers);
  }
}
