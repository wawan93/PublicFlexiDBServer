<?php

	$schema_count = _get_table_count(DB_TABLE_PREFIX."object_type_".TYPE_DATA_SCHEMA);
	$set_count = _get_table_count(DB_TABLE_PREFIX."object_type_".TYPE_DATA_SET);
	$type_count = _get_table_count(DB_TABLE_PREFIX."object_type_tbl");
	$website_count = _get_table_count(DB_TABLE_PREFIX."object_type_".TYPE_DFX_WP_SITE) + _get_table_count(DB_TABLE_PREFIX."object_type_".TYPE_DFX_GENERIC_WEBSITE);
	$subscriber_count = _get_table_count(DB_TABLE_PREFIX."object_type_".TYPE_SUBSCRIPTION);
	$applications = _get_table_count(DB_TABLE_PREFIX."object_type_".TYPE_APPLICATION);
	
	$object_count = 0;

	foreach ((array)get_objects_by_type(TYPE_DATA_SCHEMA) as $schema) {
		foreach ((array)get_schema_types($schema['object_id'], 'none') as $type){
			$object_count += _get_table_count(DB_TABLE_PREFIX."object_type_".$type['object_type_id']);
		}
	}

	$current_version = get_fx_option('flexidb_version');
		
	if (defined('CONF_ENABLE_UPDATES') && CONF_ENABLE_UPDATES) {		
		$update_options = get_fx_option('update_options', array('new_flexidb_version'=>0));
		$new_version = $update_options['new_flexidb_version'];
	}
	 
	$rss_1 = get_fx_option('rss_options_1');
	$rss_2 = get_fx_option('rss_options_2');
?>

<div class="leftcolumn wide">
    <div class="metabox">
        <div class="header">
        	<div class="icons home"></div>
            <h1><?php echo _('Summary') ?></h1>
        </div>
        <div class="content summary">
        	<table>
            <tr>
            	<td><span class="count"><?php echo $schema_count ?></span> <?php echo _('Data Schemas') ?></td>
                <td><span class="count"><?php echo $website_count ?></span> <?php echo _('Websites') ?></td>
            </tr>
            <tr>
            	<td><span class="count"><?php echo $set_count ?></span> <?php echo _('Data Sets') ?></td>
                <td><span class="count"><?php echo $subscriber_count ?></span> <?php echo _('Subscribers') ?></td>
            </tr>
            <tr>
            	<td><span class="count"><?php echo $object_count ?></span> <?php echo _('Objects') ?></td>
                <td><span class="count"><?php echo $applications ?></span> <?php echo _('Applications') ?></td>
            </tr>
            <tr>
            	<td><span class="count"><?php echo $type_count ?></span> <?php echo _('Types') ?></td>
                <td></td>
            </tr>
            <tr>
            	<td colspan="2"><hr/></td>
            </tr>
            <tr>
            	<td><strong>DFX Server <?php echo _('version').' '.$current_version ?></strong></td>
                <td>
					<?php if(version_compare($current_version,$new_version,'<')): ?>
                    <a class="button green" href="<?php echo URL ?>settings/settings_update"><?php echo _('Update to').' '.$new_version ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            </table>
            <p>&nbsp;</p>          
        </div>
    </div>
    
	<?php if ($_SESSION['current_schema']): ?>
    
    <?php
		global $data_schema;
		$app_group = get_schema_app_group($_SESSION['current_schema']);
		$schema_sfx_links = get_object_links(TYPE_DATA_SCHEMA, $_SESSION['current_schema'], TYPE_SUBSCRIPTION);	
	?>
    
    <div class="metabox">
        <div class="header">
        	<div class="icons home"></div>
            <h1><?php echo _('Schema Prospects') ?></h1>
        </div>
        <div class="content summary">
        	<table>
            <tr>
            	<td>
        			<span class="count"><?php echo _('Channel') ?>:</span>
                    <?php echo !$data_schema['channel'] ? '<a class="green" href="'.URL.'schema_admin/schema_channel">Create</a>' : '<a class="blue" href="'.URL.'/schema_channel">Edit</a>'; ?>
                </td>
            </tr>
            <tr>
            	<td>
        			<span class="count"><?php echo _('Application') ?>:</span>
					<?php echo !$app_group ? '<a class="green" href="'.URL.'app_editor/app_group">Create</a>' : '<a class="blue" href="'.URL.'app_editor/app_release_manager">Edit</a>'; ?>
                </td>
            </tr>            
            <tr>
            	<td>
        			<span class="count"><?php echo _('Data Sets').': '.count(get_objects_by_type(TYPE_DATA_SET, $_SESSION['current_schema'])) ?></span>
                
                </td>
            </tr>
            <tr>
            	<td>
        			<span class="count"><?php echo _('Subscriptions').': '.(!is_fx_error($schema_sfx_links) ? count($schema_sfx_links) : 'error') ?></span>
                </td>
            </tr>
            </table>   
        </div>
    </div>
    
    <?php endif; ?>
    
    <div class="metabox">
        <div class="header">
        	<div class="icons home"></div>
            <h1><?php echo $rss_1['rss_title'] ? $rss_1['rss_title'] : 'FlexiDB RSS' ?></h1>
        </div>
        <div class="content rss">
        <?php 
			$rssdata = simplexml_load_file($rss_1['rss_url']);
			$count = 0;

			foreach ($rssdata->channel->item as $item)
			{
			   echo '<div class="rss"><a href="'.$item->link.'">'.$item->title."</a>";
			  
			   if ($rss_1['rss_show_date'] && $item->pubDate) {
				   echo "&nbsp;&nbsp;<span class='date'>".date("M j, Y",strtotime($item->pubDate))."</span>";
			   }
			   if ($rss_1['rss_show_content'] && $item->description) {
				   echo "<div class='content'>".$item->description."</div>";
			   }
			   if ($rss_1['rss_show_author'] && $item->author) {
				   echo "<div class='author'>posted by ".$item->author."</div>";
			   }
			   
			   echo '</div>';
			   
			   $count++;
			   if ($count == $rss_1['rss_items']) {
				   break;
			   }
			} 
		?>
        </div>
    </div>    
    
</div>

<div class="rightcolumn">
    <div class="metabox">                
        <div class="header">
        	<div class="icons home"></div>
            <h1><?php echo $rss_2['rss_title'] ? $rss_2['rss_title'] : 'FlexiDB RSS' ?></h1>
        </div>
        <div class="content">
        <?php 

			$rssdata = simplexml_load_file($rss_2['rss_url']);
			
			$count = 0;
			
			foreach ($rssdata->channel->item as $item)
			{		
				echo '<div class="rss"><a href="'.$item->link.'">'.$item->title."</a>";
				
				if ($rss_2['rss_show_date'] && $item->pubDate) {
					echo "&nbsp;&nbsp;<span class='date'>".date("M j, Y",strtotime($item->pubDate))."</span>";
				}
				if ($rss_2['rss_show_content'] && $item->description) {
					echo "<div class='content'>".$item->description."</div>";
				}
				if ($rss_2['rss_show_author'] && $item->author) {
					echo "<div class='author'>posted by ".$item->author."</div>";
				}
				
				echo '</div>';
				
				$count++;
				if ($count == $rss_2['rss_items']) {
					break;
				}
			}
		?>
        </div>
    </div>
</div>