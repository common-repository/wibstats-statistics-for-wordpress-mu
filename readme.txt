=== Wibstats ===
Contributors: mrwiblog
Donate link: http://www.stillbreathing.co.uk/donate/
Tags: buddypress, wordpress mu, statistics, visitors, graphs, location, blog, visits, page, views, multi, user
Requires at least: 2.7
Tested up to: 2.9.2
Stable tag: 0.5.6

Wibstats is a Wordpress plugin that gives each blog in a Wordpress, Wordpress MU or BuddyPress installation their own visitor statistics.

== Description ==

Wibstats is a Wordpress plugin that gives a Wordpress site, or each blog in a Wordpress MU or BuddyPress installation, their own visitor statistics. The data stored includes the page viewed, date and time of visit, visitors browser, operating system and screen resolution, and the geographic location of the visitor (country and city) where it is possible to determine that information.

The plugin uses freely available APIs from several providers to determine the geographic location of the visitor. One of the APIs is chosen randomly for each visit to spread the load across each of the API providers.

A range of graphs and tables are available to users to see the visitors to their blog. Wordpress MU site administrators also have the option to easily view the statistics for any particular blog, as well as for the main site.

The plugin relies on the `wp_footer` action, normally used in the footer.php file of the theme. Without this action no visitor statistics will be stored. Please check each template available to your end users to ensure they all use the `wp_footer action`.

== Installation ==

For standard Wordpress:

The plugin should be placed in your /wp-content/plugins/ directory, so it looks like this:

`/wp-content/plugins/
/wp-content/plugins/wibstats/
/wp-content/plugins/wibstats/wibstats.php
/wp-content/plugins/wibstats/wibstats-includes/`

For Wordpress MU or BuddyPress:

The plugin should be placed in your /wp-content/mu-plugins/ directory (*not* /wp-content/plugins/) so it looks like this:

`/wp-content/mu-plugins/
/wp-content/mu-plugins/wibstats.php
/wp-content/mu-plugins/wibstats-includes/`

Wibstats for Wordpress MU requires no activation. The database table for each blog should be created automatically.

== Shortcodes ==

WibStats allows you to include statistics from your site in your blog posts and pages. This is done with shortcodes, simple bits of text that set some parameters for the information to display. For example:

`[wibstats report="popularsearches"]` 

Will give you (for example); 

`Search			Visitors
search 1		40%
search 2		30%
search 3		20%
search 4		10%` 

Please note these examples here are not formatted correctly (due to Wordpress readme file restrictions). The proper code looks like this:

`<div class="wibstats_report [name of the report]">
<table>
	<thead>
		<tr>
			<th>Column 1</th>
			<th>Column 2</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Row 1, column 1</td>
			<td>Row 1, column 2</td>
		</tr>
		<tr>
			<td>Row 2, column 1</td>
			<td>Row 2, column 2</td>
		</tr>
	</tbody>
</table>
</div>`

Want another example? OK: 

`[wibstats report="recentcountries"]`

Gives: 

`Country				Time
United Kingdom		9:22 pm
United States		9:21 pm
Portugal			9:18 pm`

When showing country names WibStats will also show a small icon of the flag of that country.

There are quite a few different reports you can show (these go in the `report="report-name-here"` bit). 

* `popularcountries`
The most popular countries to visit your blog
* `popularcities` 
The most popular cities to visit your blog
* `recentcountries` 
The most recent countries to visit your blog
* `recentcities` 
The most recent cities to visit your blog
* `popularbrowsers` 
The most popular browsers to visit your blog
* `popularplatforms` 
The most popular platforms (operating systems) to visit your blog
* `popularscreensizes` 
The most popular screen sizes to visit your blog
* `popularsearches` 
The most popular search words which found your blog
* `recentsearches` 
The most recent search words which found your blog
* `populardays` 
The most popular days of the week that people visited your blog
* `popularhours` 
The most popular hours of the day that people visited your blog
* `popularmonths` 
The most popular months of the year that people visited your blog
* `popularreferrers` 
The most popular referring websites that sent visitors to your blog
* `recentreferrers` 
The most recent referring websites that sent visitors to your blog
* `session` 
A breakdown of the data associated with the current visitor to your blog (their country, city, browser etc)

A couple of other options allow you to configure these reports as they display on your posts/pages. 

`size`

Sets the number of items you want to show (minimum 1, maximum 100) 

`cache`

Sets how long you want the report to be cached for. Caching means that the report isn't recalculated every time someone visits the page, meaning the page is a little bit faster to load. 

The `size` option is set in minutes, with "0" meaning not-cached-at-all (the report is recalculated every time someone visits the page it appears on) and "-1" for cached forever (the report is generated once then remains the same forever). 

So, a couple more examples: 

`[wibstats report="popularcities" size="25"]`

This shows the top 25 most popular cities to visit your blog. 

`[wibstats report="recentsearches" size="5" cache="0"]`

This shows the top 5 latest search words which brought visitors to your blog, and is not cached at all. 

`[wibstats report="popularreferrers" size="50" cache="-1"]`

This shows the top 50 most popular referring websites (sites that have a link to your blog) and is cached forever. This means the report will show what the top referring sites are now and will never be updated. 

== Upgrade Notice ==

= 0.5 =

