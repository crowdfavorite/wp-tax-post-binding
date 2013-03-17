<?php
/**
 * Plugin Name: CF Taxonomy Post Type Binding
 * Plugin URI: http://crowdfavorite.com
 * Description: Provides extended functionality for taxonomies such as post meta and featured image
 * 	through creating a custom post type.
 * Version: 1.1
 * Author: Crowd Favorite
 * Author URI: http://crowdfavorite.com
 */

if (!defined('CF_TAX_POST_BINDING')) {

define('CF_TAX_POST_BINDING', true);

load_plugin_textdomain('cf-tax-post-binding');

function cftpb_get_post($term_id, $taxonomy) {
	return cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
}

function cftpb_get_term_meta($term_id, $taxonomy, $meta_key, $single = false) {
	$return_val = '';
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = get_post_meta($term_post->ID, $meta_key, $single);
	}
	return $return_val;
}

function cftpb_term_the_meta($term_id, $taxonomy) {
	global $post;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$pre_post_state = $post;
		$post = $term_post;
		the_meta();
		$post = $pre_post_state;
	}
}

function cftpb_add_term_meta($term_id, $taxonomy, $meta_key, $meta_value, $unique = false) {
	$return_val = false;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = add_post_meta($term_post->ID, $meta_key, $meta_value, $unique);
	}
	return $return_val;
}

function cftpb_update_term_meta($term_id, $taxonomy, $meta_key, $meta_value, $prev_value = null) {
	$return_val = false;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = update_post_meta($term_post->ID, $meta_key, $meta_value, $prev_value);
	}
	return $return_val;
}

function cftpb_delete_term_meta($term_id, $taxonomy, $meta_key, $meta_value = null) {
	$return_val = false;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = delete_post_meta($term_post->ID, $meta_key, $meta_value);
	}
	$return_val;
}

function cftpb_get_term_custom($term_id, $taxonomy) {
	$return_val = array();
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = get_post_custom($term_post->ID);
	}
	return $return_val;
}

function cftpb_get_term_custom_values($term_id, $taxonomy, $key) {
	$return_val = null;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = get_post_custom_values($key, $term_post->ID);
	}
	return $return_val;
}

function cftpb_get_term_custom_keys($term_id, $taxonomy) {
	$return_val = null;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = get_post_custom_keys($term_post->ID);
	}
	return $return_val;
}

function cftpb_has_term_thumbnail($term_id, $taxonomy) {
	$return_val = false;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = has_post_thumbnail($term_post->ID);
	}
	return $return_val;
}

function cftpb_the_term_thumbnail($term_id, $taxonomy, $size = 'post-thumbnail', $attr = '') {
	global $post;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$old_post_state = $post;
		$post = $term_post;
		the_post_thumbnail($size, $attr);
		$post = $old_post_state;
	}
}

function cftpb_get_term_thumbnail_id($term_id, $taxonomy) {
	$return_val = null;
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = get_post_thumbnail_id($term_post->ID);
	}
	return $return_val;
}

function cftpb_get_the_term_thumbnail($term_id, $taxonomy, $size = 'post-thumbnail', $attr = '') {
	$return_val = '';
	$term_post = cf_taxonomy_post_type_binding::get_term_post($term_id, $taxonomy);
	if (!empty($term_post) && !is_wp_error($term_post)) {
		$return_val = get_the_post_thumbnail($term_post->ID, $size, $attr);
	}
	return $return_val;
}

function cftpb_get_term_id($tax_slug, $post_id = null) {
	if (is_null($post_id)) {
		global $post;
		$post_id = $post->ID;
	}
	return get_post_meta($post_id, '_cf-tax-post-binding_'.$tax_slug, true);
}

class cf_taxonomy_post_type_binding {
	private static $taxonomies;
	private static $current_term_post;
	private static $term_before;

