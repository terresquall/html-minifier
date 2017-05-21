=== HTML Minifier ===

Contributors: terresquall
Tags: akismet, comments, spam, antispam, anti-spam, anti spam, comment moderation, comment spam, contact form spam, spam comments
Requires at least: 3.2
Tested up to: 4.7.5
Stable tag: 1.0.3
License: GPLv2 or later

A server-side source code minifier for Wordpress, HTML Minifier is designed to minimise and optimise HTML output.

== Description ==

HTML Minifier is a server-side source code minifier that is available both as a PHP class and as a Wordpress plugin. It is designed to optimise HTML output sent out to the client by removing whitespace, and by reorganising and / or merging <link>, <style> and <script> tags scattered across HTML pages that are built dynamically on server-side applications.

A variety of optimisation options and minification styles are available in the plugin, and they can be selected from or toggled depending on the user's needs. To see the full list of options or to download the PHP version, [click here](http://www.terresquall.com/web/html-minifier/).

There is also a [GitHub repository](https://github.com/terresquall/html-minifier) for the project, if you want to contribute.

== Installation ==

Upload the HTML Minifier plugin to your blog and activate it in WP Admin.

== Changelog ==

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