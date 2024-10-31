=== RELATED POST with THUMBNAIL ===
Tags: image, resize, tag, post, related, post, correlation, similar
Contributors: Michele Gobbi
Author Website: http://www.dynamick.it
Plugin web page: http://www.dynamick.it/related-post-with-thumbnail-942.html 
Requires at least: 1.5
Tested up to: 2.3
Stable tag: trunk

Returns a list of the related entries based on active/passive keyword matches 
and show the first available image in the post.

== Description ==

Returns a list of the related entries based on active/passive keyword matches 
and show the first available image in the post. It can generate a custom sized
thumbnail. Based on an original plugin of Alexander Malov & Mike Lu 
(v. 2.02) 

== Installation ==

1. Download the .zip file and extract it
2. Upload the extrated folder (related-post-thumbnail/) to the WordPress plugins folder (wp-content/plugins/)
3. Activate the plugin from the WordPress back office panel
4. Run the SQL script from the plugin option menu
5. Customize the plugin in the option menu

== Syntax ==

The syntax is:

  related_posts($limit='', $len='', $before_title = '', $after_title = '', $before_post = '', $after_post = '', $show_pass_post = '', $show_excerpt = '')

where:

1. limit: how many related post to show
2. len: how many word have the excerpt
3. $before_title, $after_title, $before_post, $after_post: too simple to comment ;-)
4. $show_pass_post: show the password protected post
5. $show_excerpt: show the post excerpt