<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
    <?php
		global $fx_main_menu, $fx_server_menu;
		if ($item = $fx_main_menu -> get_menu_item(PAGE, FIRST_PARAM) || $item = $fx_server_menu -> get_menu_item(PAGE, FIRST_PARAM)) {
			$title = ' / '.$item['header'];
		}
	?>
	<title>FlexiDB Server - <?php echo $_SERVER['SERVER_NAME'].$title ?></title>
    <?php do_actions('fx_print_styles'); ?>

    <?php $args = array(
        'schema_id' => $_SESSION['current_schema'],
        'set_id' => $_SESSION['current_set'],
        'session_page' => PAGE,
        'session_cat' => FIRST_PARAM,
        'site_url' => CONF_SITE_URL
    ); ?>

    <script type="text/javascript">
        (function() {
            var data = '<?php echo(json_encode($args)); ?>';
            window.session = <?php echo(json_encode($_SESSION)); ?>;

            if( typeof window.flexiweb === 'undefined' )
                window.flexiweb = {};

            var constants = JSON.parse(data);
            for (var i in constants) {
                window.flexiweb[i] = constants[i];
            }
        })();
    </script>
    <?php do_actions('fx_print_scripts', 'header'); ?>
    <?php do_actions('fx_print_scripts', 'custom'); ?>
</head>
<body>
    <!-- HEADER -->
    <div id="header">
        <div class="logo head-divider">
            <a href="<?php echo URL ?>" id="logo"></a>
        </div>
        <div class="dropdowns head-divider">
            <form action="" method="post">
                <input type="hidden" name="set_fx_dir"/>
                <select name="current_schema" id="current_schema" onChange="this.parentNode.submit()">
                    <option value="0" style="color:#CCC">Select Schema</option>
                    <?php
						show_select_options(get_objects_by_type(TYPE_DATA_SCHEMA, 0), 'object_id','display_name',$_SESSION['current_schema']);
					?>
                </select>
                <select name="current_set" id="current_set" onChange="submit()" <?php echo !$_SESSION['current_schema'] ? 'disabled' : ''; ?> >
                    <option value="0" style="color:#CCC"><?php echo !$_SESSION['current_schema'] ? 'Select Set' : 'Root'; ?></option>
                    <?php
						if($_SESSION['current_schema']) {
							show_select_options(get_objects_by_type(TYPE_DATA_SET, $_SESSION['current_schema']),'object_id','display_name',$_SESSION['current_set']);
						}
					?>
                </select>
            </form>
        </div>
        
        <?php if($_SESSION['current_schema']): ?>
        <!-- DATA SCHEMA CONTROL PANEL -->
        <div id="schema-channel-app">
        	<?php do_actions('fx_show_schema_control'); ?>
        </div>
        <?php endif; ?>
        
        <div id="auth">
            <?php echo _('Currently logged in as') ?> <a href="<?php echo URL.'settings/settings_dfx_users?object_type_id='.TYPE_DFX_USER.'&object_id='.$_SESSION['user_id'] ?>" id="name"><?php echo $_SESSION['display_name'] ?></a>&nbsp;&nbsp;
            <a href="<?php echo URL ?>logout" id="logout-btn"><?php echo _('Logout') ?></a>
            <div id="bug" class="occured" title="<?php echo _('Send error report') ?>"></div>
        </div>
    </div>
    <!-- SIDEBAR MENU -->
    <ul id="menu">
        <li id="company-logo">
       		<?php do_actions('fx_show_company_logo', $menu_server); ?>
        </li>
        <div id="main_menu" class="menu_list">
			<?php do_actions('fx_show_main_menu', $fx_main_menu); ?>
        </div>
        <span id="collapse" class="menu-icons"><?php echo _('minimize menu') ?></span>
    </ul>
    <!-- CONTENT BLOCK -->
    <div class="tab-content">
        <?php print_fx_errors($initial_errors); ?>
        <div class="tab-pane active">
            <?php do_actions('fx_show_content'); ?>
            <div id="footer">
            <?php do_actions('fx_show_footer'); ?>
            </div>
        </div>
    </div>
	<!-- MODAL WINDOW -->
    <div id="dialog-form"></div>
    <?php do_actions('fx_print_scripts', 'footer'); ?>
</body>
</html>