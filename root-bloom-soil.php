<?php

/**
 * Plugin Name:       Soil
 * Plugin URI:        https://rootandbloom.studio
 * Description:       Our rich and clean soil.
 * Version:           1.0
 * Requires PHP:      7.4
 * Author:            Root + Bloom Studio
 * Author URI:        https://rootandbloom.studio
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       root-bloom
 */

// clean json routes
add_filter('rest_endpoints', function ($endpoints) {
    unset($endpoints['/']);

    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }

    return $endpoints;
});

// clean dashboard
remove_action('welcome_panel', 'wp_welcome_panel');
remove_action('template_redirect', 'rest_output_link_header');

// clean up head
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');

remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
remove_action('wp_head', 'index_rel_link'); // Index link
remove_action('wp_head', 'wp_resource_hints');
remove_action('wp_head', 'feed_links'); // Display the links to the general feeds: Post and Comment Feed
remove_action('wp_head', 'feed_links_extra'); // Display the links to the extra feeds such as category feeds
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action('wp_head', 'parent_post_rel_link'); // Prev link
remove_action('wp_head', 'start_post_rel_link'); // Start link
remove_action('wp_head', 'adjacent_posts_rel_link'); // Display relational links for the posts adjacent to the current post.
remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'rest_output_link_wp_head');

// remove yoast helper classname 
add_action('wp_head', function () {
    ob_start(function ($o) {
        $o = str_replace('class="yoast-schema-graph"', '', $o);
        return preg_replace('/\n?<.*?yoast seo plugin.*?>/mi', '', $o);
    });
}, ~PHP_INT_MAX);

// set our logo in the editor instead of wordpress
add_action('admin_head', function () {
	echo '<style>
	.edit-post-header .edit-post-fullscreen-mode-close svg {display:none;}
	.edit-post-header .edit-post-fullscreen-mode-close::before {
		background-image: url("/wp-content/mu-plugins/root-bloom-soil/logo.svg");
		background-repeat: no-repeat;
		background-position: center center;
		background-size: contain;
		box-shadow: none;
	}

	.editor-styles-wrapper .edit-post-visual-editor__post-title-wrapper { margin: 0 0 2rem 0 !important; padding: 1rem; border-bottom: 1px solid currentColor; }
	.editor-styles-wrapper h1 { padding: 0.5rem; margin: 0; font-size: 1.5rem; }
	</style>';
});

remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
add_filter('rss_widget_feed_link', '__return_false');

// disable comments
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);
add_filter('comments_array', '__return_empty_array', 10, 2);
remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu');
add_filter('comments_open', '__return_false', ~PHP_INT_MAX);

foreach (get_post_types() as $type) {
    remove_post_type_support($type, 'comments');
    remove_post_type_support($type, 'trackbacks');
}

add_action('admin_init', function () {
    // Redirect any user trying to access comments page
    global $pagenow;

    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }

    // Remove comments metabox from dashboard
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});

// remove comments from admin bar
add_action('wp_before_admin_bar_render', function () {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
});

// remove comments page in menu
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

// modernize theme
add_action('after_setup_theme', function () {
    /**
     * Enable plugins to manage the document title.
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');
});

// parse a block template part via a custom helper
if (!function_exists('rb_get_block_template')) {
    function rb_get_block_template($part)
    {
        $template_part = get_block_template(get_stylesheet() . '//' . $part, 'wp_template_part');
        if (!$template_part || empty($template_part->content)) {
            return;
        }

        return $template_part->content;
    }
}

// disable google_gtag js while logged in
add_action('wp_print_scripts', function () {
    if (is_user_logged_in()) {
        wp_dequeue_script('google_gtagjs');
    }
}, 100);

// image changes
add_filter('big_image_size_threshold', function ($threshold) {
    return 4000;
}, 999, 1);

// don't resize gifs (so they stay animated)
add_filter('intermediate_image_sizes_advanced', function ($sizes, $metadata) {
    $filetype = wp_check_filetype($metadata['file']);
    if ($filetype['type'] === 'image/gif') {
        return [];
    }

    return $sizes;
}, 10, 2);

// allow svg upload
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

// set viewport height as root style
add_action('wp_head', function () {
    echo '<script type="text/javascript">
        const root = document.documentElement;
        root.style.setProperty(\'--rb-vp\', `${window . innerHeight}px`);

        const setSpacingVariables = () => {
            root.style.setProperty(\'--rb-vp\', `${window . innerHeight}px`);
        }

        window.addEventListener(\'load\', function() { setSpacingVariables(); });
        setSpacingVariables();
    </script>';
});

// create "backup" while developing for tailwind to compile
add_action('save_post', function ($post_id) {
    if (!str_contains(get_home_url(), 'localhost')) {
        return false;
    }

    $path = get_theme_file_path('.devcontainer/data/backup');
    if (!file_exists($path)) {
        return;
    }

    $post = get_post($post_id);

    if (str_contains($post->post_name, 'autosave') || str_contains($post->post_name, 'revision') || str_contains($post->post_name, 'trashed')) {
        return;
    }

    $path = sprintf('%s/wp-%s.html', $path, $post->post_name);
    file_put_contents($path, $post->post_content);
});