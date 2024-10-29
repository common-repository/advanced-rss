=== Advanced RSS ===
Contributors: jixor
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=stephen%40margni%2ecom&item_name=XSLT%20RSS&no_shipping=1&return=http%3a%2f%2fwww%2ejixor%2ecom%2fthankyou%2ehtm&cancel_return=http%3a%2f%2fjp%2ejixor%2ecom&no_note=1&tax=0&currency_code=AUD&lc=AU&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: widget,rss,rss2,atom,xml,xsl,xslt,sidebar,feeds,feed,deviantart,flickr,delicious,google,twitter
Requires at least: 2.5
Tested up to: 2.8.5

Create advanced, fully customizable RSS feed widgets. You can use this to
replace the built in RSS widget or along side it. It is substantially more
powerful than the built in widget in that you have complete control over how the
feed is displayed via XSL template files.



== Description ==

This plugin creates a new RSS widget. You can use this to replace the built in
RSS widget or along side it. It is substantially more powerful than the built in
widget in that you have complete control over how the feed is displayed via XSL
templates. The plugin also includes an admin page for creating, editing and
deleting the xsl templates, along with running tests to ensure your environment
supports the required features. Additionally some plugin-wide options may be
configured.

= Included Templates =

* Blog
* Default - Mimics the built in RSS widget's apperance.
* Del.icio.us - List your recent Delicious bookmarks.
* Flickr Thumbnails - Thumbnail list from your photostream.
* DeviantArt - Thumbnail list of deviations from your gallery, with different
  display options.
* Google Groups - List recent posts to a group, with different display options.
* Twitter - Posts with users, hashtags and URLs linked. Formatting option for
  time and date display.

= Server Requirments =

* PHP 5.2 +
* DOM extention
* XSL extentions

If you don't know what you have then activate the plugin. Once activated if
there are any problems they will be reported below the plugin on your plugins
page. Report these issues to your hosting provider and they should be able to
fix them - if not maybe you should consider getting a better host.

= Notes =

This plugin is demanding of your server. If your server isn't up to date or well
configured the plugin may not function. But don't worry the plugin will make
some tests and let you know exactly what needs to fixed, if anything.

You can only use this plugin with valid RSS or ATOM feeds.

The plugin comes with a few example XSL templates to get you started. You may
not even need to edit or create your own depending on what feeds you want to
syndicate. XSL is not the easiest thing, however once once you understand the
basics its fairly strait forward.

The included XSL templates demonstrate various XSLT methods, such as conditional
statemenets, variable use, XPath expressions and namespace access. I strongly
encourage you to analyse the included templates when learning xslt.



== Installation ==

1. Upload the jp-advancedrss plugin directory, along with its contents, to your
   worpress plugins directory: `/wp-content/plugins/`.
2. Activate the plugin and follow the instructions in the description.
3. Add a widget.

Optionally you may create your own templates for fommatting your feeds. You also
might like to edit your CSS to customize the look of your feeds. Each feed has
a unique ID so its easy to target.



== Frequently Asked Questions ==

= Can you make a custom template for site X? =

Just send me your request with a link to the feed and what details you want and
if I think people might find it handy I'll consider creating it and distributing
it along with the plugin.

= I'm receiving an error about my PHP environment! =

Please send the error message to your hosting provider to see if they can
upgrade your version of PHP.

= What is XSL/XSLT? =

(Excerpt from w3schools)
XSL stands for EXtensible Stylesheet Language.

The World Wide Web Consortium (W3C) started to develop XSL because there was a
need for an XML-based style sheet language.

XSLT stands for XSL Transformations.

To put it another way, XSL is an advanced templateing language.

Read more about XSLT at [w3Schools](http://www.w3schools.com/xsl), or at
[w3](http://www.w3.org/TR/xslt).

= Do I need to know XSLT to use this plugin? =

The plugin includes an increasing number of default templates. If one of these
templates doesn't do what you need see the question above; "Can you make a
custom template for site X?".

If your editing your own template the plugin will help you to make it valid XML
and XSL before you use it.



== Screenshots ==

1. The Widget configuration panel
2. The XSL template mangement screen
