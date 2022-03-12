<?php

namespace RootBloom;

class Soil
{
    public function __construct()
    {
        $this->cleanDashboard();
        $this->cleanEditor();
        $this->cleanHead();
        $this->disableComments();
        $this->setupQueries();
        $this->setupThemeSupport();
    }

    protected function cleanDashboard()
    {
        define('DISALLOW_FILE_EDIT', true);
        define('DISABLE_FSE', true);

        remove_action('welcome_panel', 'wp_welcome_panel');
        remove_action('template_redirect', 'rest_output_link_header');
    }

    protected function cleanEditor()
    {
        remove_action('welcome_panel', 'wp_welcome_panel');

        // Disable Block Editor FullScreen mode & Welcome guide
        add_action('enqueue_block_editor_assets', function () {
            $script = "window.onload = function() { 
                wp.data && wp.data.select('core/edit-post').isFeatureActive('fullscreenMode') && wp.data.dispatch('core/edit-post').toggleFeature('fullscreenMode');
            }";
            wp_add_inline_script('wp-blocks', $script);
        });

        add_action('customize_register', function ($wp_customize) {
            $wp_customize->remove_section('custom_css');
        });
    }

    protected function cleanHead()
    {
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

        add_action('wp_head', function () {
            ob_start(function ($o) {
                $o = str_replace('class="yoast-schema-graph"', '', $o);
                return preg_replace('/\n?<.*?yoast seo plugin.*?>/mi', '', $o);
            });
        }, ~PHP_INT_MAX);

        remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    }

    protected function disableComments()
    {
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', '__return_empty_array', 10, 2);

        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu');
        }

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

        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('comments');
        });

        // Remove comments page in menu
        add_action('admin_menu', function () {
            remove_menu_page('edit-comments.php');
        });
    }

    protected function setupQueries()
    {
        add_action('pre_get_posts', function () {
            add_filter('posts_where', function ($where = '') {
                if (!is_single() && !is_admin()) {
                    $where .= " AND post_password = ''";
                }

                return $where;
            });
        });
    }

    protected function setupThemeSupport()
    {
        add_action('after_setup_theme', function () {
            /**
             * Disable full-site editing support.
             *
             * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
             */
            remove_theme_support('block-templates');

            /**
             * Disable the default block patterns.
             * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
             */
            remove_theme_support('core-block-patterns');

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

            add_filter('allowed_block_types_all', function ($block_editor_context, $editor_context) {
                if (empty($editor_context->post) || !in_array($editor_context->post->post_type, ['post', 'page'])) {
                    return [];
                }

                return [
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/file',
                    'core/audio',
                    'core/image',
                    'core/video',
                    'core/embed',
                    'core/columns',
                    'core/column',
                    'core/text-columns'
                ];
            }, 10, 2);
        });
    }
}