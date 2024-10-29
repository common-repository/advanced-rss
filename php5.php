<?php

/**
 * Advanced RSS For PHP 5.2.0+
 *
 * RSS feed agregator widget which uses xsl transfomations to format a feed.
 *
 * @category plugin
 * @package advancedrss
 * @author Stephen Ingram <code@jixor.com>
 * @copyright Copyright (c) 2009, Stephen Ingram
 */
class jp_advancedrss
{

    private static $instance;

    /**
     * Referance to wpdb class
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var array
     */
    private $built_in_templates;

    /**
     * @var array
     */
    private $built_in_functions;

    /**
     * @var string
     */
    private $table_templates;

    /**
     * @var string
     */
    private $table_cache;

    /**
     * Holds options array
     *
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $plugin_folder;

    /**
     * @var string
     */
    private $plugin_basename;

    private $validator;



    /**
     * Constructor
     *
     * Initialize members, load options and register hooks.
     */
    private function __construct()
    {

        global $wpdb;

        $this->wpdb =& $wpdb;

        $this->built_in_templates = array(
            'Blog',
            'Default',
            'Del.icio.us',
            'Flickr Thumbnails',
            'DeviantArt',
            'Google Groups',
            'Twitter'
            );

        $this->built_in_functions = array(
            'jp_xslt_date',
            'jp_xslt_snip',
            'jp_xslt_replace',
            'jp_xslt_twitter_user',
            'jp_xslt_twitter_strip',
            'jp_xslt_twitter_format',
            'preg_replace',
            'strip_tags',
            'htmlentities',
            'htmlspecialchars',
            'strlen',
            'substr'
            );

        $this->table_templates = "{$wpdb->prefix}jp_advancedrss_templates";
        $this->table_cache     = "{$wpdb->prefix}jp_advancedrss_cache";

        $this->plugin_folder   = dirname(__FILE__);

        $this->plugin_basename = JP_ADVANCEDRSS;

        $this->get_options();

        add_filter("plugin_action_links_{$this->plugin_basename}", array($this, 'filter_plugin_action')); 

        add_action('widgets_init', array($this, 'action_widgets_init'));
        add_action('admin_menu', array($this, 'action_admin_menu'));
        add_action('switch_theme', array($this, 'action_switch_theme'));

    }



    /**
     * Singleton Getter & Initializer
     *
     * @return jp_advancedrss
     */
    public function get_instance()
    {

        if (!self::$instance)
            self::$instance = new jp_advancedrss();

        return self::$instance;

    }



    /**
     * Sets self::$options while configuring with defaults if required.
     *
     * @return void
     */
    public function get_options()
    {

        $this->options = get_option('jp_advancedrss');

        $update = false;



        /**
         * Ensure options is an array.
         */
        if (!is_array($this->options))
        {
            $this->options = array();
            $update = true;
        }



        /**
         * Default behaviour to create an empty array of widgets
         */
        if (!isset($this->options['widgets'])
            || !is_array($this->options['widgets'])
            )
        {

            $this->options['widgets'] = array();
            $update = true;

        }



        if (!isset($this->options['required_capability'])
            || empty($this->options['required_capability'])
            || !is_string($this->options['required_capability'])
            )
        {

            $this->options['required_capability'] = 'switch_themes';
            $update = true;

        }



        if (!isset($this->options['phpfunctions'])
            || empty($this->options['phpfunctions'])
            || !is_array($this->options['phpfunctions'])
            )
        {

            $this->options['phpfunctions'] = $this->built_in_functions;
            $update = true;

        }



        if ($update)
            update_option('jp_advancedrss', $this->options);

        return;

    }



    /**
     * Clear Feed Cache
     *
     * Truncates the cache database table.
     *
     * @return void
     */
    private function clear_cache()
    {

        return $this->wpdb->query("TRUNCATE TABLE {$this->table_cache}");

    }



    /**
     * Plugin Action Filter
     *
     * Add Options and Template page links to the manage plugins interface.
     *
     * @return void
     */
    public function filter_plugin_action($links)
    {

        array_unshift(
            $links,
            '<a href="options-general.php?page=jp-advancedrss.php">' . __('Options') . '</a>'
            );

        array_unshift(
            $links,
            '<a href="themes.php?page=jp-advancedrss.php">' . __('Templates') . '</a>'
            );

        return $links;

    }



    /**
     * Widgets Initialization Action
     *
     * @return void
     */
    public function action_widgets_init()
    {

        if (!function_exists('wp_register_sidebar_widget'))
            return;

        $widget_ops = array(
            'classname' => 'widget-jp-advancedrss',
            'description' => __('Entries from any RSS or Atom feed')
            );

        $control_ops = array(
            'width' => 400,
            'height' => 400,
            'id_base' => 'jp-advancedrss'
            );

        $registered = false;

        if (isset($this->options['widgets'])
            && is_array($this->options['widgets'])
            )
            foreach (array_keys($this->options['widgets']) as $o)
            {

                /**
                 * Create each instance with its own id in the format
                 * "{$control_ops[id_base]}-{$o}".
                 */
                $id = $control_ops['id_base'] . "-$o";
                //$registered = true; // -- Confising, really the item should always be available to add
                wp_register_sidebar_widget(
                    $id,
                    'Advanced RSS',
                    array($this, 'widget_widget'),
                    $widget_ops,
                    array('number' => $o)
                    );

                wp_register_widget_control(
                    $id,
                    'Advanced RSS',
                    array($this, 'widget_control'),
                    $control_ops,
                    array('number' => $o)
                    );

            }



        /**
         * If there are none, we register the widget's existance with a generic
         * template.
         */
        if (!$registered)
        {
            wp_register_sidebar_widget(
                $control_ops['id_base'] . '-1',
                'Advanced RSS',
                array($this, 'widget_widget'),
                $widget_ops,
                array('number' => -1)
                );
            wp_register_widget_control(
                $control_ops['id_base'] . '-1',
                'Advanced RSS',
                array($this, 'widget_control'),
                $control_ops,
                array('number' => -1)
                );
        }

    }