	public static function on_wp_loaded() {
		$configs = apply_filters('cftpb_configs', array());
		if (!is_array($configs)) {
			trigger_error(__('Invalid CF Extended Taxonomy configurations. Plugin contents will not be loaded.', 'cf-tax-post-binding'), E_USER_WARNING);
			return;
		}
		else if (!empty($configs)) {
			foreach ($configs as $config) {
				$tax_name = '';
				$post_type = '';
				
				// Validate taxonomy setting
				if (empty($config['taxonomy'])) {
					trigger_error(__('A CF Extended Taxonomy configuration is missing its taxonomy. That configuration has been ignored.', 'cf-tax-post-binding'), E_USER_WARNING);
					continue;
				}
				if (is_string($config['taxonomy'])) {
					$tax_name = $config['taxonomy'];
				}
				else if (is_array($config['taxonomy'])) {
					$args = count($config['taxonomy']);
					if (!$args == 3) {
						trigger_error(sprintf(_n('CF Extended Taxonomy taxonomy configuration requires %d parameter. A configuration has been ignored.', 'CF Extended Taxonomy taxonomy configuration requires %d parameters. A configuration has been ignored.', $args, 'cf-tax-post-binding'), $args), E_USER_WARNING);
						continue;
					}
					else if (!is_string($config['taxonomy'][0])) {
						trigger_error(__('CF Extended Taxonomy config taxonomy parameter 1 must be a string. Ignoring this configuration.', 'cf-tax-post-binding'), E_USER_WARNING);
						continue;
					}
						else if (!is_array($config['taxonomy'][1])) {
						$config['taxonomy'][1] = array($config['taxonomy'][1]);
					}
					if (taxonomy_exists($config['taxonomy'][0])) {
						trigger_error(sprintf(__('The "%s" taxonomy is already registered. Assuming use of existing taxonomy.', 'cf-tax-post-binding'), $config['taxonomy'][0]), E_USER_WARNING);
						$tax_name = $config['taxonomy'][0];
					}
				}
				
				// Validate post type setting
				if (empty($config['post_type'])) {
					trigger_error(__('A CF Extended Taxonomy configuration is missing its post type. That configuration has been ignored.', 'cf-tax-post-binding'), E_USER_WARNING);
					continue;
				}
				if (is_array($config['post_type'])) {
					$args = count($config['post_type']);
					if (!$args == 2) {
						trigger_error(sprintf(_n('CF Extended Taxonomy post_type configuration requires %d parameter. A configuration has been ignored.', 'CF Extended Taxonomy post_type configuration requires %d parameters. A configuration has been ignored.', $args, 'cf-tax-post-binding'), $args), E_USER_WARNING);
						continue;
					}
					else if (!is_string($config['post_type'][0])) {
						trigger_error(__('CF Extended Taxonomy config post_type parameter 1 must be a string. Ignoring this configuration.', 'cf-tax-post-binding'), E_USER_WARNING);
						continue;
					}
					if (post_type_exists($config['post_type'][0])) {
						trigger_error(sprintf(__('The "%s" post type is already registered. Ignoring this configuration.', 'cf-tax-post-binding'), $config['post_type']['name']), E_USER_WARNING);
						continue;
					}
					else if (empty($config['post_type'][0])) {
						trigger_error(__('A configuration is missing the required first parameter to create a custom post type. That configuration has been ignored.'), E_USER_WARNING);
						continue;
					}
				}
				else {
					trigger_error(__('CF Extended Taxonomy config post_type parameter must be an array. Ignoring this configuration.', 'cf-tax-post-binding'), E_USER_WARNING);
					continue;
				}
				
				// Register the custom post type if it doesn't exist and information was passed to create it.
				if (empty($post_type) && is_array($config['post_type'])) {
					global $CFTPB_ENABLED_POST_TYPES;
					$CFTPB_ENABLED_POST_TYPES[] = $config['post_type'][0];
					if (!empty($tax_name)) {
						if (!isset($config['post_type'][1]['hierarchical'])) {
							$config['post_type'][1]['hierarchical'] = is_taxonomy_hierarchical($tax_name);
						}
						else if (is_taxonomy_hierarchical($tax_name) != $config['post_type'][1]['hierarchical']) {
							trigger_error(sprintf(__('Post type "%1$s" must have matching hierarchical value to taxonomy "%2$s". Ignoring configuration.'), $config['post_type'][0], $tax_name), E_USER_WARNING);
							continue;
						}
					}
					else {
						$tax_hierarchical = isset($config['taxonomy'][1]['hierarchical']) ? $config['taxonomy'][1]['hierarchical'] : false;
						$post_hierarchical = isset($config['post_type'][1]['hierarchical']) ? $config['post_type'][1]['hierarchical'] : false;
						if ($post_hierarchical != $tax_hierarchical) {
							trigger_error(sprintf(__('Post type "%1$s" must have matching hierarchical value to taxonomy "%2$s". Ignoring configuration.'), $config['post_type'][0], $config['taxonomy'][0]), E_USER_WARNING);
							continue;
						}
					}
					register_post_type($config['post_type'][0], $config['post_type'][1]);
					do_action('cftpb_register_post_type', $config['post_type'][0], $config);
					$post_type = $config['post_type'][0];
				}
				
				if (empty($tax_name) && is_array($config['taxonomy'])) {
					if (!in_array($post_type, $config['taxonomy'][1])) {
						$config['taxonomy'][1][] = $post_type;
					}
					register_taxonomy($config['taxonomy'][0], $config['taxonomy'][1], $config['taxonomy'][2]);
					do_action('cftpb_register_taxonomy', $config['taxonomy'][0], $config);
				}
				
				self::$taxonomies[$tax_name] = array(
					'post_type' => $post_type,
					'slave_title_editable' => (isset($config['slave_title_editable'])) ? $config['slave_title_editable'] : false,
					'slave_slug_editable' => (isset($config['slave_slug_editable'])) ? $config['slave_slug_editable'] : false
				);
			}
		}
	}
	
