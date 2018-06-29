=== HTML Minifier ===

Contributors: terresquall
Tags: source minifier, minify, html, javascript, css, optimisation, caching, reorganisation
Requires at least: 3.6.4
Tested up to: 4.9.6
Stable tag: 2.2.4
Requires PHP: 5.4
License: GPLv2 or later

Provides a variety of optimisation options (e.g. minification, caching, code reorganisation) for your site's source code to help meet today's web performance standards.

== Description ==

HTML Minifier is a server-side source code minifier, reorganiser and caching tool and that is available both as a PHP class and as a WordPress plugin. It is designed to optimise HTML, CSS and Javascript output sent out to the client by removing whitespace, and by reorganising and / or merging &lt;link&gt;, &lt;style&gt; and &lt;script&gt; tags scattered across HTML pages that are built dynamically on server-side applications.

A variety of optimisation options and minification styles are available in the plugin, and they can be selected from or toggled depending on the user's needs. To see the full list of options or to download the PHP version, [click here](http://www.terresquall.com/web/html-minifier/).

There is also a [GitHub repository](https://github.com/terresquall/html-minifier) for the project, if you want to contribute. Alternatively, [donations](https://paypal.me/Terresquall) are also always welcome.

== Installation ==

Unzip the downloaded file into the plugin folder in your WordPress site and activate it in WP-Admin. Alternatively,download it directly from WP-Admin and activate it.

== Changelog ==

= 2.2.4 =
*Release Date - 27 June 2018*

* This release implements a few fixes to make the plugin more compliant with WordPress plugin coding standards.
* Changed the injection location of HTML Minifier's .htaccess snippet on the WordPress webroot.
* Moved the caching folder from _/wp-content/html-minifier/cache_ to _/wp-content/cache/html-minifier_.
* Moved the Quick Presets section in Primary Settings up, to make it more noticeable for users.
* Fixed a bug that causes the 'ignore_async_and_defer_tags' to behave opposite of what it should (i.e. it was working when it was toggled off and vice versa).

= 2.2.3 =
*Release Date - 25 May 2018*

* Fixed a bug where if you enabled .css and .js minification, visiting non-existing .css or .js files would not show a 404.

= 2.2.2 =
*Release Date - 13 May 2018*

* Fixed a bug causing &lt;style scoped&gt; tags to not have their comments cleaned.
* Fixed a bug with &lt;style&gt; types nested in IE conditional tags causing a fatal error.
* &lt;script&gt; tags that have an 'id' attribute are no longer merged with other scripts.
* Non-Javascript &lt;script&gt; tags are no longer moved if you choose to move Javascript to the bottom of the page, and comments in these non-Javascript tags are no longer removed.
* Improved detection algorithm for identifying what are Javascript blocks and what are not.

= 2.2.1 =
*Release Date - 12 May 2018*

* Changed the exclamation mark icons added in version 2.2.0 (this was an unlisted change) to question mark icons, to better signify their function as tooltip. The exclamation mark made it seem like they were issues that required the user's attention.
* HTML Minifier has been tested to work with WP Super Cache, and the tooltip on the Caching tab of the settings page was changed to reflect this.
* Changed the tooltip on top of the Advanced Settings page to more accurately explain why certain things might break your site.
* Rewrote the description of this plugin, so that it also describes recently-implemented features.

= 2.2.0 =
*Release Date - 9 May 2018*

* Re-added the features from version 2.1.2 (see below).
* Fixed some bugs that occurred with .htaccess writing for the Advanced Features, due to not locking .htaccess when writing the file.
* Fixed a bug where the .htaccess file is written multiple times when the database is updated.
* Options that modify .htaccess should be much more stable now, with the major fixes applied to how .htaccess is written.
* Added some mouse over tool tips notifications for the more "dangerous" options.

= 2.1.5 =
*Release Date - 8 May 2018*

* Bug-fix: Rolled back the files to version 2.1.1 as versions 2.1.2 to 2.1.4 contained severe errors.

= 2.1.4 =
*Release Date - 8 May 2018*

* Restored the previous method of upgrading the database, as the new method doesn't work and the database is not upgrading.

= 2.1.3 =
*Release Date - 8 May 2018*

* Fixed a bug that caused the database to not update when the plugin is updated.

= 2.1.2 =
*Release Date - 8 May 2018*

* Made the database upgrader work more efficiently. Previously, it ran every time a new page loaded. Now, it only works when you activate or upgrade the plugin.
* Added a new option to "Browser Resource Caching", which tells the client-side browser to cache minified CSS or JS files. This can improve loading speed.
* Added a new option "Compression Ignored Tags", which allows you to set tags whose contents are not compressed. This is for tags like &lt;textarea&gt; and &lt;pre&gt;, wherein whitespace in their contents affect rendering. You can also use it to disable &lt;script&gt; tag compression, in case your inline Javascript is not properly-written.
* The "Compression Ignore Script Tags" option has been removed, as its functionality has been transferred to "Compression Ignored Tags". If you have checked the option before upgrading, it will automatically be reflected in your settings under "Compression Ignored Tags".
* If 'Show Signatures' is enabled, HTML Minifier now leaves a signature in minified resources files (i.e. .js and .css) too.
* Improved the design of the static tooltips in the Settings page.
* Made the Settings UI work better with browsers that have Javascript disabled - it now shows a message to prompt the user to enable their Javascript.

= 2.1.1 =
*Release Date - 29 March 2018*

* Bug-fix: Fixed a severe error with caching that caused the plugin to break on some servers.

= 2.1.0 =
*Release Date - 29 March 2018*

* HTML Minifier now minifies CSS and JS files on your WordPress site too. You can find these settings under the Advanced Settings tab.
* The CSS and JS files minified by HTML Minifier can also be cached, so that they will be served faster.
* Bug-fix: Fixed an issue with the Javascript comments remover in the HTMLMinifier.php source. It no longer removes comment blocks inside regex expressions.

= 2.0.1 =
*Release Date - 4 January 2018*

* Contents inside &lt;textarea&gt; tags are now no longer compressed, as this causes important characters in the form field to be removed.

= 2.0.0 =
*Release Date - 25 December 2017*

* Completely recoded the source PHP class HTMLMinifier. Now, it isn't dependent on PHP's DOMDocument class, which caused some strange behaviours (like changing double quotes to single quotes, or encoding some UTF-8 characters wrongly.
* Added a lot of new control options for minifying your HTML, and made the Settings page more seamless (it works on Backbone now!).
* Features to expect in the future: 1) more selective HTML shifting options, such as excluding certain <script> tags (like HTML5 Shiv) when moving them to the bottom, 2) 'ligtening' WordPress by removing certain tags and includes that are generally not very useful.
* As usual, please help to report if there are any bugs you find. The source has been completely recoded, so there are bound to be bugs.

