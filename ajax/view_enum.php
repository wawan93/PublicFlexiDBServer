<?php

	require_once dirname(dirname(__FILE__))."/fx_load.php";
	fx_start_session();
	validate_script_user();

	require_once dirname(dirname(__FILE__))."/fx_ui/fx_enum_form.php";

	if (isset($_GET['schema_id'])) {
		$schema_id = (int)$_GET['schema_id'];
	}
	else {
		$schema_id = $_SESSION['current_schema'];
	}

	$is_system = (int)$_GET['system'] ? true : false;

	$referer = isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER'];
	$redirect_to = false;
	$IOResult = false;

	if(isset($_POST['enum_action']) && $_POST['enum_action'] == 'add')
	{
		$enum_array = $_POST;
 		$IOResult = add_enum_type($enum_array);

		if (!is_fx_error($IOResult)) {
			if(is_url($referer)) $redirect_to = replace_url_param('enum_type_id', $IOResult, $referer);
			else $IOResult = new FX_Error('add_enum', 'Enum type successfully added but redirect url is invalid.');
		}
	}
?>
<html>
<head>
	<meta http-equiv="content-language" content="en"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link href="<?php echo URL?>style/flexiweb.css" rel="stylesheet" type="text/css">

    <script type="text/javascript" src="<?php echo URL?>js/jquery.js"></script>
    <script type="text/javascript" src="<?php echo URL?>js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="<?php echo URL?>js/flexiweb.js"></script>
    
    <script>
        fx_dir = '<?php echo URL; ?>';
    </script>
	<script language="javascript">
        if (typeof(jQuery) == "undefined") {
            var iframeBody = document.getElementsByTagName("body")[0];
            var jQuery = function (selector) { return parent.jQuery(selector, iframeBody); };
            var $ = jQuery;
        }

		$(document).ready(function(e) {
			<?php if($redirect_to) echo 'window.parent.location = "'.$redirect_to.'";'; ?>

            $("#fieldsTable").sortable({
                axis:'y',
                items: "li:not(.ui-disabled)",
                placeholder: "placeholder",
                start: function(e,ui){
                    ui.placeholder.height(ui.item.height());
                },
                helper: function(e, element)
                {
                    var $originals = element.children();
                    var $helper = element.clone();
                    $helper.children().each(function(index)
                    {
                        // Set helper cell sizes to match the original sizes
                        $(this).width($originals.eq(index).width())
                    });
                    return $helper;
                }
            });

            $('.button').last().attr('onclick','').unbind('click').click(function(event) {
                alert(1);
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            });
		});

	</script>
    <style type="text/css">
        ul {
            list-style: none;
        }
	</style>
</head>
<body class="popup">

	<?php

    echo enum_form();
	
	?>
</body>
</html>