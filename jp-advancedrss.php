<?php

/**
 * Plugin Name: Advanced RSS
 * Plugin URI: http://jp.jixor.com/plugins/advanced-rss
 * Description: Feed aggregator widget which is able to reformat your RSS and ATOM feeds into any HTML layout you want. Like the built in widget feeds are cached locally to speed up load times, and title, link and number of items to display may be modified. Feeds are formatted via XSLT, but if you don't know how to write XSLT there is a variety of built in templates for you use and learn from.
 * Version: 2.7.3
 * Author: Stephen Ingram
 * Author URI: http://blog.jixor.com
 *
 * Feed agregator widget which uses XSL transfomations to format RSS/ATOM feeds.
 *
 * @author Stephen Ingram <code@jixor.com>
 * @copyright Copyright (c) 2009, Stephen Ingram
 * @category plugin
 * @package advancedrss
 * @todo Use socket connect to send proper headers, if-modified-since so that
 *       feed cache is only updated if the feed has changed since it was last
 *       modified. Of course not all servers will send proper headers and respnd
 *       properly, however this is still really a must! Note that it isnt
 *       currently implemented as the PHP extentions required can be flakey.
 * @todo Or new WP_Http class? get it via _wp_http_get_object()
 * @todo Add more templates
 * @todo Ability to include on a page/post
 * @todo More widget options to make editing generally unecessary.
 * @todo Don't load the widget if environment doesnt support it.
 * @todo As this only supports PHP 5 it could use registershutdown function to
 *       save options changes.
 * @todo uninstall.php
 *       http://codex.wordpress.org/Migrating_Plugins_and_Themes_to_2.7#Uninstall_Plugin_API
 * @todo XSL authoring similar to the HTML tab on post/page authoring
 * @todo XSL help, explain the tags, and their usage
 * @todo Template tested against a real xml feed cant be done, but it would be
 *       nice to check if $var, php:stringFunction() and test/select XPath
 *       queries appear to be valid. Currently the XML and XSL structure are
 *       tested only.
 * @todo Use W3 Feed Validator, SOAP mode: http://validator.w3.org/feed/docs/soap.html
 * @todo For conveniance a list of all configured widgets linked to a config
 *       page for the widget.
 */



/**
 * Advanced RSS plugin basename
 */
define('JP_ADVANCEDRSS', plugin_basename(__FILE__));



/**
 * Only include the actual plugin class if the server meets the minimum
 * requirements.
 */
if (!class_exists('jp_advancedrss'))
    if (version_compare(PHP_VERSION, '5.2.0') < 0
        || !ini_get('allow_url_fopen')
        || !class_exists('DOMDocument')
        || !class_exists('XSLTProcessor')
        )
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'php4.php');
    else
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'php5.php');



if (!function_exists('jp_xslt_date'))
{

    /**
     * Format Date
     *
     * Format the RFC 2822 date as found on rss feeds, or many other date
     * formats.
     *
     * @param string RFC 2822 or other date, from Jan 1 1970 till Jan 19 2038
     *               03:14:07 or from 13 Dec 1901 20:45:54 on non Windows
     *               systems.
     * @param string The format of the outputted date string. See the formatting
     *               options for PHP's date() function. Defaults to Y/m/d.
     * @return string
     */
    function jp_xslt_date($date, $format = 'Y/m/d')
    {

        // This is to fix strange google dates in UT time, I'm assuming it
        // should be UTC.
        $date = trim($date);

        if (substr($date, -2, 2) == 'UT')
                $date .= 'C';

        if (!$time = strtotime($date))
            return 'Unknown Date Format';

        return date($format, $time);

    }

}



if (!function_exists('jp_xslt_snip'))
{

    /**
     * String Snippit
     *
     * Take a snip from the start of a field.
     *
     * This can also be done with regular expressions in XSL.
     *
     * @param  string $string  HTML or plain text.
     * @param  string $max_len If the input string is longer than this it will
     *                         be shortened to this maximum length.
     * @param  string $marker  If the string is shortened this will be inserted.
     *                         Defaults to an hellipsis "..." but you might use
     *                         "[snip]", "[continue reading]", etc.
     * @return string
     */
    function jp_xslt_snip($string, $max_len = 50, $marker = "&hellip;")
    {

        $string = strip_tags($string);

        if (strlen($string) > $lax_len)
            $string = substr($string, 0, $max_len) . $marker;

        return $string;

    }

}



if (!function_exists('jp_xslt_replace'))
{

    /**
     * Replace
     *
     * Perform a regular expression search and replace
     *
     * Extended php's preg_replace by running the subject through trim() and
     * optionally through strip_tags()
     *
     * @see preg_replace()
     * @see trim()
     * @see strip_tags()
     * @param  string $regex      Pattern
     * @param  string $replace    Replacement
     * @param  string $subject    Subject
     * @param  bool   $strip_tags Also apply strip_tags()? Defaults to FALSE.
     * @return string
     */
    function jp_xslt_replace($regex, $replace, $subject, $strip_tags = false)
    {

        if ($strip_tags)
            $subject = strip_tags($subject);

        return preg_replace($regex, $replace, trim($subject));

        $string = strip_tags($string);

        if (strlen($string) > $lax_len)
            $string = substr($string, 0, $max_len) . $marker;

        return $string;

    }

}



/**
 * Activate Action
 *
 * @return void
 */
function jp_advancedrss_action_activate()
{

    jp_advancedrss::get_instance()->action_activate();

}



/**
 * Initialize the class to $jp_advancedrss
 */
$jp_advancedrss = jp_advancedrss::get_instance();

/**
 * Register activation hook
 */
register_activation_hook(__FILE__, 'jp_advancedrss_action_activate');
