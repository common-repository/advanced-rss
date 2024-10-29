<?php

/**
 * Advanced RSS Insufficient Requirments Class
 *
 * Informs the user that their server's configuration doesn't meet the plugin's
 * minimum requirments.
 *
 * @category plugin
 * @package advancedrss
 * @author Stephen Ingram <code@jixor.com>
 * @copyright Copyright (c) 2009, Stephen Ingram
 */
class jp_advancedrss
{

    /**
     * Constructor adds action hook.
     */
    function jp_advancedrss()
    {

        add_action('after_plugin_row', array($this, 'action_after_plugin_row'), 10, 4);

    }



    /**
     * Singleton Getter & Initializer
     *
     * @return jp_advancedrss
     */
    function &get_instance()
    {

        /**
         * @var jp_advancedrss
         */
        static $instance;

        if ($instance === null)
            $instance = new jp_advancedrss();

        return $instance;

    }



    /**
     * Activate Action
     *
     * @return void
     */
    function action_activate()
    {
    }



    /**
     * After the plugin's row in the plugins table display an error message.
     *
     * @return void Echoes output
     */
    function action_after_plugin_row($plugin_file, $plugin_data, $context)
    {

        if ($plugin_file == JP_ADVANCEDRSS)
            echo '<tr><td colspan="5" class="error">
                <p><strong>Critical</strong></p>'
                . $this->get_error()
                . '<p>Please contact your server\'s administrator with this
                error message for more information.</p>
                </td></tr>';

        return;

    }



    /**
     * Get friendly error messages telling the user why they can't use the
     * plugin.
     *
     * @return string HTML error message.
     */
    function get_error()
    {

        if (version_compare(PHP_VERSION, '5.2.0') < 0)
            return '<p>This plugin will only work on a server with a PHP version
                of <strong>at least 5.2.0</strong>.  Your server is running PHP
                version ' . phpversion() . '</p>';

        $errors = '';

        /**
         * Check for fopen_wrappers
         */
        if (!ini_get('allow_url_fopen'))
            $errors .= '<p>Your server\'s PHP enviroment must support
                <strong>fopen_wrappers</strong> to use this plugin.</p>';

        /**
         * Check for DOM library
         */
        if (!class_exists('DOMDocument'))
            $errors .= '<p>Your server\'s PHP enviroment must have the
                <strong>DOM extention</strong> installed.</p>';

        /**
         * Check for XSL library
         */
        if (!class_exists('XSLTProcessor'))
            $errors .= '<p>Your server\'s PHP enviroment must have the
                <strong>XSL extention</strong> installed.</p>';

        return $errors;

    }

}
