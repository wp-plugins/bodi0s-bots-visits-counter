=== bodi0`s Bots visits counter ===
Contributors:
Donate link:
Tags: website, visits, counter, bot, crawler, spider, statistics
Requires at least: 3.1.0
Tested up to: 3.8.1
Stable tag: 0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Counts the visits from web spiders, crawlers and bots in your blog, with ability to get the blog rankings.

== Description ==
Counts the visits from web spiders, crawlers and bots in your blog, with ability to get the blog rankings from Google or Alexa. 
Also can count any other visit, the plug-in is looking for patterns in user-agent string, which pattern can be customized.
You can block or unblock crawler, spider (or any other item), by IP address and identifier via .htaccess file. 
As additional information you can get your blog rankings from Google PageRank or Alexa.

== Installation ==
This section describes how to install the plugin and get it working.

1. Upload unzipped archive of the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
None

== Screenshots ==
None

== Changelog ==
= 0.7 =
Added blog ranking check via Statscrop.
UI improvements and small bugfixes.
Minor translation updates.

= 0.6 =
Added ability to get the blog rankings, from Google or Alexa.
Small improvements, translation updated and bugfixes.

= 0.5 =
Updated filtering of the results. Now filter applies to any table column.
Added option to block/unblock bot, by IP address and identifier via .htaccess file, the plugin creates a backup of your old .htaccess file in case something goes wrong, called .htaccess.bot-counter-backup.txt in the same folder as your original file.
Removed unnecessary javascript files related to table results filtering.

= 0.4 =
Added in-line edit/delete of bot's name.
Added ability to export statistics as XML Spreadsheet file, can be opened by MS Excel.
Added French translation.
Bugfixes and small improvements in filtering and sorting of the results.

= 0.3 =
Added on-page sort of the table with results (TableSorter 2.14.4 by Christian Bach).

= 0.2 =
Added internationalization support. For now Bulgarian and English only.
Added ability to filter the bot list results.
Added additional statistics.
Added geo-location search by bot's IP address.
Improved plugin's security.
Various small bug-fixes and improvements.

= 0.1 =
Initial release.