WibStats version 0.5 enables WibStats for standard (i.e. standalone) Wordpress installations. It also introduces several new reports, fixes some bugs, changes the database schema and allows the use of shortcodes. This is a highly recommended upgrade.

== Frequently Asked Questions ==

= Why did you write this plugin? =

I looked around for a suitable statistics plugin for one of my websites (wibsite.com) but was disappointed in the options available. In fact I tried one of the most popular ones but it was very badly written and I had to remove it as it was killing my server. So, I decided to write one myself.

= The stats aren't working? What's gone wrong? =

There are two reasons visitor stats might not be working:

1) The wp_footer() action is not being fired in your template. Please ensure that the wp_footer() action is in your footer.php file.

2) The statistics tables could not be created. If your Wordpress database user does not have CREATE TABLE provileges you will need to run these two SQL scripts (replacing [prefix] with your Wordpress database prefix, for example "wp_"). For Wordpress MU the [prefix] needs to be replaced with the base database prefix AND the blog id (for example "wp_123"):

`CREATE TABLE [prefix]wibstats_sessions ( 
id mediumint( 9 ) NOT NULL AUTO_INCREMENT,
timestamp bigint( 11 ),
ipaddress VARCHAR( 24 ),
sessionid VARCHAR( 50 ),
colordepth VARCHAR( 3 ),
screensize VARCHAR( 12 ),
browser VARCHAR( 50 ),
version VARCHAR( 12 ),
platform VARCHAR( 50 ),
page VARCHAR( 255 ),
title varchar( 255 ),
referrer VARCHAR( 255 ),
referrer_domain VARCHAR( 255 ),
terms VARCHAR( 255 ),
city VARCHAR( 50 ),
country VARCHAR( 50 ),
countrycode VARCHAR( 3 ),
latitude FLOAT( 10,6 ),
longitude FLOAT( 10,6 ),
PRIMARY KEY  ( id ),
KEY timestamp ( timestamp ),
KEY ipaddress ( ipaddress ),
KEY sessionid ( sessionid ),
KEY colordepth ( colordepth ),
KEY screensize ( screensize ),
KEY browser ( browser ),
KEY version ( version ),
KEY platform ( platform ),
KEY page ( page ),
KEY title ( title ),
KEY referrer ( referrer ),
KEY referrer_domain ( referrer_domain ),
KEY terms ( terms ),
KEY city ( city ),
KEY country ( country ),
KEY countrycode ( countrycode ),
KEY latitude ( latitude ),
KEY longitude ( longitude )
);`

`CREATE TABLE [prefix]wibstats_pages ( 
id mediumint( 9 ) NOT NULL AUTO_INCREMENT,
timestamp bigint( 11 ),
page VARCHAR( 255 ),
title varchar( 255 ),
sessionid VARCHAR( 50 ),
referrer VARCHAR( 255 ),
referrer_domain VARCHAR( 255 ),
terms VARCHAR( 255 ),
PRIMARY KEY  ( id ),
KEY timestamp ( timestamp ),
KEY page ( page ),
KEY title ( title ),
KEY sessionid ( sessionid ),
KEY referrer ( referrer ),
KEY referrer_domain ( referrer_domain ),
KEY terms ( terms )
);`

For more information and support leave a comment here: http://www.stillbreathing.co.uk/projects/mu-wibstats/

== Screenshots ==

1. The main Wibstats reports page
2. Recent visitor locations, with visitor map popup
3. Searches report
4. Search term report
5. Referrers report
6. Referring site report
7. Direct visitors report
8. Pages viewed report
9. Visitor locations report
10. Visit times report
11. Visitor environment report
12. Session report

== Changelog ==

0.5.6 (2010/12/03) Fixed bug in Plugin Register caused by latest version of WordPress

0.5.5 (2010/15/14) Updated plugin URI

0.5.4 (2010/04/20) Implemented new Plugin Register version.

0.5.3 Fixed bug with Google Maps API key. Fixed duplicate admin menu option bug. Added Plugin Register code.

0.5.2 Fixed bugs with visitor tracking image and Google map

0.5.1 Added a support link and donate button

0.5 Completely rewrote the plugin, which now works with standard Wordpress. Added new reports, fixed errors with old reports, changed menu system. Added shortcodes so statistics can be included in blog posts or pages.

0.4.4 Fixed bug which stopped tables being created automatically

0.4.3 Fixed bug which led to divide by zero errors

0.4.2 Fixed bug which hid the recent visitors map

0.4.1 Added link to options screen, fixed bug with 24 hour report timezone offset

0.4 Added referrer report, cleaned up country reports, added option to choose time offset to show visitor times relative to the viewer, added breakdown of search, referred and direct visitors

0.3 Added Google maps, visitor and page view percentage change numbers, view by referrer/search term/page/visitor environment, session report and many more improvements

0.2 Added date range views (24 hour, 14 day, 12 week)

== To-do ==

- Stats-by-email, where Wibstats will email you daily, weekly or monthly with the latest statistics.
- More ways to slice and dice the existing data. More graphs, perhaps using a serious graphic system (Flot, perhaps).
- A "live" view showing who is visiting your blog Right Now
- Storing the exit time for each page, so reports on how long people spent on your site can be built
- More reports on average pages per visitor

Any further ideas will be gratefully received.