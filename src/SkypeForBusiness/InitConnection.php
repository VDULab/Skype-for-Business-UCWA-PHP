<?php

namespace SkypeForBusiness;

use SkypeForBusiness\Base;

/**
 * Initialize the connection.
 */
class InitConnection extends Base {

  /**
   * Constructor.
   */
  public function __construct($fqdn, array $config = []) {

    // To start with, let's pass any configuration values into the base class.
    parent::__construct($config);

    // FQDN.
    if (!empty($fqdn)) {
      $link = parse_url($fqdn);
      self::$ucwa_fqdn = $link["scheme"] . "://" . $link["host"];
    }

    // Do AutoDiscover.
    if (self::autodiscover()) {
      // Get OAuth URL.
      if (!self::getOauthLink()) {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /*
   *  Main methods
   */

  /**
   * Autodiscover links.
   *
   * Discovers the required URL's automatically.
   */
  private static function autodiscover() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_HEADER => FALSE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_URL => self::$ucwa_autodiscover,
      CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        // "X-Ms-Origin: " . self::$ucwa_fqdn,
      ),
      CURLOPT_TIMEOUT => 15,
    ));

    $response = curl_exec($curl);
    $status = curl_getinfo($curl);
    curl_close($curl);

    if ($status["http_code"] == 200) {
      $data = json_decode($response, TRUE);
      $link_usr = parse_url($data["_links"]["user"]["href"]);
      $link_frm = parse_url($data["_links"]["xframe"]["href"]);

      self::$ucwa_baseserver = $link_usr["scheme"] . "://" . (substr($link_usr["host"], -1) == "/" ? substr($link_usr["host"], 0, -1) : $link_usr["host"]);
      self::$ucwa_path_user = (substr($link_usr["path"], 0, 1) == "/" ? "" : "/") . $link_usr["path"] . "?" . $link_usr["query"];
      self::$ucwa_path_xframe = (substr($link_frm["path"], 0, 1) == "/" ? "" : "/") . $link_frm["path"];

      return TRUE;
    }
    else {
      self::_error("Can't automatically detect user url. Autodiscover failed.", $status);
    }
  }

  /**
   * Function getOauthLink().
   *
   * Get the OAuth link from the (unauthorized) "user" site.
   *
   * @return bool
   *   Operation succeeded..
   */
  private static function getOauthLink() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_HEADER => TRUE,
      CURLOPT_NOBODY => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_user,
      CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
      CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        // "X-Ms-Origin: " . self::$ucwa_fqdn,
      ),
      CURLOPT_TIMEOUT => 15,
    ));

    $response = curl_exec($curl);
    $status = curl_getinfo($curl);
    curl_close($curl);

    if ($status["http_code"] == 401) {
      preg_match('/href=["\']?([^"\'>]+)["\']?/', $response, $match);
      $link = parse_url($match[1]);

      self::$ucwa_path_oauth = (substr($link["path"], 0, 1) == "/" ? "" : "/") . $link["path"];

      return TRUE;
    }
    else {
      self::_error("Can't get OAuth-Link.", $status);
    }
  }

  /**
   * Function getApplicationLink().
   *
   * Get the application link and check,
   * if the Skype "Pool" for the authorized
   * user is the same as the autodiscover
   * pool.
   *
   * @return bool
   *   Operation succeeded.
   */
  private static function getApplicationLink() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_HEADER => FALSE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_user,
      CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
      CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        "Authorization: Bearer " . self::$ucwa_accesstoken,
        "X-Ms-Origin: " . self::$ucwa_fqdn,
      ),
      CURLOPT_TIMEOUT => 15,
    ));

    $response = curl_exec($curl);
    $status = curl_getinfo($curl);
    curl_close($curl);

    if ($status["http_code"] == 200) {
      $data = json_decode($response, TRUE);
      $link = parse_url($data["_links"]["applications"]["href"]);

      self::$ucwa_path_application = $link["path"] . (isset($link["query"]) ? '?' . $link["query"] : '');

      // Check if Hostname is the same.
      if (self::$ucwa_baseserver != $link["scheme"] . "://" . (substr($link["host"], -1) == "/" ? substr($link["host"], 0, -1) : $link["host"])) {
        // Hostname different!
        // New access token.
        self::$ucwa_baseserver = $link["scheme"] . "://" . (substr($link["host"], -1) == "/" ? substr($link["host"], 0, -1) : $link["host"]);

        if (self::getAccessToken(self::$ucwa_user, self::$ucwa_pass)) {
          return TRUE;
        }
        else {
          self::_error("Hostname changed (application resource) => requested new access token => failed.", array());
          return FALSE;
        }
      }
      else {
        return TRUE;
      }
    }
    else {
      self::_error("Can't get applications link for Skype UCWA", $status);
      return FALSE;
    }
  }

  /**
   * Function getAccessToken().
   *
   * Authorizes the sender and stores
   * the access token.
   *
   * @return bool
   *   Operation succeeded.
   */
  public static function getAccessToken($username, $password) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_HEADER => FALSE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_oauth,
      CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => array(
        "grant_type" => "password",
        "username" => $username,
        "password" => $password,
      ),
      CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        // "X-Ms-Origin: " . self::$ucwa_fqdn,
      ),
      CURLOPT_TIMEOUT => 15,
    ));

    $response = curl_exec($curl);
    $status = curl_getinfo($curl);
    curl_close($curl);

    if ($status["http_code"] == 200) {
      $data = json_decode($response, TRUE);
      self::$ucwa_accesstoken = $data["access_token"];
      self::$ucwa_user = $username;
      self::$ucwa_pass = $password;

      // Get application link.
      return self::getApplicationLink();
    }
    else {
      self::_error("Can't get an access token for Skype UCWA", $status);

      return FALSE;
    }
  }

}
