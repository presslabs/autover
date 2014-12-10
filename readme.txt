=== AutoVer ===
Contributors: PressLabs, olarmarius
Donate link: http://www.presslabs.com/
Tags: auto, automatic, pages, head, css, wp-enqueue, filter, javascript, script, style, ver, version, versioning, autover, presslabs
Requires at least: 3.5.1
Tested up to: 4.1
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically version your CSS and JS files.

== Description ==

Automatically version your CSS and JS files.

== Installation ==

= Installation =
1. Upload `autover.zip` to the `/wp-content/plugins/` directory;
2. Extract the `autover.zip` archive into the `/wp-content/plugins/` directory;
3. Activate the plugin through the 'Plugins' menu in WordPress.

= Usage =
Use your plugin from the `Tools->AutoVer` page;

== Frequently Asked Questions ==

= Why should I use this plugin? =
If you want to automatically version your CSS and JavaScript files, this will help to load your CSS and JS file into the cache memory only when you change the code from the files.

== Screenshots ==

1. Before activation

2. After activation


== Changelog ==

= 1.4 =
* add cron job to reset the lists
* remove `*.xcf` files
* formatted PHP code to WP std.
* use `preg_replace` to remove the query string
* fix `PHP Notice: Undefined variable: filetype`

= 1.3 =
* add file lists.

= 1.2 =
* fix error at 'filemtime' function.

= 1.1 =
* fix some settings error.

= 1.0 =
* start version on WP.

== Upgrade Notice ==

= 1.4. =
* Add cron job to reset the lists
* Remove `*.xcf` files
* Formatted PHP code to WP std.
* Use `preg_replace` to remove the query string
* Fix `PHP Notice: Undefined variable: filetype`

= 1.3 =
Add file lists.

= 1.2 =
Fix error at 'filemtime' function.

= 1.1 =
Fix some settings error.

= 1.0 =
Start version on WP.

