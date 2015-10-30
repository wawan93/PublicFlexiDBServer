<?php

	function _app_preview_tool()
	{
		?>
            <div class="preview-nav">
                Screen Resolution:
                <select id="preview-frame-size">
                    <option data-width="320" data-height="480">320x480</option>
                    <option data-width="480" data-height="800" selected="selected">480x800</option>
                    <option data-width="640" data-height="960">640x960</option>
                    <option data-width="640" data-height="1136">640x1136</option>
                    <option data-width="720" data-height="1280">720x1280</option>
                    <option data-width="768" data-height="1024">768x1024</option>
                    <option data-width="900" data-height="1400">900x1400</option>
                    <option data-width="1536" data-height="2048">1536x2048</option>
                </select>
                <select id="preview-frame-orientation">
                    <option value="portrait">Portrait</option>
                    <option value="landscape" >Landscape</option>
                </select>
                <input type="button" class="button blue" id="download-button" style="display: inline-block;float: none;" value="Download">
            </div>

            <iframe id="preview-frame"
                    name="preview-frame"
                    src="<?php echo URL ?>/mobile_app/generate.php"
                    style="width: 320px; height: 480px; border: 1px solid #a9a9a9">

            </iframe>
            <iframe id="download-frame" style="display: none;"></iframe>        
			<script>
                $(document).ready(function () {
                    $("#download-button").click(function () {
                        window.open(window.flexiweb.site_url + 'mobile_app/get_build_zip.php');
                    });
                    initPreview($('#preview-frame-size'), $('#preview-frame-orientation'), $('#preview-frame'), $('#preview-form'));
                });
            </script>
        <?php
	}

	$mb_data = array('body' => array('function' => '_app_preview_tool'),
					 'footer' => array('hidden' => true));

	fx_show_metabox($mb_data);
?>