	public static function on_admin_head() {
		global $CFTPB_ENABLED_POST_TYPES;
		if (!empty($CFTPB_ENABLED_POST_TYPES)) {
			$sel = array();
			foreach ($CFTPB_ENABLED_POST_TYPES as $post_type) {
				$sel[] = 'a[href*="post-new.php?post_type='.$post_type.'"]';
			}
		}
?>
<script type="text/javascript">
jQuery(function ($) {
	$('<?php echo implode(', ', $sel); ?>').remove();
});
</script>
<?php
	}
	
	public static function on_admin_head_post() {
		global $current_screen;
		$connection_settings = array();
		foreach (self::$taxonomies as $record) {
			if ($record['post_type'] == $current_screen->post_type) {
				$connection_settings = $record;
				break;
			}
		}
		if (empty($connection_settings)) {
			return;
		}
?>
<style type="text/css">
.add-new-h2,
#minor-publishing,
#delete-action,
#publishing-action #ajax-loading {
	display: none;
}
#major-publishing-actions #publishing-action {
	float: left;
}
#title.disabled {
	border: 0 !important;
	margin: 0 !important;
	padding: 0 !important;
}
#edit-slug-box {
	padding-left: 0 !important;
}
</style>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.add-new-h2, #minor-publishing, #delete-action').remove();
	$('select#parent_id').addClass('disabled').prop('disabled', true);
<?php
		if (!$connection_settings['slave_title_editable']) {
?>
	$('input[name="post_title"]').addClass('disabled').prop('disabled', true);
<?php
		}
		if (!$connection_settings['slave_slug_editable']) {
?>
	$('input[name="post_name"]').addClass('disabled').prop('disabled', true);
	$('a.edit-slug').remove();
<?php
		}
