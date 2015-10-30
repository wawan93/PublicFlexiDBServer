<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";

	if ($_GET['schema_id'] || $_GET['schema']) {
		$schema = $_GET['schema_id']|$_GET['schema'];
	}
	
	$image_types = array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg' => 'image/jpeg' );
?>

<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

	<style type="text/css">
		.wrap		    { overflow-y: scroll; overflow-x: hidden; width:100%; height:400px; }
    	.item 		    { display:inline-block; position: static; width: 92px; height: 92px; text-align: center;}
		.item img	    { cursor:pointer; }
        .item img:hover   { transform:scale(2); -webkit-transform:scale(2);  -moz-transform:scale(2); -o-transform:scale(2); -ms-transform: scale(2); }
		.info		    { font-size:10px; text-align: center; }
        h2              { color: #595959; margin: 10px 5px 0; font-family: Arial;}
        .divider        { height: 3px; background: url('<?php echo URL ?>images/rightcolumn-bottom-divider.png') repeat-x center; padding: 5px 0; margin-bottom: 10px; }
        .divider > *    { height: 3px; float: left; width: 70px; }
        .left-grad      { background: url('<?php echo URL ?>images/divider-left-grad.png') no-repeat;}
        .right-grad     { float: right; background: url('<?php echo URL ?>images/divider-right-grad.png') no-repeat; }
    </style>
</head>
<body>
    <div class="wrap">
	<?php
        if (!$schema) {
            echo '<p style="text-align:center; font-size:24px; color:#ccc;">'._('Please select Data Schema').'</p>';
        }
        else {
            $img_dir = CONF_UPLOADS_DIR.'/schema_media/'.$schema.'/';
            $result = get_objects_by_type(TYPE_MEDIA_IMAGE, $schema);
            
            if (!is_fx_error($result) && $result) {
                foreach ($result as $img) {
                    $img_path = CONF_UPLOADS_DIR.'/'.TYPE_MEDIA_IMAGE.'/'.$img['object_id'].'/';
                    $img_url = CONF_UPLOADS_URL.TYPE_MEDIA_IMAGE.'/'.$img['object_id'].'/'.$img['image'];
                    $img_src = $img_path.$img['image'];
                    $size = getimagesize($img_src);
                    $thumb_size = getimagesize($img_path.'thumb_'.$img['image']);
					echo '
                    <div class="item">
                        <img src="'.$img_url.'" '.$thumb_size[3].' alt="'.$img['image'].'" data-path="'.$img_src.'">
                        <div class="info">'.$size[0].' x '.$size[1].'</div>
                    </div>';
                }
            }
			else {
				echo '
					<div>No images in current schema</div>
					<a target="_parent" href="'.URL.'schema_admin/schema_media">Schema media</a>
				';
			}
        }
    ?>
    </div>
</body>
</html>