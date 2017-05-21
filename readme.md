=== HTML Minifier ===

Contributors: terresquall
Tags: source minifier, minify, html, javascript, css, html optimiser
Requires at least: 3.2
Tested up to: 4.7.5
Stable tag: 1.0.4
License: GPLv2 or later

A server-side source code minifier for Wordpress, HTML Minifier is designed to minimise and optimise HTML output.

== Description ==

HTML Minifier is a server-side source code minifier that is available both as a PHP class and as a Wordpress plugin. It is designed to optimise HTML output sent out to the client by removing whitespace, and by reorganising and / or merging <link>, <style> and <script> tags scattered across HTML pages that are built dynamically on server-side applications.

A variety of optimisation options and minification styles are available in the plugin, and they can be selected from or toggled depending on the user's needs. To see the full list of options or to download the PHP version, [click here](http://www.terresquall.com/web/html-minifier/).

There is also a [GitHub repository](https://github.com/terresquall/html-minifier) for the project, if you want to contribute.

== Installation ==

Unzip the downloaded file into the plugin folder in your blog and activate it in WP-Admin. Alternatively, you can just download it directly from WP-Admin and activate it.

== Changelog ==

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