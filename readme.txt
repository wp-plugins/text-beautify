
=== Text Beautify ===

Contributors: rsantor
Donate link: http://rommelsantor.com/clog/
Tags: grammar, text, case, punctuation, text beautify, aesthetics, sentence case, title case, curly quotes
Requires at least: 2.0.2
Tested up to: 3.5.1
Stable tag: 0.6

Cleans up posts and comments for sentence case or title case, fixes punctuation, makes quotes and commas curly, and allows custom enhancements.

== Description ==

This plugin will automatically parse through the contents of each blog post and/or comment when displayed and look for bad casing and other common grammatical/aesthetic faux pas, like sloppy punctuation. It will also make blog post titles all uppercase except for select words, which are kept lowercase. Double and single quotation marks, as well as commas, are automatically made into their attractive curly form. You can also provide a custom list of tweaks you'd like the plugin to make, such as uppercasing select words or disabling some of the plugin's default replacements.

For example, consider the following text as a comment's contents:
  AMAZING!!!!! THIS IS SUCH AN AMAZING STORY AND I KNOW MY FRIEND,, DR. BOBBY WOULD THINK SO TOO... DON'T YOU AGREE???!!!

The default functionality of the Text Beautify plugin would display that content as follow:
  Amazing! This is such an amazing story and I know my friend, Dr. Bobby would think so too... Don't you agree?!

The full feature list is as follows:

*   Use proper sentence casing instead of all capitals in comments and post body
*   Capitalize each word in blog titles except for user-editable list of exceptions
*   Remove user-editable list of excessive punctuation; by default: exclamation marks, question marks, asterisks, commas
*   Replace standard quotation marks, apostrophes, and commas with their curly equivalents
*   Preserve case (in blog posts, blog titles, and comments) of user-editable list of terms
*   Execute user-editable list of specific string replacements (in blog posts, blog titles, and comments)

== Installation ==

1. Unzip text-beautify.zip into the `/wp-content/plugins/` directory. This will create directory `/wp-content/plugins/text-beautify/`.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. That's it!

== Frequently Asked Questions ==

= Can I disable the plugin on just comments in my blog? =

Yes! You can enable or disable the plugin on each of blog contents, blog titles, and comments.

= Will the blog title capitalization override my custom uppercase terms? =

Nope. You customizations will always take precedence over any of the default text processing.

== Screenshots ==

1. The plugin settings interface.
1. An example sloppy post without Text Beautify enabled.
1. The same post but this time with Text Beautify enabled.

== Changelog ==

= 0.6 =
* Fixed significant bug wherein some beautified text was getting mangled or stripped. It would only happen on occasional page loads. Thanks to James Burnby who alerted me to this issue.

= 0.5 =
* Plugin will now automatically leave any text contained within square brackets untouched. Example: if [some plugin="CODE"] is in your body of text, it will be passed through as-is. This is because it's very common for other WordPress plugins to use such special bracket tags to do their own special processing.

= 0.4.2 =
* Modified and corrected the way the new disabling of automatic case manipulation is processed; some automatic case adjustment was still executing

= 0.4.1 =
* Forcing new default options to be merged into existing options upon upgrade

= 0.4 =
* Fixed warning message on line 291
* Added default case preservation for days of week, full month names, and short month names
* Added ability to disable automatic case manipulation

= 0.3 =
* Corrected preg_match_all bug with invalid starting delimiter

= 0.2.1 =
* Minor fixes in plugin package

= 0.2 =
* Fixed handling of URL and HTML entity strings
* Allowed for compatibility with &lt;!--start_raw--&gt;&lt;!--end_raw--&gt; tags
* Better processing of multi-line HTML tags

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
None.

