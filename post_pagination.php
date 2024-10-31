<?php
/**
 * @package Post_pagination
 * @version 1.0
 */
/*
Plugin Name: Post Pagination
Plugin URI: http://www.nethuesindia.com
Description: Apply pagination to the posts pages in admin section.
Author: Nethuesindia
Version: 1.0
Plugin URI: http://www.nethuesindia.com
*/
session_start();
// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
/**
 * Indicates that a clean exit occured. Handled by set_exception_handler
 */
if (!class_exists('E_Clean_Exit')) {
	class E_Clean_Exit extends RuntimeException
	{
	}
}
class AdminPerPageLimits
{
	var $admin_options_name 	= 'c2c_admin_per_page_limits';
	var $base_field_name 		= 'admin_per_page_limit';
	var $possible_limits 		= array(25, 50, 100, 250);
	var $config 				= array(
										'comments_limit' => 20, 
										'pages_limit' => 20,
										'posts_limit' => 25
									);
	// Internal use
	var $field_name 	= '';
	var $prompt 		= '';
	var $options 		= array();
	var $type 			= '';
	var $js_before 		= '';

	function AdminPerPageLimits()
	{
		global $pagenow;

		if ( !is_admin() || !in_array($pagenow, array('edit.php')))
			return;
 
		if ( 'edit.php' == $pagenow )
		{
			if($_REQUEST['post_type'] == 'post' || $_REQUEST['post_type'] == '') {

				$this->type = 'posts';
				$this->js_before = '.bottom .tablenav-pages';
	
				$this->field_name 	= $this->base_field_name . '_' . $this->type;
				$this->prompt 		= __('%s ');
				$this->setting_name = $this->type . '_limit';
		
				add_action('admin_init', array(&$this, 'maybe_save_options'));
				add_action('admin_footer', array(&$this, 'add_js'));
				add_action('post_limits', array(&$this, 'admin_post_limit'));
			}
		}
	}

	function add_js()
	{
		$options 	= $this->get_options();
		$input 		= "<div class='alignleft actions'><b>Records Per Page</b> <select id='{".$this->field_name."}' name='{".$this->field_name."}' style='float:none;' onchange='changePostPaging(this.value);'>";
		foreach ($this->possible_limits as $limit)
		{
			$checked 	= ($options[$this->setting_name] == $limit ? ' selected=\\"selected\\"' : '');
			$input 		.= "<option value='$limit'$checked>" . sprintf($this->prompt, $limit) . "</option>";
		}
		$input .= "</select></div>";
		
		echo '<script type="text/javascript">
				jQuery(document).ready(function($)
				{
					$("'.$this->js_before.'").before("'.$input.'");
				});
				
				function changePostPaging(getSelectedValue) {
					var getCurrentParams	= document.getElementsByName("_wp_http_referer")[0].value;
					var findPosition		= getCurrentParams.indexOf("admin_per_page_limit_posts=");
					if(findPosition == -1)
					{
						var findParamPosition	= getCurrentParams.indexOf("?");
						if(findParamPosition == -1)
						{
							replacedURL		= getCurrentParams + "?admin_per_page_limit_posts="+getSelectedValue;
						}
						else
						{
							replacedURL		= getCurrentParams + "&admin_per_page_limit_posts="+getSelectedValue;
						}
					}
					else
					{
						var oldValue = "'.$options[$this->setting_name].'";
						var valueWithReplace	= "admin_per_page_limit_posts="+getSelectedValue;
						var valueToReplace		= "admin_per_page_limit_posts='.$options[$this->setting_name].'";
						var replacedURL			= getCurrentParams.replace(valueToReplace, valueWithReplace);
					}
					window.location.href	= replacedURL;
				}
			</script>';
	}
	
	/*
	function changePostPaging(getSelectedValue)
	{
		alert(getSelectedValue);
		var getCurrentParams	= document.getElementsByName("_wp_http_referer")[0].value;
		var findPosition		= getCurrentParams.indexOf("admin_per_page_limit_posts=");
		if(findPosition == -1)
		{
			var findParamPosition	= getCurrentParams.indexOf("?");
			if(findParamPosition == -1)
			{
				replacedURL		= getCurrentParams + "?admin_per_page_limit_posts="+getSelectedValue;
			}
			else
			{
				replacedURL		= getCurrentParams + "&admin_per_page_limit_posts="+getSelectedValue;
			}
		}
		else
		{
			var valueWithReplace	= "admin_per_page_limit_posts="+getSelectedValue;
			var valueToReplace		= "admin_per_page_limit_posts={'.$options[$this->setting_name].'}";
			var replacedURL			= getCurrentParams.replace(valueToReplace, valueWithReplace);
		}
		alert(replacedURL);
		window.location.href	= "edit.php?admin_per_page_limit_posts="+getSelectedValue;
	}
	*/

	function get_options() {
		if ( !empty($this->options) ) return $this->options;
		$existing_options = get_user_option($this->admin_options_name);
		$this->options = wp_parse_args($existing_options, $this->config);
		return $this->options;
	}

	function maybe_save_options()
	{
		$user 	= wp_get_current_user();
		if ( isset($_GET[$this->field_name]) )
		{
			$options 						= $this->get_options();
			$options[$this->setting_name] 	= attribute_escape($_GET[$this->field_name]);
			update_user_option($user->ID, $this->admin_options_name, $options);
			$this->options = $options;
		}
	}

	function admin_post_limit($sql_limit)
	{
		if ( !$sql_limit || !is_admin() )
			return $sql_limit;
			
		$options 					= $this->get_options();
		list($offset, $old_limit) 	= explode(',', $sql_limit, 2);
		$limit 						= $options[$this->setting_name];
		if (empty($limit))
			return $sql_limit;
		
		// Deal with possible paging
		if (is_paged())
		{
			global $wp_query;
			$offset 	= absint($wp_query->query_vars['offset']);
			$page 		= absint($wp_query->query_vars['paged']);
			if (empty($page))
				$page 	= 1;

			if (empty($offset))
			{
				$offset 	= ($page - 1) * $limit;
			}
			else
			{
				$offset 	= absint($offset);
			}
			$offset 	= "LIMIT $offset";
		}
		
		global $wp_query;
		$wp_query->query_vars['posts_per_page'] 	= $limit;

		return ($limit ? "$offset, $limit" : '');
	}
}
new AdminPerPageLimits();