<?php
/**
 * Plugin Name: PMPro Bulk Edit
 * Description: Allow membership level assignment using Bulk and Quick edit.
 * Author: Great H Master
 * Author URI: 
 */

function pmprobe_manage_posts_columns( $columns, $post_type)
{
	$post_types =  apply_filters('pmprobe_post_type_columns', array('post'));
		
	if (in_array($post_type, $post_types))
	{
		$columns[ 'pmpro_membership_levels' ] = 'Membership Levels';
	}
		
	return $columns;	
}
add_filter( 'manage_posts_columns', 'pmprobe_manage_posts_columns', 10, 2 );

function pmprobe_manage_pages_columns($columns)
{
	$columns[ 'pmpro_membership_levels' ] = 'Membership Levels';		
	return $columns;	
}
add_filter( 'manage_pages_columns', 'pmprobe_manage_pages_columns', 10, 2 );

function pmprobe_manage_posts_custom_column( $column_name, $post_id )
{
	global $wpdb;
	
	$sqlQuery = "SELECT membership_id, name FROM $wpdb->pmpro_memberships_pages LEFT JOIN $wpdb->pmpro_membership_levels ON $wpdb->pmpro_memberships_pages.membership_id = $wpdb->pmpro_membership_levels.id WHERE page_id = $post_id";
	$levels = $wpdb->get_results($sqlQuery, OBJECT);

	switch( $column_name )
	{
		case 'pmpro_membership_levels':
		
			echo '<div id="pmpro_membership_levels-' . $post_id . '"> ';
			   
			   foreach($levels as $level)
			   {
				   echo '<span id = "pmpro-level-'.$level->membership_id.'">';
				   echo $level->membership_id. "</span>-". $level->name."<br>";
				   
			   }
			   
			echo '</div>';
		break;		
	}
	
}
add_action( 'manage_posts_custom_column', 'pmprobe_manage_posts_custom_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'pmprobe_manage_posts_custom_column', 10, 2 );

function pmprobe_quick_edit_custom_box( $column_name, $post_type )
{
	global $wpdb;
	switch( $column_name )
	{
		case 'pmpro_membership_levels':

			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
			$levels = $wpdb->get_results($sqlQuery, OBJECT);?>
			<fieldset class="inline-edit-col-left">
				<div class="inline-edit-col">
					<label>
						<span class="title">Membership Levels</span>
					</label>
							
					<input type="hidden" name="pmpro_noncename" id="pmpro_noncename" value="<?php echo wp_create_nonce(plugin_basename(__FILE__) )?>" />		
							
					<div id ="pmpro_levels_quick_edit_checkboxes" class="checkbox_box" <?php if(count($levels) > 7) { ?>style="height: 100px; overflow: auto;"<?php } ?>><?php
						
						foreach($levels as $level)
						{ ?>	
							<div class="clickable"><input type="checkbox" id="pmproquickedit_<?php echo $level->id?>" name="page_levels[]" value="<?php echo $level->id?>" > <?php echo $level->name?></div> <?php
						}?>
					</div>	
				</div>
			</fieldset><?php
		break;			
	}
}
add_action( 'quick_edit_custom_box','pmprobe_quick_edit_custom_box', 10, 2 );

function pmprobe_bulk_edit_custom_box( $column_name, $post_type )
{
	global $wpdb;
	$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
	$levels = $wpdb->get_results($sqlQuery, OBJECT);?>
	<fieldset class="inline-edit-col-left">
		<div class="inline-edit-col">						
			<label>
					<span class="title">Membership Levels</span>
			</label>
			<select id="pmpro_bulk_edit_levels" name="pmpro_bulk_edit_levels">
				<option value="no"> <?php _e('— No Change —');?></option>
				<option value="yes"> <?php _e('Bulk Edit Levels');?></option>
			</select>				
			
			<input type="hidden" name="pmpro_noncename" id="pmpro_noncename" value="<?php echo wp_create_nonce(plugin_basename(__FILE__) )?>" />							
			<div id ="pmpro_levels_bulk_edit_checkboxes" class="checkbox_box" <?php if(count($levels) > 7) { ?>style="height: 100px; overflow: auto;"<?php } ?>>
				<div style="color:red;">WARNING: This will override existing level settings for ALL selected pages/posts</div><?php
							
				foreach($levels as $level)
				{ ?>	
					<div class="clickable"><input type="checkbox" id="pmprobulkedit_<?php echo $level->id?>" name="page_levels[]" value="<?php echo $level->id?>" > <?php echo $level->name?></div> <?php
				}?>
			</div>
		</div>
	</fieldset><?php	
}
add_action( 'bulk_edit_custom_box', 'pmprobe_bulk_edit_custom_box', 10, 2 );

function pmprobe_admin_print_scripts_edit()
{
	wp_enqueue_script( 'pmprobe_bulk_edit', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'pmpro-bulk-edit.js', array( 'jquery', 'inline-edit-post' ), '', true );
}
add_action( 'admin_print_scripts-edit.php', 'pmprobe_admin_print_scripts_edit' );

function pmprobe_page_save($post_id)
{
	global $wpdb;

	if(empty($post_id))
		return false;
	
	if (!empty($_POST['pmpro_noncename']) && !wp_verify_nonce( $_POST['pmpro_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	
	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;

	// Check permissions
	if(!empty($_POST['post_type']) && 'page' == $_POST['post_type'] )
	{
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	}
	else
	{
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	// OK, we're authenticated: we need to find and save the data	
	if(isset($_POST['pmpro_noncename']))
	{
		if(!empty($_POST['page_levels']))
			$mydata = $_POST['page_levels'];
		else
			$mydata = NULL;
			
		pmprobe_save_levels($post_id, $mydata);
	}
}
add_action('save_post', 'pmprobe_page_save');

function pmprobe_save_levels($post_id, $level_array)
{
	global $wpdb;
	
	$wpdb->query("DELETE FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '$post_id'");
	
	if(is_array($level_array))
	{
		foreach($level_array as $level)
			$wpdb->query("INSERT INTO {$wpdb->pmpro_memberships_pages} (membership_id, page_id) VALUES('" . intval($level) . "', '" . intval($post_id) . "')");
	}
}

function pmprobe_retrieve_levels_callback()
{
	global $wpdb;
	
	// The $_REQUEST contains all the data sent via ajax
	if ( isset($_REQUEST))
	{
		$post_id = $_REQUEST['post_id'];
		$sql = "SELECT membership_id FROM $wpdb->pmpro_memberships_pages WHERE page_id = ".$post_id;
		$results = $wpdb->get_results($sql, OBJECT_K);
		
		$levels = '';
	    
		foreach($results as $level)
		{
			$levels .= $level->membership_id.',';
		}

		echo $levels;     
	}
	die();
}
add_action( 'wp_ajax_pmprobe_retrieve_levels_callback', 'pmprobe_retrieve_levels_callback' );
 
function pmprobe_save_bulk_edit_callback()
{
	$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : NULL;
	$levels	= ( isset( $_POST[ 'levels' ] ) && !empty( $_POST[ 'levels' ] ) ) ? $_POST[ 'levels' ] : NULL;
	$change	= ( isset( $_POST[ 'change' ] ) && !empty( $_POST[ 'change' ] ) ) ? $_POST[ 'change' ] : NULL;
	
	if($change == 'yes')
	{
		foreach($post_ids as $post_id)
		{
			pmprobe_save_levels($post_id, $levels);
		}
	}

	die();
}
add_action( 'wp_ajax_pmprobe_save_bulk_edit_callback', 'pmprobe_save_bulk_edit_callback' );