= 1.1.1 =
*Release Date - 5 September 2017*

Bugfix patch to correct a major issue.

* Fixed an issue with HTMLMinifier.php that caused certain compression options to remove chunks of HTML code.
* Fixed an issue with "Don't compress content in &lt;script&gt; tags" leaving some whitespace behind after compression.

= 1.1.0 =
*Release Date - 28 August 2017*

* Redesigned the options page.
* Added a caching tab. Yes, the caching option is coming soon! For now, just use [WP Super Cache](https://wordpress.org/plugins/wp-super-cache/) for your caching needs first.
* Fixed some minor bugs with the HTML Minifier code so that it will be more effective and less buggy.
* Added a bunch of preset options for new users to choose from, since the amount of options available are quite a doozy.
* Multi-language support should be coming soon.

= 1.0.5 =
*Release Date - 2 July 2017*

Watch out for a native caching function in this plugin in future!

* Fixed a small issue with the movement of script tags inside IE conditional brackets.
* Now you cannot uncheck 'Remove JS comments' and 'Remove CSS comments' when your compression mode is "All whitespace".

= 1.0.4b/c =
*Release Date - 26 May 2017*

Marked this plugin as being in beta in the readme.

= 1.0.4 =
*Release Date - 21 May 2017*

Mainly a bug-fixing release.

* Fixed a bug that caused some IE conditional comments to be removed.
* Minification of <script> tag contents is now done by string manipulation instead of PHP DOMDocument. This fixes a few bugs with IE conditional comments (yes, those are stupid).
* The "Show signature" option now tracks the number of bytes saved from minification.
* Added a new "Minify WP-Admin" option. You might not want to use it until after a few versions, as it can break certain pages in your WP-Admin. Note that "Combine Javascript in script tags" is always disabled in WP-Admin, as it breaks the code.
* If your HTML source is broken, HTMLMinifier (the class) now throws some errors. In the future, it will start checking if your HTML document is valid before minifying, so that its easier to find where errors are.

As usual, please report any bugs to [mail@terresquall.com](mailto:mail@terresquall.com).

= 1.0.3 =
*Release Date - 20 May 2017*

* Added a new option to force remove commented CDATA tags in <script> blocks. They optimally should not be because they make a document XHTML compatible.
* Fixed some minor minification bugs that caused certain conditional HTML tags to be erroneously removed.
* Fixed some formatting errors on readme.md.
* Fixed some display issues the Settings page has with display on mobile.

= 1.0.2 =
*Release Date - 18 May 2017*

* Implemented stricter user input sanitisation on the plugin options page.
* Optimised some code related to plugin initialisation on page load.

= 1.0.1 =
*Release Date - 17 May 2017*

* First open release to public. 