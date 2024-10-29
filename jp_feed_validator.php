<?php

/*
    Feed Validator PHP class (feed_validator.class.php) is intended for feed validation
    It is a proxy to http://feedvalidator.org/
    Greetings to http://feedvalidator.org/

    License:
        Free for commercial or non-commercial use.
        All kind of modifications,improvements is granted but my author details must be
        intact AND all patches made by you to the original code must be marked.

        provided "AS IS" without any warranties.

         Svetoslav Marinov
         svetoslavm#gmail.com
         http://feed-validator.devquickref.com
*/

/**
 *    Feed Validator PHP class (feed_validator.class.php) is intended for feed validation
 *    It is a proxy to http://feedvalidator.org/
 *
 * @author Svetoslav Marinov <svetoslavm#gmail.com>
 * @link http://feed-validator.devquickref.com
 */
class jp_feed_validator {
    /**
     * class constructor
     * @param void
     * @return void
     * @return dynphp
     */
    function jp_feed_validator() {
        $this->validator_url = "http://feedvalidator.org/check.cgi?url=";

    }

    /**
     * validate a feed
     *
     * @param string $feed
     * @return bool
    */
    function validate($feed) {
        if (empty($feed))
            return false;

        if (!preg_match("@^http://@si", $feed))
            $feed = "http://" . $feed;

        // submitting to feedvalidator.org and checking the result
        if ($this->get($feed) 
            && !empty($this->buffer)
            && !preg_match("@sorry@si", $this->buffer)
            )
            return true;            

        return false;
    }

    /**
     * retrieve
     * @param  void
     * @return string
     */
    function get($feed) {
        $url = $this->validator_url . $feed;

        // try #1 fopen
        $fp = @fopen($url, "r");
        if (!empty($fp)) {
            $in = '';
          while (!feof($fp)) {
              $in .= fgets($fp, 4096);
          }

          @fclose($fp);
            $this->buffer = $in;
        }

        // try #2 fsockopen
        if (empty($fp))
        {
            $url_parsed = parse_url($url);
            $host = @$url_parsed["host"];
            $port = @$url_parsed["port"];
            if ($port == 0)
                $port = 80;

            $path = @$url_parsed["path"];
            //if url is http://example.com without final "/"
            //I was getting a 400 error
            if (empty($path))
                $path="/";

            if (!preg_match("@\?@si", $path))
                $path .= "?url=".rawurlencode($feed);

            //redirection if url is in wrong format
            if (empty($host))
                return false;

            $ua = "User-Agent: feed_validator/1.0 (feed_validator PHP class; +http://feed-validator.devquickref.com)\r\n";

            $out = "GET $path HTTP/1.0\r\n{$ua}Host: $host\r\n\r\n";
                $fp = @fsockopen($host, $port, $errno, $errstr, 30);
                fwrite($fp, $out);
                $body = false;

            $in = '';
            while (!feof($fp)) 
                $in .= fgets($fp, 4096);

            $in = split("\r\n\r\n", $in, 2);

            fclose($fp);
            $this->buffer = $in[1];
            //print $this->buffer;

        }

        # try #3 cURL
        // http://fr.php.net/manual/en/function.fopen.php
        if (empty($fp) && function_exists("curl_init") && extension_loaded('curl'))
        {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "feed_validator/1.0 (feed_validator PHP class; +http://feed-validator.devquickref.com)");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

            $string = curl_exec($ch);
            curl_close($ch);

            if (strlen($string))
                $this->buffer = $string;

        }

        return true;

    }

}