    /**
     * Admin Menu Action
     *
     * @return void
     */
    public function action_admin_menu()
    {

        add_options_page(
            'Advanced RSS &rsaquo; ' . __('Options'),
            'Advanced RSS',
            $this->options['required_capability'],
            'jp-advancedrss.php',
            array($this, 'page_options')
            );

        add_theme_page(
            'Advanced RSS &rsaquo; ' . __('Options'),
            'Advanced RSS',
            $this->options['required_capability'],
            'jp-advancedrss.php',
            array($this, 'page_theme')
            );

    }



    /**
     * Switch Theme Hook
     *
     * Clear the cache on theme switch as the widget caches template elements.
     *
     * @return void
     */
    public function action_switch_theme()
    {

        $this->clear_cache();

        return;

    }



    /**
     * Plugin Activation Action
     *
     * @return void
     */
    public function action_activate()
    {

        // I have no idea how this is possible as get_options absolutley has to
        // be called first and that forces this to be an array. However someone
        // has had this error.
        if (!is_array($this->options['phpfunctions']))
            $this->options['phpfunctions'] = array();

        foreach($this->built_in_functions as $f)
            if (!in_array($f, $this->options['phpfunctions']))
                $this->options['phpfunctions'][] = $f;



        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_templates} (
                template_id smallint(1) unsigned NOT NULL auto_increment,
                built_in tinyint(1) unsigned NOT NULL default 0,
                title varchar(64) NOT NULL,
                description text default NULL,
                xslt longtext NOT NULL,
                PRIMARY KEY  (template_id),
                UNIQUE KEY title (title)
            )";

        dbDelta($sql);

        // import old options templates, will be removed by version 3.0
        if (isset($this->options['templates'])
            && is_array($this->options['templates'])
            )
        {

            foreach ($this->options['templates'] as $t)
            {

                $t['no_delete'] = ($t['no_delete'] ? 1 : 0);
                $t['title'] = "'" . $this->wpdb->escape($t['title']) . "'";
                $t['description'] = ($t['description']
                    ? "'" . $this->wpdb->escape($t['description']) . "'"
                    : 'NULL'
                    );
                $t['xsl'] = "'" . $this->wpdb->escape(str_replace('\$', '$', $t['xsl'])) . "'";

                $sql = $this->insert_template_sql($t['no_delete'], $t['title'], $t['description'], $t['xsl']);

                $this->wpdb->query($sql);

            }

            unset($this->options['templates']);

        }



