=== Actionable ===
Contributors: dzappone
Donate link: http://www.23systems.net/donate/
Tags: actionable, profile, actions, ajax, lists, users
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 0.8.3

Actionable is a plugin for WordPress that allows you to create a check list of action items that users can check off and track.

== Description ==

Actionable allows the creation of a categroized list of action items for users to check off and track.  It was originally developed for <a href="http://www.share350.com">Share 350.0 - a plan for regional sustainability</a> to help people track their actions and efforts to create a sustainable community.

== Installation ==

1. Extract actionable.zip to your `wp-content/plugins` directory.
2. In the admin panel under plugins activate Actionable.
3. View the Actionable admin panel under the Manage menu.
4. Add the text `<!--actionable form-->` to the page you want the plugin to appear.

At this time you must manually add categories and actions.  I am working to complete the admin panel of actionable to allow the addition of categories and actions from the admin panel.

You are able to view current actions and categories as well as some elementary statistics from the admin panel which resides under the manage menu.

== Screenshots ==

1. Actionable in Action

== Frequently Asked Questions ==

= Can you tell me how to add actions and categories? =

The easiest way is to use either phpMyAdmin or mySQL GUI tools to directly edit the database.  You should look for tables called `wp_actionable` and `wp_actionable_categories` (wp_ is the default WordPress database prefix - if you have changed this look for the table names with your prefix.)  Create some categories in `_actionable_categories` first then create some actions in `_actionable_actions` assigning `actionable_id` in `_actionable_categories` to `actionable_cat` in `_actionable_actions` for the appropriate action.

The next release will allow for category and action creation from the admin panel

= Can I add my own styles? =

Yes, just edit the file `wp-content/plugins/actionable/css/styles.css`.

== Changelog ==

= 0.8.3 =
* Updated animated collapse JavaScript to fix issue with jquery 1.3.2.
* Tested to work with WordPress 2.8.

= 0.8.2 =
* Fixed table creation bug - again
* Fixed absolute pathing - should work with blogs in subdirectories

= 0.8.0 =
* Initial release

= Notice =

Due to the current method of adding categories and actions you acknowledge that you are using at your own risk and I am not responsible for any data loss.