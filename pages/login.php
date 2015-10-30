<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>DFX Admin - Flexiweb</title>

    <link rel="stylesheet" type="text/css" href="<?php echo URL?>style/login.css">
    <link rel="stylesheet" type="text/css" href="<?php echo URL?>style/jquery-ui.custom.css">

    <script type="text/javascript" src="<?php echo URL?>js/jquery.min.js" ></script>
    <script type="text/javascript" src="<?php echo URL?>js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="<?php echo URL?>js/jquery.placeholder.min.js"></script>
	<script type="text/javascript">

		$(document).ready(function() {
            $('input[placeholder], textarea[placeholder]').placeholder();
            $(document).keypress(function(e) { 
				if(e.which == 13) { 
					$("#login_form").submit();
				}
			});
            $('.footer label').click(function(e){
                if($('#remember-me').prop('checked')) {
                    $(this).removeClass('checked').addClass('not-checked');
                } else {
                    $(this).removeClass('not-checked').addClass('checked');
                }
            });
        });

    </script>
</head>
<body>
    <div class="login-window">
        <div id="auth">
          	<?php 
				if(is_fx_error($login_result)) { 
					echo '<div class="msg error">'.$login_result->get_error_message().'</div>';
				} 
			?>
            <div class="header">
                <img src="<?php echo URL?>images/FDB-logo-232x61.jpg">
            </div>
            <form action="" method="post" id="login_form">
                <input type="hidden" name="login">
                <div class="content">
                    <input type="text" name="username" id="username" value="<?php echo $_POST['username']?>" placeholder="<?php echo _('username'); ?>" title="<?php echo _('username'); ?>">
                    <input type="password" name="password" id="password" value="<?php echo $_POST['password']?>" placeholder="<?php echo _('password'); ?>" title="<?php echo _('password'); ?>">
                </div>
				<input type="button" class="button green" onClick="submit()" value="<?php echo _('Sign In'); ?>">
                <div class="footer">
                    <input type="checkbox" name="remember_me" id="remember-me"<?php if(isset($_POST['remember_me'])) echo " checked"?>>
                    <label for="remember-me" class="not-checked"><?php echo _('Stay Signed In'); ?></label>
                    <span class="divider">&nbsp;|&nbsp;</span>
                    <a href="#" onClick="restore_password()" id="forgot-pwd"><?php echo _('Forgot your password'); ?>?</a>
                </div>
            </form>
        </div>
        <div class="footer">
            <a id="fx-link" href="<?php echo FX_SERVER ?>">Flexilogin.com</a>
        </div>
    </div>
</body>
</html>