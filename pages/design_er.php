<?php
	if (!intval($_SESSION['current_schema'])) {
		fx_show_error_metabox(_('Please select Data Schema'));
		return;
	}

	global $fx_db;

    if (!get_fx_option('er_tmp_'.(int)$_SESSION['current_schema'])) $er_status = '';
    else $er_status = ' <sup>*</sup>';
?>



<div class="leftcolumn">
    <?php
    function entities_footer() {
        echo '<input type="hidden" id="system-elems" checked="checked">';
        echo "<div class='button green' onclick='add_type(". $_SESSION['current_schema'].")'>Add Type</div>";
    }

    $entities = array(
        'header' => array('content' => 'Entities'),
        'body' => array('id' => 'entities'),
        'footer' => array('function' => 'entities_footer')
    );

    fx_show_metabox($entities);

    function links_content () {
        ?>
        <div class="link_create" data-relation="1">
            <h3>One-to-One</h3>
        </div>
        <div class="link_create" data-relation="2">
            <h3>One-to-Many</h3>
        </div>
        <div class="link_create" data-relation="3">
            <h3>Many-to-One</h3>
        </div>
        <div class="link_create" data-relation="4">
            <h3>Many-to-Many</h3>
        </div>
        <?php
    }
    $links = array(
        'header' => array('content' => 'Relationships'),
        'body' => array('function'=> 'links_content', 'id' => 'links'),
        'footer' => array('hidden' => true)
    );

    fx_show_metabox($links);

    ?>

</div>
<div class="rightcolumn" id="ERDesigner">
    <?php
    function diagram_content() {?>
        <div id="toolContainer">
            <canvas id="Canvas" class="canvas loading" style="position: relative; min-height: 200px ;"></canvas>
            <div id="designer"></div>
        </div>
    <?php }

    $diagram = array(
        'header' => array('content' => 'ER Diagram<span id="er-status" style="color:#FF0000;"><?php echo $er_status; ?></span>'),
        'body' => array('function'=> 'diagram_content'),
        'footer' => array('id' => 'statusbar')
    );

    fx_show_metabox($diagram);
    ?>
    <form action="" method="post">
    	<input type="hidden" name="revert_changes"/>
        <input type="button" id="saveDiagram" class="button green" value="Save">
        <input type="button" id="revert" class="button red" value="Revert Changes">
        <input type="button" id="undo" class="button" value="Undo">
        <input type="button" id="redo" class="button" value="Redo">

        <select id="zoomControl">
            <option value="0.5">50%</option>
            <option value="0.75">75%</option>
            <option value="1" selected="selected">100%</option>
        </select>
	</form>
</div>

<script id="entity" type="text/html">
    <div class="entity button {{color}} {{classes}}" id="{{id}}">
        <span>{{display_name}}</span>
    </div>
</script>

<script>
    $(function() { window.ER = ERToolDesigner(); });
</script>