        // Check that all the default templates are present.
        // If it is already present then reset it to the default
        foreach ($this->built_in_templates as $t)
            if ($id = $this->wpdb->get_var("SELECT template_id FROM {$this->table_templates} WHERE title = '$t'"))
            {

                // Template exists, replace it.
                $sql = "UPDATE {$this->table_templates}
                    SET xslt = " . $this->built_in_template_xslt($t) . ",
                        description = " . $this->built_in_template_description($t) . "
                    WHERE template_id = $id";
                $this->wpdb->query($sql);

            }
            else
            {

                // Template does not exist, create it.
                $sql = $this->insert_template_sql(
                    1,
                    "'$t'",
                    $this->built_in_template_description($t),
                    $this->built_in_template_xslt($t)
                    );

                $this->wpdb->query($sql);

            }



        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_cache} (
                widget_id int(1) unsigned NOT NULL,
                time int(1) unsigned NOT NULL,
                cache longtext NOT NULL,
                PRIMARY KEY  (widget_id),
                KEY time (widget_id,time)
            )";

        dbDelta($sql);

        if (isset($this->options['widgets'])
            && is_array($this->options['widgets'])
            && $count = count($this->options['widgets'])
            )
            foreach ($this->options['widgets'] as $k => $v)
            {

                unset($this->options['widgets'][$k]['cache']);
                unset($this->options['widgets'][$k]['cachetime']);

            }



        // Clear the cache
        $this->clear_cache();

        // Its safe to assume options have probabally changed, if not no harm done.
        update_option('jp_advancedrss', $this->options);

        return;

    }



    /**
     * Validate Feed
     *
     * @param  string $url Feed URL
     * @return false|array Error array containing modified url and error
     *                     message.
     */
    private function validatefeed($url, $bRunValidator = false)
    {
/*
        if (!function_exists('fetch_feed'))
            require_once(ABSPATH . WPINC . '/rss.php');
*/
        $rss = fetch_feed($url);
        $error = false;
        if (!is_object($rss))
        {

            return array(
                'url' => wp_specialchars(__('Error: could not find an RSS or ATOM feed at that URL.'), 1),
                'error' => sprintf(__('Error in RSS %1$d'), $widget_number)
                );

        }
        else if ($bRunValidator)
        {

            if (!$this->validator)
            {

                if (!class_exists('jp_feed_validator'))
                    require_once($this->plugin_folder . DIRECTORY_SEPARATOR . 'jp_feed_validator.php');

                $this->validator = new jp_feed_validator;

            }

            if (!$this->validator->validate($url))
                return array(
                    'url' => wp_specialchars(__('Feed is not valid RSS or ATOM', 'jp-advancedrss'), 1),
                    'error' => sprintf(__('Error in RSS %1$d'), $widget_number)
                    );

        }

        return false;

    }



    /**
     * Insert Template SQL
     *
     * Provided with escaped arguments will produce the sql query to insert a
     * new template.
     *
     * @return string
     */
    private function insert_template_sql($built_in, $title, $description, $xslt)
    {

        return "INSERT INTO {$this->table_templates}
            (built_in, title, description, xslt)
            VALUES
            (
                $built_in,
                $title,
                $description,
                $xslt
            )";

    }



    /**
     * Format Widget Error
     *
     * Used when a widget encounteres an error at run time.
     *
     * @return string
     */
    private function widget_error($message)
    {

        echo 'Advanced RSS: ' . $message;

    }



    /**
     * Generate A Widget
     *
     * Hit the specified feed URI, process the feed and generate the widget
     * body. Results may be cached according to individual feed settings.
     *
     * @param  array $args
     * @param  int   $widget_args Holds unique widget id
     * @return void  Output echoed out.
     */
    public function widget_widget($args, $widget_args = 1)
    {

        /**
         * Wordpress calls the widget with special beg/end of title tags to add
         * a title to the widget management page. In such a case do not cache
         * the output.
         */
        $donotcache = (preg_match('/%BEG_OF_TITLE%/', $args['before_title'])
            ? true
            : false
            );



        /**
         * name => sidebar name
         * id => sidebar id
         * before_widget
         * after_widget
         * before_title
         * after_title
         * widget_id
         * widget_name
         */
        extract($args);

        /**
         * number => unique widget id, i.e. "225194951"
         */
        extract($widget_args);

        $number = ($number
            ? (int)$number
            : 0
            );



        /**
         * $this->options['widgets'][$number]
         * url, title, link, items, maxage, show_title
         */


        if (!isset($this->options['widgets'][$number]))
            return $this->widget_error('Invalid widget.');

        if (isset($this->options['widgets'][$number]['error'])
            && $this->options['widgets'][$number]['error']
            )
            return $this->widget_error($this->options['widgets'][$number]['url']);

        $mode = 'basic';
        extract($this->options['widgets'][$number]);

        $maxage = time() - $maxage;

        $cache = $this->wpdb->get_var(
            "SELECT cache
                FROM {$this->table_cache}
                WHERE widget_id = $number
                    AND time > $maxage"
            );

        if (!$donotcache
            && $cache
            )
        {
            echo $cache;
            return;
        }



        /**
         * Spoof user agent, necessary for some feeds, like facebook, for some
         * strange reason only known to that site's developers.
         */
        $old = ini_get('user_agent');

        ini_set(
            'user_agent',
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1)'
            );

        /**
         * Get feed using file_get_content - requires fopen wrappers enabled.
         */
        if($url)
            $feed = @file_get_contents($url);

        /**
         * Return the user_agent setting
         */
        ini_set('user_agent', $old);

        if (!$feed)
            return $this->widget_error('<a href="' . $url . '">Feed</a> not found.');



        /**
         * Initialize and load the rss DOMDocument object.
         */
        $rss = new DOMDocument;
        $rss->loadXML($feed);

        if (!is_object($rss))
            return $this->widget_error('Feed is not valid RSS or ATOM.');



        /**
         * If title or link specified modify the feed
         */
        if ($title || $link)
            $xpath = new DOMXPath($rss);

        // why is this commented out?
        // this will allow errors on the queries below
        //if (!is_object($xpath))
            //return $this->widget_error('Invalid <a href="' . $url . '">feed</a>, or your server is not correctly configured.');

        // Test the validity of an rss feed now too?

        // Note that this only supports RSS feeds at the moment.
        if ($title)
        {
            // Edit the rss feed to insert our own title
            $nodes = $xpath->query('/rss/channel/title');
            if ($nodes && $nodes->length)
                $nodes->item(0)->nodeValue = $title;
        }

        if ($link)
        {
            // Edit the rss feed to insert our own link
            $nodes = $xpath->query('/rss/channel/link');
            if ($nodes && $nodes->length)
                $nodes->item(0)->nodeValue = $link;
        }



        $template_id = $template;

        if (!$template_id
            || !$template = $this->wpdb->get_var(
                "SELECT xslt
                    FROM {$this->table_templates}
                    WHERE template_id = $template_id"
                )
            )
            return $this->widget_error('The selected template does not exist.');



        /**
         * Initialize and load the xsl template DOMDocument object.
         */
        $xsl = new DOMDocument;
        $xsl->loadXML($template);

        /**
         * Initialize and configure the XSLTProcessor
         */
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);

        if ($this->options['phpfunctions'])
            $proc->registerPHPFunctions(
                $this->options['phpfunctions']
                );

        /**
         * Add parameters
         */
        $proc->setParameter(
            '',
            array(
                'url' => $url,
                'items' => $items,
                'rss_icon' => includes_url('images/rss.png'),
                'before_widget' => $before_widget,
                'before_title' => $before_title,
                'after_title' => $after_title,
                'after_widget' => $after_widget,
                'showtitle' => ($showtitle ? 'true' : ''),
                'link' => $link,
                'mode' => $mode
                )
            );

        /**
         * Apply the transformation
         */
        $out = $proc->transformToXML($rss);

        /**
         * Save to cache, unless do_not_cache is set to true.
         */
        if (!$donotcache)
        {

            $this->wpdb->query("DELETE FROM {$this->table_cache} WHERE widget_id = $number");

            $this->wpdb->query(
                "INSERT INTO {$this->table_cache}
                    (widget_id, time, cache)
                    VALUES
                    (
                        $number,
                        " . time() . ",
                        '" . $this->wpdb->escape($out) . "'
                    )"
                );

        }

        echo $out;

    }



    /**
     * Basically copied from wordpress text widget, this could be somewhat
     * better, however it does work.
     *
     * @return void Output echoed.
     */
    public function widget_control($widget_args)
    {

        global $wp_registered_widgets;

        $this->clear_cache();

        /**
         * Have the widgets been updated?
         */
        static $updated = false;

        /**
         * Get widget id number
         */
        if (is_numeric($widget_args))
            $widget_args = array('number' => $widget_args);
        $widget_args = wp_parse_args($widget_args, array('number' => -1));
        extract($widget_args, EXTR_SKIP);


/*
        $urls = array();
        if (!empty($this->options['widgets']))
            foreach ($this->options['widgets'] as $w)
                if (isset($w['url']))
                    $urls[$w['url']] = true;
*/


        // has update already run for a previous widget (all done at once)
        // data was posted
        // there are sidebars
        if (!$updated
            && 'POST' == $_SERVER['REQUEST_METHOD']
            && !empty($_POST['sidebar'])
            )
        {

            // What sidebar are we processing?
            $sidebar = (string)$_POST['sidebar'];

            /**
             * Set $this_sidebar, the current sidebar, an array of widgets. May
             * not contain any jp-advancedrss widgets.
             */
            $sidebars_widgets = wp_get_sidebars_widgets();
            if (isset($sidebars_widgets[$sidebar]))
                $this_sidebar =& $sidebars_widgets[$sidebar];
            else
                $this_sidebar = array();



            /**
             * Foreach widgets in the sidebar, if the widget is a jp-advancedrss
             * and it has a number set then see if such a widget was submitted.
             * If not it means the user must have clicked remove so unset it.
             * We dont need to worry about removing its cahce as the cache table
             * is truncated on every widget update.
             */
            foreach ($this_sidebar as $_widget_id)
                if ('jp-advancedrss' == $wp_registered_widgets[$_widget_id]['callback']
                    && isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
                    )
                {

                    $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                    if (!in_array("jp-advancedrss-$widget_number", $_POST['widget-id']))
                        unset($this->options['widgets'][$widget_number]);

                }

            /**
             * jp-advancedrss is an array of widget id => properties array pairs
             */
            foreach ((array)$_POST['jp-advancedrss'] as $widget_number => $widget_rss)
            {

                if (!isset($widget_rss['url'])
                    && isset($this->options['widgets'][$widget_number])
                    ) // user clicked cancel
                    continue;

                $widget_rss = stripslashes_deep($widget_rss);

                $url = $widget_rss['url'];
                $this->options['widgets'][$widget_number] = $this->widget_control_process($widget_rss);//, !isset($urls[$url]));

    		}

            update_option('jp_advancedrss', $this->options);

    		$updated = true;

        }

        $default_template = $this->wpdb->get_var("SELECT template_id FROM {$this->table_templates} WHERE title = 'Default'");

        /**
         * Define defaults or get settings if editing.
         */
        if ($number == -1) {
            $title      = '';
            $url        = '';
            $link       = '';
            $items      = 10;
            $template   = $default_template;
            $number     = '%i%';
            $maxage     = 21600;
            $showtitle  = true;
            $mode       = 'basic';
        } else {
            $title      = attribute_escape($this->options['widgets'][$number]['title']);
            $url        = attribute_escape($this->options['widgets'][$number]['url']);
            $link       = attribute_escape($this->options['widgets'][$number]['link']);
            $items      = attribute_escape($this->options['widgets'][$number]['items']);
            $template   = (int)attribute_escape($this->options['widgets'][$number]['template']);     
            $number     = attribute_escape($number);
            $maxage     = (int)attribute_escape($this->options['widgets'][$number]['maxage']);
            $showtitle  = attribute_escape($this->options['widgets'][$number]['showtitle']);
            $mode       = attribute_escape($this->options['widgets'][$number]['mode']);
        }

        /**
         * Build the form.
         */
        $out = '<p>
                <label for="jp-advancedrss-url-' . $number . '">'
                    . _('Enter the RSS feed URL here:') . '</label>
                <input class="widefat" id="jp-advancedrss-url-' . $number . '"
                    name="jp-advancedrss[' . $number . '][url]" type="text"
                    value="' . $url . '" />
            </p>
            <p>
                <label for="jp-advancedrss-title-' . $number . '">'
                    . _('Give the feed a title (optional):') . '</label>
                <input class="widefat" id="jp-advancedrss-title-' . $number . '"
                    name="jp-advancedrss[' . $number . '][title]" type="text"
                    value="' . $title . '" />
            </p>
            <p>
                <label for="jp-advancedrss-link-' . $number . '">'
                    . _('Link (optional)') . ':</label>
                <input class="widefat" id="jp-advancedrss-link-' . $number . '"
                    name="jp-advancedrss[' . $number . '][link]" type="text"
                    value="' . $link . '" />
            </p>
            <p>
                <label for="jp-advancedrss-maxage-' . $number . '">'
                    . _('Cache time (seconds)') . ':</label>
                <input id="jp-advancedrss-maxage-' . $number . '"
                    name="jp-advancedrss[' . $number . '][maxage]" type="text"
                    value="' . $maxage . '" />
            </p>
            <p>
                <label for="jp-advancedrss-items-' . $number . '">'
                    . _('How many items would you like to display?') . '</label>
                <select id="jp-advancedrss-items-' . $number . '"
                    name="jp-advancedrss[' . $number . '][items]">';

        for ($i = 1; $i <= 20; ++$i)
            $out .= '<option value="' . $i . '"' . ($items == $i
                ? ' selected="selected"'
                : ''
                )
                . ">$i</option>";

        $out .= '</select>
            </p>
            <p>
                <input type="checkbox"
                    id="jp-advancedrss-showtitle-' . $number . '"
                    name="jp-advancedrss[' . $number . '][showtitle]"
                    value="true"'
            . ($showtitle
                ? ' checked="checked"'
                : ''
                )
            . ' /><label for="jp-advancedrss-showtitle-' . $number . '">'
            . __('Display feed title?', 'jp-advancedrss')
            . '</label>
            </p>
            <p>
                <label for="jp-advancedrss-template-' . $number . '">'
                    . _('Template') . ':</label> <a href="themes.php?page=jp-advancedrss.php"
                    >[Edit Templates]</a>
                <select id="jp-advancedrss-template-' . $number . '" class="widefat"
                    name="jp-advancedrss[' . $number . '][template]">';

        $templates = $this->wpdb->get_results("SELECT template_id, title FROM {$this->table_templates}");

        foreach ($templates as $t)
            $out .= '<option value="' . $t->template_id . '"' . ($template == $t->template_id
                ? ' selected="selected"'
                : ''
                )
                . ">{$t->title}</option>";

        $out .= '</select>
            </p>';

        $modes = array('basic' => 'Basic', 'extended' => 'Extended', 'full' => 'Full');

        $out .= '<p>
                <label for="jp-advancedrss-mode-' . $number . '">' . _('Formatting') . '<br /><small>Check template description for effect</small>:</label>
                <select id="jp-advancedrss-mode-' . $number . '" class="widefat"
                    name="jp-advancedrss[' . $number . '][mode]">';

        foreach($modes as $k => $v)
            $out .= '<option value="' . $k . '"' . ($mode == $k
                ? ' selected="selected"'
                : ''
                )
                . ">$v</option>";

        $out .= '</select>
            </p>';

        echo $out;

    }



    /**
     * Format Widget Options
     *
     * Format a widgets options and optionally validate its feed.
     *
     * @return array
     */
    private function widget_control_process($widget_rss, $check_feed = true)
    {

        $items = (int)$widget_rss['items'];
        if ( $items < 1 || 20 < $items )
            $items = 10;

        $maxage = (int)$widget_rss['maxage'];
        if ($maxage < 120)
            $maxage = 120;

        $url        = sanitize_url(urldecode($widget_rss['url']));
        $title      = trim(strip_tags($widget_rss['title']));
        $link       = sanitize_url(urldecode($widget_rss['link']));
        $template   = (int)$widget_rss['template'];
        $showtitle  = (isset($widget_rss['showtitle'])
            ? true
            : false
            );
        $mode       = $widget_rss['mode'];

        if ($check_feed)
            if ($validateerror = $this->validatefeed($url))
            {

                $url = $validateerror['url'];
                $error = $validateerror['error'];

            }

        return compact('title', 'url', 'link', 'items', 'error', 'template',
            'maxage', 'showtitle', 'mode');

    }



    /**
     * Options Page
     */
    public function page_options()
    {

        $out = '<div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>
            <h2>Advanced RSS &rsaquo; ' . __('Options') . '</h2>';



        if (isset($_POST)
            && !empty($_POST)
            )
        {

            $this->options['required_capability'] = ((isset($_POST['required_capability'])
                && !empty($_POST['required_capability'])
                )
                ? (string)$_POST['required_capability']
                : 'switch_themes'
                );

            $this->options['phpfunctions'] = ((isset($_POST['phpfunctions'])
                && !empty($_POST['phpfunctions'])
                )
                ? explode(',', trim((string)$_POST['phpfunctions']))
                : $this->built_in_functions
                );

            update_option('jp_advancedrss', $this->options);

            $out .= '<div id="message" class="updated fade"><p>' . __('Settings saved.') . '</p></div>';

        }



        $out .= '<form method="post" action="options-general.php?page=jp-advancedrss.php&amp;action=options">

            <table class="form-table"><tbody>
                <tr>
                    <th scope="row">
                        <label for="form-required_capability">' . __('User Capability', 'jp-advancedrss') . '</label>
                    </th>
                    <td>
                        <input name="required_capability" id="form-required_capability" value="' . $this->options['required_capability'] . '" class="regular-text" />
                        <span class="setting-description">' . __('Required user capability to change these settings. Clear to reset.', 'jp-advancedrss') . '</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="vertical-align:middle;">
                        <label for="form-phpfunctions">' . __('Allowed PHP Functions', 'jp-advancedrss') . '</label>
                    </th>
                    <td>
                        <input type="text" id="form-phpfunctions"
                            name="phpfunctions" tabindex="2" style="width:80%;"
                            value="' . implode(',', (array)$this->options['phpfunctions']) . '" />
                        <br /><span class="setting-description">'
            . __('Comma seperated list of allowable php functions for use within your xsl templates. Clear to reset. Plugin updates may automatically add more functions to this list.', 'jp-advancedrss') . '
                    </span></td>
                </tr>
            </tbody></table>
            <p class="submit">
                <input type="submit" name="submit" class="button-primary"
                    value="' . __('Save Changes') . '"
                    tabindex="3" />
            </p>

            </form></div>';

        echo $out;

    }



    /**
     * Theme Page
     *
     * Depending queerystring options this can generate a variety of pages
     */
    public function page_theme()
    {

        $action = (isset($_GET['action'])
            ? (string)$_GET['action']
            : null
            );

        $xsl = (isset($_GET['xsl'])
            ? (string)$_GET['xsl']
            : null
            );



        $out = '<div class="wrap"><div id="icon-themes" class="icon32"><br /></div>';



        if (isset($_GET['debug'])) {
            echo $out . '<h2>' . __('Advanced RSS &rsaquo; Show Saved Options', 'jp-advancedrss') . '</h2>';
            echo '<pre>' . htmlentities(print_r($this->options, true));
            echo '</pre></div>';
            return;
        }


        if (isset($_GET['clearcache']))
            $out .= $this->page_theme_clearcache();



        switch($action) {

        case 'create':

            $out .= $this->page_theme_createtemplate()
                . $this->page_theme_listtemplates();
            break;

        case 'edit':

            $out .= $this->page_theme_edittemplate($xsl);
            break;

        default:

            switch ($action)
            {

            case 'validate':
                $out .= $this->page_theme_validatefeed();
                break;

            case 'delete':

                $out .= $this->page_theme_deletetemplate($xsl);
                break;

            case 'reset':
                $out .= $this->page_theme_resettemplate($xsl);

            }

            $out .= $this->page_theme_listtemplates();

        }

        echo $out . '</div>';

    }



    /**
     * @see jp_advancedrss::page_theme()
     */
    private function page_theme_clearcache()
    {

        $this->clear_cache();

        return '<div id="message" class="updated fade"><p>'
            . __('Cache cleared.', 'jp-advancedrss') . '</p></div>';

    }



    /**
     * @see    jp_advancedrss::page_theme()
     * @return string HTML output
     */
    private function page_theme_listtemplates()
    {

        $base_uri = 'themes.php?page=jp-advancedrss.php';

        $out = '<h2>' . __('Advanced RSS &rsaquo; Manage Templates', 'jp-advancedrss') . '</h2>
            <p>
                Note: Default templates can\'t be deleted.
            </p>
            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Description</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Description</th>
                    </tr>
                </tfoot>
                <tbody>';

        foreach($this->wpdb->get_results("SELECT * FROM {$this->table_templates}") as $t)
        {

            $class = ($class == 'alternate'
                ? null
                : 'alternate'
                );

            $out .= '<tr'
                . ($class
                    ? ' class="' . $class . '"'
                    : ''
                    )
                . '><td class="column-title"><a class="row-title" href="' . $base_uri . '&amp;xsl=' . $t->template_id . '&amp;action=edit"><strong>' . $t->title . '</strong></a><div class="row-actions">
                    <span class="edit"><a href="' . $base_uri . '&amp;xsl=' . $t->template_id . '&amp;action=edit">' . ($t->built_in ? 'View' : 'Edit' ) . '</a></span>'
                . (!$t->built_in
                    ? ' | <span class="delete"><a class="submitdelete" href="' . $base_uri . '&amp;xsl=' . $t->template_id
                        . '&amp;action=delete" onclick="javascript:return window.confirm(
                        \'Delete &quot;' . $t->title . '&quot;?\');">Delete</a></span>'
                    : ''
                    )
                . '</div></td><td>'
                . $t->description
                . '</td></tr>';

        }

        $out .= '</tbody></table>

            <p>'
            . __(
                '<strong>Tip:</strong> If you want to edit a default template first copy it to a new file and edit the new copy only. This is so that your changes are not lost when you update the plugin.',
                'jp-advancedrss'
                )
            . '</p>

            <h2>' . __('Create New Template', 'jp-advancedrss') . '</h2>

            <form method="get" action="themes.php">

            <table class="form-table"><tbody>
                <tr class="form-field form-required">
                    <th scope="row" style="vertical-align:middle;">
                        <label for="form-title">' . __('Title') . '</label>
                    </th>
                    <td>
                        <input type="text" id="form-title" name="title"
                            tabindex="1" style="font-size:1.75em;" />
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row" style="vertical-align:middle;">
                        <label for="form-description">' . __('Description') . '</label>
                    </th>
                    <td>
                        <input type="text" id="form-description" name="description"
                            tabindex="2" style="width:80%;" />
                    </td>
                </tr>
            </tbody></table>
            <input type="hidden" name="page" value="jp-advancedrss.php" />
            <input type="hidden" name="action" value="create" />
            <p class="submit">
                <input type="submit" name="submit" class="button-primary"
                    value="' . __('Create Template', 'jp-advancedrss') . '"
                    tabindex="3" />
            </p>

            </form>



            <h2>' . __('Validate An RSS Or ATOM Feed', 'jp-advancedrss') . '</h2>

            <form method="get" action="themes.php">

            <table class="form-table"><tbody>
                <tr>
                    <th scope="row">
                        <label for="form-url">' . __('URL') . '</label>
                    </th>
                    <td>
                        <input type="text" id="form-url" name="url"
                            tabindex="4" style="font-size:1.75em;" />
                    </td>
                </tr>
            </tbody></table>

            <input type="hidden" name="page" value="jp-advancedrss.php" />
            <input type="hidden" name="action" value="validate" />
            <p class="submit">
                <input type="submit" name="submit" class="button-primary"
                    value="' . __('Validate Feed', 'jp-advancedrss') . '"
                    tabindex="5" />
            </p>

            </form>

            <h2>' . __('Clear Cache', 'jp-advancedrss') . '</h2>

            <p>'
            . __(
                'Your cache is cleared when you change your Wordpress theme, edit a template, or modify a widget. If you need to clear the cache for any other reason use this function.',
                'jp-advancedrss'
                )
            . ' <a href="' . $base_uri . '&amp;clearcache=true"><strong
                    >' . __('Clear Cache', 'jp-advancedrss') . '</strong></a>
            </p>
            <p><a href="' . $base_uri . '&amp;debug"><small>show saved options</small></a></p>';

        return $out;

    }



    /**
     * @see    jp_advancedrss::page_theme()
     * @return string HTML output
     */
    private function page_theme_createtemplate()
    {

        $title = (isset($_GET['title'])
            ? "'" . $this->wpdb->escape(preg_replace('/[^a-zA-Z0-9.\-_]/', '', str_replace(' ', '_', (string)$_GET['title']))) . "'"
            : null
            );

        if (!$title)
            return '<div class="error"><p><strong>Error:</strong> You must
                specify a title for your new template.</p></div>';

        $description = (isset($_GET['description'])
            ? "'" . $this->wpdb->escape((string)$_GET['description']) . "'"
            : 'NULL'
            );

        if ($this->wpdb->query("SELECT 1 FROM {$this->table_templates} WHERE title = $title"))
            return '<div class="error"><p><strong>Error:</strong> A template
                titled &quot;' . $title . '&quot; already exists.</p></div>';

        $xsl = "'" . $this->wpdb->escape(file_get_contents($this->plugin_folder . DIRECTORY_SEPARATOR . 'new.xslt')) . "'";

        $sql = $this->insert_template_sql(
            0,
            $title,
            $description,
            $xsl
            );

        $this->wpdb->query($sql);

        return '<div id="message" class="updated fade"><p>'
            . __('Created new xsl template.', 'jp-advancedrss') . '</p></div>';

    }



    /**
     * @see    jp_advancedrss::page_theme()
     * @param  int $id Template ID
     * @return string HTML output
     */
    private function page_theme_edittemplate($id)
    {

        $id = (int)$id;

        if (!$t = $this->wpdb->get_row("SELECT * FROM {$this->table_templates} WHERE template_id = $id"))
            return '<div class="error"><p><strong>Error:</strong> The selected
                template does not exist!</p></div>';

        $out = '<h2>Advanced RSS &rsaquo; Edit Template</h2>';

        if (!empty($_POST)
            && $id
            && !$t->built_in
            )
        {

            $xsl = (get_magic_quotes_gpc()
                ? stripslashes((string)$_POST['xsl'])
                : (string)$_POST['xsl']
                );

            $errmsg = '';
            libxml_use_internal_errors(true);

            $dom = new DOMDocument;
            $dom->loadXML($xsl);

            $xsl_error = false;
            if (!$errors = libxml_get_errors())
            {

                // XML is well formatted, what about the xsl?
                $xsl_error = true;
                $proc = new XSLTProcessor;
                $proc->importStyleSheet($dom);

            }

            if ($errors = libxml_get_errors())
            {

                $xml = explode("\n", $xsl);

                $errmsg .= $this->display_xml_error($errors[($xsl_error ? 1 : 0)], $xml, $xsl_error);
//$errmsg .= print_r($errors, true);
                //foreach ($errors as $error)
                    //$errmsg .= $this->display_xml_error($error, $xml);

                libxml_clear_errors();

            }

            if ($errmsg)
            {

                $t->xslt = $xsl;

                $out .= '<div class="error"><p>There are errors in your XML.</p><p>' . $errmsg . '</p></div>';

            }
            else
            {

                $xsl = "'" . $this->wpdb->escape($xsl) . "'";

                if (isset($_POST['title'])
                    && !empty($_POST['title'])
                    )
                    $_POST['title'] = (get_magic_quotes_gpc()
                        ? stripslashes((string)$_POST['title'])
                        : (string)$_POST['title']
                        );

                $title = ((isset($_POST['title'])
                    && !empty($_POST['title'])
                    )
                    ? "'" . $this->wpdb->escape(preg_replace('/[^a-zA-Z0-9.\-_]/', '', (string)$_POST['title'])) . "'"
                    : "'" . $t->title . "'"
                    );

                if (isset($_POST['description'])
                    && !empty($_POST['description'])
                    )
                    $_POST['description'] = (get_magic_quotes_gpc()
                        ? stripslashes((string)$_POST['description'])
                        : (string)$_POST['description']
                        );

                $description = ((string)$_POST['description']
                    ? "'" . $this->wpdb->escape((string)$_POST['description']) . "'"
                    : 'NULL'
                    );

                $sql = "UPDATE {$this->table_templates} SET title = $title, description = $description, xslt = $xsl WHERE template_id = $id";

                $this->wpdb->query($sql);

                $this->clear_cache();

                $t = $this->wpdb->get_row("SELECT * FROM {$this->table_templates} WHERE template_id = $id");

                $out .= '<div id="message" class="updated fade"><p>Template updated.</p></div>';

            }
        }



        $out .= '
<form method="post"
    action="themes.php?page=jp-advancedrss.php&amp;action=edit&amp;xsl=' . $id . '">

<table class="form-table"><tbody>

<tr>
    <th scope="row">'
. ($t->built_in
    ? __('Title') . '</th><td><strong style="font-size:1.75em;">'
        . $t->title . '</strong></td>'
    : '<label for="form-title">' . __('Title') . '</label>
        </th>
        <td>
            <input type="text" id="form-title" name="title"
            tabindex="1" style="font-size:1.75em;" value="'
        . $t->title . '" />
        </td>'
    )
. '</tr>

<tr>
    <th scope="row">'
. ($t->built_in
    ? __('Description') . '</th><td>' . $t->description . '</td>'
    : '<label for="form-description">' . __('Description') . '</label>
        </th>
        <td>
            <input type="text" id="form-description"
            name="description" tabindex="2" style="width:80%;"
            value="' . $t->description . '" />
        </td>'
    )
. '</tr>

<tr>
    <th scope="row">
        <label for="form-xsl">' . __('XSL Template') . '</label>
    </th>
    <td>
        <textarea cols="106" rows="25" tabindex="1" name="xsl"
            id="form-xsl"'
. ($t->built_in
    ? ' readonly="readonly"'
    : ''
    )
. '>' . htmlentities($t->xslt) . '</textarea>
    </td>
</tr>

</tbody></table>'

. (!$t->built_in
    ? '<p class="submit">
        <input type="submit" name="submit" class="button-primary" value="'
        . __('Update Template', 'jp-advancedrss') . '" tabindex="2" />
        </p>'
    : ''
    )

. '</form>

<h3>Tips</h3>

<p>
    Ensure you use the before_widget, before_title, after_title and
    after_widget variables in your template. You must use xsl:value-of
    with dissable-output-excaping set to yes. I.E.
</p>

<code>&lt;xsl:value-of select=&quot;$before_widget&quot; disable-output-escaping=&quot;yes&quot; /&gt;</code>

<p>
    Additionally if you want to enable the item limiting feature your
    tempalte will have to respond to the $items variable. The feed
    itself is not modified by this setting. To do so you should use a
    conditional immediatly following your for-each tag. I.E.
</p>

<pre><code>&lt;xsl:for-each select=&quot;item&quot;&gt;
    &lt;xsl:if test=&quot;position() &amp;lt;= $items&quot;&gt;
        &lt;!-- each iteration here --&gt;
    &lt;/xsl:if&gt;
&lt;/xsl:for-each&gt;</code></pre>

<p>
    Wrap all tag attributes in text tags so that unwanted whitespace is not
    introduced into the tag, I.E.
</p>

<pre><code>&lt;xsl:attribute name="class"&gt;
    &lt;xsl:text&gt;example-class&lt;/xsl:text&gt;
&lt;/xsl:attribute&gt;</code></pre>';

        return $out;

    }



    /**
     * @see    jp_advancedrss::page_theme()
     * @return string HTML output
     */
    private function page_theme_validatefeed()
    {

        if (isset($_GET['url'])
            && $validationerror = $this->validatefeed(urldecode($_GET['url']), true)
            )
            return '<div class="error"><p><strong>Invalid Feed:</strong> '
                . $validationerror['url'] . '</p></div>';

    }



    /**
     * @see    jp_advancedrss::page_theme()
     * @param  int $id Template ID
     * @return string HTML output
     */
    private function page_theme_deletetemplate($id)
    {

        $id = (int)$id;

        if ($this->wpdb->query("DELETE FROM {$this->table_templates} WHERE template_id = $id AND built_in = 0"))
            return '<div id="message" class="updated fade"><p>Deleted XSL
                template.</p></div>';

        return '';

    }



    /**
     * @see    jp_advancedrss::page_theme()
     * @return string HTML output
     */
    private function page_theme_resettemplate($id)
    {

        $id = (int)$id;

        if (!$title = $this->wpdb->get_var("SELECT title FROM {$this->table_templates} WHERE template_id = $id AND built_in = 1"))
            return;

        $description = $this->built_in_template_description($title);
        $xslt        = $this->built_in_template_xslt($title);

        $sql = "UPDATE {$this->table_templates} SET description = $description, xslt = $xslt WHERE template_id = $id";

        $this->wpdb->query($sql);

        return '<div id="message" class="updated fade"><p>Reset built-in XSL
                template.</p></div>';

    }



    /**
     * Get Built-in Template Description
     *
     * @param  string $title Template title
     * @return string
     */
    private function built_in_template_description($title)
    {

        switch($title)
        {

        case 'Blog':
            return "'Displays post titles as links, followed by a formatted date and below that the description content. Improves upon the built-in widget by using the formatted content if available, so links and other formatting is retained.'";

        case 'Default':
            return "'Copies the behaviour of the built in RSS widget.'";

        case 'Del.icio.us':
            return "'Displays links with a list of tags for each link. Item descriptions form the links title attribute.'";

        case 'Flickr Thumbnails':
            return "'Displays thumbnails from feed, image titles form the links title attribute.'";

        case 'DeviantArt':
            return "'For your Deviant Art Gallery Feed. Will show thumbnails along with optional additional infomation.<br /><br />When adding your widget the \'Formatting\' setting will have the following efects:<dl><dt>Basic</dt><dd>Small thumbnails only.</dd><dt>Extended</dt><dd>Small thumbnails and deviation title.</dd><dt>Full</dt><dd>Large thumbnails, title, date and description.</dd></dl>'";

        case 'Google Groups':
            return "'For Google Groups RSS Feeds. Will show post title along with optional additional infomation.<br /><br />When adding your widget the \'Formatting\' setting will have the following efects:<dl><dt>Basic</dt><dd>Post title only.</dd><dt>Extended</dt><dd>Title, date and author.</dd><dt>Full</dt><dd>Title, date, author and at most 150 characters from their post.</dd></dl>'";

        case 'Twitter':
            return "'For Twitter User Feeds (but not searches), Display each item with users, hashtags and URLs linked. If there is a long post a link to read the full version will be added too.<br /><br />When adding your widget the \'Formatting\' setting will have the following efects:<dl><dt>Basic</dt><dd>Post only</dd><dt>Extended</dt><dd>Post and date</dd></dl>'";

        }

    }



    /**
     * Get Built-in Template
     *
     * @param  string $title Template title
     * @return string XML string.
     */
    private function built_in_template_xslt($title)
    {

        return "'" . $this->wpdb->escape(file_get_contents(
            $this->plugin_folder
                . DIRECTORY_SEPARATOR
                . str_replace(' ', '', str_replace('.', '', strtolower($title)))
                . '.xslt')) . "'";

    }



    /**
     * Format XML/XSL Error
     *
     * @see    jp_advancedrss::page_theme_edittemplate()
     * @param  object $error
     * @param  string $xml        XML source
     * @param  bool   $error_only Do not show line and col, for XSLT erros which
     *                            don't populate there members.
     * @return string
     */
    private function display_xml_error($error, $xml, $error_only = false)
    {

        $ret = '';

        if (!$error_only)
            $ret  .= htmlentities($xml[$error->line - 1]) . "<br />";

        switch ($error->level)
        {

        case LIBXML_ERR_WARNING:
            $ret .= "Warning $error->code: ";
            break;

        case LIBXML_ERR_ERROR:
            $ret .= "Error $error->code: ";
            break;

        case LIBXML_ERR_FATAL:
            $ret .= "Fatal Error $error->code: ";
            break;

        }

        $ret .= trim($error->message);

        if (!$error_only)
            $ret .= "<br />At line $error->line, column $error->column<br />";

        return $ret;

    }



    /**
     * Get Twitter Item User
     *
     * If a twitter feed item's title or description contains an @user tag get
     * that user.
     *
     * @deprecated
     * @param  string $subject Twitter feed item titme or description.
     * @return string 1 for true, 0 for false.
     */
    public static function twitter_user($subject)
    {

        if (preg_match('/^\w+: @(\w+)/', $subject, $matches))
            return $matches[1];

        return '';

    }



    /**
     * Get Twitter Item Strip Channel & User
     *
     * @param  string $subject Twitter feed item titme or description.
     * @param  string $link    Twitter feed item guid
     * @param  string $options JSON options string. Supports the following:
     *                         - user: Set to enable user linking and use its
     *                           value as the class for the wrapping span tag.
     *                         - link: Set to enable URL linking and use its
     *                           value as the class for the wrapping span.
     *                         - hashtag: Set to enable hashtag linking and use
     *                           its value as the class for the wrapping span.
     *                         - continues: Set to enable appending link when a
     *                           post has been shortened by Twitter. Its value
     *                           is used as the link's text, suggested use is
     *                           "&hellip;" (An ellipsis).
     * @return string HTML formatted item title/description.
     */
    public static function twitter_format($subject, $link = '', $options = '{}')
    {

        $options = @json_decode($options);

        if ($options && !is_object($options))
            return 'Advanced RSS Twitter Format: Invalid options json, check your XSL.';

        // make @user into user page links
        if (isset($options->user) && $options->user)
            $subject = preg_replace(
                '/(@(\w+):)/',
                "<span class=\"{$options->user}\">@<a href=\"http://www.twitter.com/$2\">$2</a>:</span>",
                $subject
                );

        /**
         * make http:// or www. into links
         * twitter feed can chop the ned off a url so need to be careful to only
         * link where they appear to be full, that is there isnt ...$ following
         * a link
         */
        if (isset($options->link) && $options->link)
            $subject = preg_replace_callback(
                array(
                    '`((?:https?|ftp)://\S+[[:alnum:]]/?( (?!\.{3}$)|$))`si',
                    '`((?<!//)(www\.\S+[[:alnum:]]/?)( (?!\.{3}$)|$))`si'
                    ),
                create_function(
                    '$matches',
                    '$matches[1] = preg_replace("/&(?!amp;)/","&amp;",$matches[1]);
                        return "<a href=\"$matches[1]\" rel=\"nofollow\" class=\"$link_class\">$matches[1]</a>";'
                    ),
                $subject
                );

        // make #hashtag into search links
        // do not match if the hashtag is followed by ...[end of string]
        // this is safe for character escapes such as &#244; because they end
        // with a ; which "(\w+) " wont match due to the space requirement
        if (isset($options->hashtag) && $options->hashtag)
            $subject = preg_replace(
                '/(#(\w+)( (?!\.{3}$)|$))/',
                "<span class=\"{$options->hashtag}\">#<a href=\"http://search.twitter.com/search?q=%23$2\">$2</a></span> ",
                $subject
                );

        if ($options->continues && (substr($subject, -3) == '...'))
            $subject = substr($subject, 0, -3) . "<a class=\"twitter-concat\" href=\"$link\">{$options->continues}</a>";

        return preg_replace('/(^\w+:)|(\.{3}$)/', '', $subject);

    }

}



/**
 * Get Twitter Item User
 *
 * @deprecated
 * @see    jp_advancedrss::twitter_user()
 * @param  string $subject Twitter feed item titme or description.
 * @return string 1 for true, 0 for false.
 */
function jp_xslt_twitter_user($subject)
{

    return jp_advancedrss::twitter_user($subject);

}



/**
 * Twitter Item Strip Channel & User
 *
 * @deprecated
 * @see    jp_advancedrss::twitter_format()
 * @param  string $subject Twitter feed item titme or description.
 * @return string 1 for true, 0 for false.
 */
function jp_xslt_twitter_strip($subject)
{

    return jp_advancedrss::twitter_format($subject, '', '');

}



/**
 * Twitter Item Format Into HTML
 *
 * @see    jp_advancedrss::twitter_format()
 * @param  string $subject Twitter feed item titme or description.
 * @return string 1 for true, 0 for false.
 */
function jp_xslt_twitter_format($subject, $link, $options = '{"user":"twitter-user", "link":"twitter-link", "hashtag":"twitter-hashtag", "continues":"&hellip;"}')
{

    return jp_advancedrss::twitter_format($subject, $link, $options);

}