?>
});
</script>
<?php
	}
	
	public static function on_admin_head_edit() {
		global $current_screen;
		$connection_settings = array();
		foreach (self::$taxonomies as $record) {
			if ($record['post_type'] == $current_screen->post_type) {
				$connection_settings = $record;
				break;
			}
		}
		if (empty($connection_settings)) {
			return;
		}
?>
<style type="text/css">
div.actions,
.add-new-h2,
.row-actions .inline,
.row-actions .trash {
	display: none;
}
</style>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('div.actions, .add-new-h2, .row-actions .inline, .row-actions .trash').remove();
});
</script>
<?php
	}
	
	public static function on_edit_term($term_id, $tt_id, $taxonomy) {
		self::$term_before = get_term($term_id, $taxonomy);
	}
	
	public static function on_edited_term($term_id, $tt_id, $taxonomy) {
		if (!self::supports($taxonomy)) {
			return;
		}
		$post = self::get_term_post($term_id, $taxonomy);
		$term = get_term($term_id, $taxonomy);
		$connection_settings = self::$taxonomies[$taxonomy];
		if (empty($term) || is_wp_error($term)) {
			trigger_error(sprintf(__('Could not retrieve term "%1$d" for taxonomy "%1$s"', 'cf-tax-post-binding'), $term_id, $taxonomy), E_USER_WARNING);
			return;
		}
		if (is_wp_error($post)) {
			trigger_error(sprintf(__('Error retrieving post for term "%1$d" in taxonomy "%2$s"', 'cf-tax-post-binding'), $term_id, $taxonomy), E_USER_WARNING);
			return;
		}
		else if (!empty($post)) {
			// Update the post
			$old_term = self::$term_before;
			$update_post['ID'] = $post->ID;
			if ($connection_settings['slave_title_editable']) {
				$update_post['post_title'] = ($old_term->name == $post->post_title) ? $term->name : $post->post_title;
			}
			else {
				$update_post['post_title'] = $term->name;
			}
			if ($connection_settings['slave_slug_editable']) {
				$update_post['post_name'] = ($old_term->slug == $post->post_name) ? $term->slug : $post->post_name;
			}
			else {
				$update_post['post_name'] = $term->slug;
			}
			if (!empty($term->parent)) {
				// We need to set the post parent
				$parent_post = self::get_term_post($term->parent, 'category');
				if (!empty($parent_post) && !is_wp_error($parent_post)) {
					$update_post['post_parent'] = $parent_post->ID;
				}
			}
			wp_update_post($update_post);
		}
		else {
			// Create a new post.
			$post_type = self::supports($taxonomy);
			$insert_post = array(
				'post_type' => $post_type,
				'post_status' => 'publish',
				'post_title' => $term->name,
				'post_name' => $term->slug,
			);
			if (!empty($term->parent)) {
				// We need to set the post parent
				$parent_post = self::get_term_post($term->parent, 'category');
				if (!empty($parent_post) && !is_wp_error($parent_post)) {
					$insert_post['post_parent'] = $parent_post->ID;
				}
			}
			$post_id = wp_insert_post($insert_post);
			if (!empty($post_id) && !is_wp_error($post_id)) {
				update_post_meta($post_id, '_cf-tax-post-binding_'.$taxonomy, $term_id);
			}
		}
		self::$term_before = null;
	}
	
	public static function on_edited_term_taxonomies($tt_ids) {
		global $wpdb;
		if (!empty($tt_ids) && is_array($tt_ids)) {
			$tt_records = $wpdb->get_results('SELECT term_id, taxonomy, parent FROM '.$wpdb->term_taxonomy.' WHERE term_taxonomy_id IN ('.implode(',', $tt_ids).')', ARRAY_A);
			if (!empty($tt_records) && !is_wp_error($tt_records)) {
				$parent_post = self::get_term_post($tt_records[0]['parent'], $tt_records[0]['taxonomy']);
				if (empty($parent_post) || is_wp_error($parent_post)) {
					return;
				}
				foreach ($tt_records as $record) {
					$my_post = self::get_term_post($record['term_id'], $record['taxonomy']);
					if (!empty($my_post) && !is_wp_error($my_post)) {
						$update_record = array();
						$update_record['ID'] = $my_post->ID;
						$update_record['post_parent'] = $parent_post->ID;
						wp_update_post($update_record);
					}
				}
			}
		}
	}
	
	public static function on_delete_term($term_id, $tt_id, $taxonomy) {
		$post = self::get_term_post($term_id, $taxonomy);
		if (is_wp_error($post)) {
			trigger_error(sprintf(__('Error retrieving post for term "%1$d" in taxonomy "%2$s"', 'cf-tax-post-binding'), esc_html($term_id), esc_html($taxonomy)), E_USER_WARNING);
			return;
		}
		else if (empty($post)) {
			trigger_error(sprintf(__('Could not find post for term "%1$s" in taxonomy "%2$s"', 'cf-tax-post-binding'), esc_html($term_id), esc_html($taxonomy)), E_USER_WARNING);
			return;
		}
		else {
			wp_delete_post($post->ID, true);
		}
	}
	
	public static function on_tag_row_actions($actions, $tag) {
		global $taxonomy, $tax;
		$post = self::get_term_post($tag->term_id, $taxonomy);
		if (empty($post) || is_wp_error($post)) {
			return $actions;
		}
		if (empty($actions['term-post'])) {
			$actions['term-post'] = '';
		}
		if (current_user_can($tax->cap->edit_terms)) {
			$actions['term-post'] .= '<a href="'.esc_url(get_edit_post_link($post->ID)).'">'.__('Edit Term Post', 'cf-tax-post-binding').'</a> | ';
		}
		$actions['term-post'] .= '<a href="'.esc_url(get_permalink($post->ID)).'">'.__('View Term Post', 'cf-tax-post-binding').'</a>';
		return $actions;
	}
	
	public static function get_term_post($term_id, $taxonomy) {
		$return_val = null;
		if (self::$current_term_post && 
			self::$current_term_post['term_id'] == $term_id &&
			self::$current_term_post['taxonomy'] == $taxonomy
		) {
			$return_val = self::$current_term_post['post'];
		}
		else if ($tax_post_type = self::supports($taxonomy)) {
			$posts = get_posts(array(
				'posts_per_page' => 1,
				'post_type' => $tax_post_type,
				'post_status' => 'any',
				'meta_query' => array(
					array(
						'key' => '_cf-tax-post-binding_'.$taxonomy,
						'value' => $term_id,
						'compare' => '=',
						'type' => 'NUMERIC'
					)
				)
			));
			if (!empty($posts)) {
				if (is_wp_error($posts)) {
					$return_val = $posts;
				}
				else {
					self::$current_term_post = array(
						'term_id' => $term_id,
						'taxonomy' => $taxonomy,
						'post' => $posts[0]
					);
					$return_val = self::$current_term_post['post'];
				}
			}
		}
		return $return_val;
	}
	
	public static function supports($taxonomy) {
		return isset(self::$taxonomies[$taxonomy]) ? self::$taxonomies[$taxonomy]['post_type'] : null;
	}
}
add_action('wp_loaded', 'cf_taxonomy_post_type_binding::on_wp_loaded');
add_action('admin_head', 'cf_taxonomy_post_type_binding::on_admin_head');
add_action('admin_head-post.php', 'cf_taxonomy_post_type_binding::on_admin_head_post');
add_action('admin_head-edit.php', 'cf_taxonomy_post_type_binding::on_admin_head_edit');
add_action('created_term', 'cf_taxonomy_post_type_binding::on_edited_term', 10, 3);
add_action('edit_term', 'cf_taxonomy_post_type_binding::on_edit_term', 10, 3);
add_action('edited_term', 'cf_taxonomy_post_type_binding::on_edited_term', 10, 3);
add_action('edited_term_taxonomies', 'cf_taxonomy_post_type_binding::on_edited_term_taxonomies', 10, 1);
add_action('delete_term', 'cf_taxonomy_post_type_binding::on_delete_term', 10, 3);
add_filter('tag_row_actions', 'cf_taxonomy_post_type_binding::on_tag_row_actions', 10, 2);

} // end loaded check
