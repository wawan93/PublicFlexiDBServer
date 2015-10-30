<?php
$schema = $_SESSION['current_schema'];

$date_format_settings = get_fx_option('fx_datetime_format', array());
if (!empty($date_format_settings)) {
    $date_format = $date_format_settings['date']['format'] ?
        $date_format_settings['date']['format'] :
        $date_format_settings['date']['custom'];
    $date_format .= ' ';
    $date_format .= $date_format_settings['time']['format'] ?
        $date_format_settings['time']['format'] :
        $date_format_settings['time']['custom'];
} else {
    $date_format = 'F j, Y H:i:s';
}
$dropbox_keys = get_fx_option('fx_dropbox_keys', array());
if ($dropbox_keys['key']) {
    ?>
    <script type="text/javascript"
            src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs"
            data-app-key="<?php echo $dropbox_keys['key']; ?>"></script>

    <script>
        $(document).ready(function () {
            var options = {
                success: function (files) {
                    $.post('<?php echo CONF_AJAX_URL.'backup.php'?>', {
                        link: files[0].link,
                        name: files[0].name
                    }, function (data) {
                        console.log(data);
                        data = JSON.parse(data);
                        if (data.error != undefined) {
                            $('.rightcolumn').prepend('<div class="error"><p>' + data.error + '</p></div> ');
                        } else {
                            var $tr = $('<tr></tr>');
                            $tr.append('<td>' + data.name + '</td>');
                            $tr.append('<td>' + data.time + '</td>');
                            var $links = $('<td></td>');
                            $links.append(' <a href="?restore=1&filename=' + data.name + '" class="button red" >Restore</a> ');
                            $links.append(' <a href="?delete=1&name=' + data.name + '" class="button red"> Delete </a> ');
                            $links.append(' <a href="' + data.link + '" target="_blank" class="button green">Download</a> ');
                            var saver = Dropbox.createSaveButton(data.link, data.name);
                            $links.append(saver);
                            $tr.append($links);

                            $('.list tbody').append($tr);
                        }
                    });
                },
                cancel: function () {

                },
                linkType: "direct",
                extensions: ['.sql', '.gz', '.bz2']
            };

            var button = Dropbox.createChooseButton(options);
            $("#dbx_choose_container").append($(button));
        });
    </script>
<?php } ?>
<div class="rightcolumn">

    <?php

    $obj = new FX_Backup();
    if (isset($_REQUEST['restore']) && !empty($_REQUEST['restore'])) {
        $fn = $_REQUEST['filename'];
        $restore = FX_Backup::get_instance()->restore($fn);
        if (is_fx_error($restore)) {
            fx_show_metabox(
                array(
                    'body' => array('content' => $restore),
                    'footer' => array('hidden' => true)
                )
            );
        } else {
            fx_show_metabox(
                array(
                    'body' => array(
                        'content' => '<div class="info"><p>' .
                            _('Database successfully restored from ')
                            . '"' . $fn . '"'
                            . '</p></div>'
                    ),
                    'footer' => array('hidden' => true)
                )
            );
        }
    } elseif ($_REQUEST['backup']) {
        $options = array();
        $options['add-drop-table'] = true;
        if (in_array($_REQUEST['compress'], array('none', 'gzip', 'bzip2'))) {
            $options['compress'] = $_REQUEST['compress'];
        }

        $obj->backup($options, normalize_string($_REQUEST['filename']));

        if (file_exists(
            CONF_UPLOADS_DIR . FX_Backup::$dir . $obj->get_filename()
        )
        ) {
            fx_show_metabox(
                array(
                    'body' => array(
                        'content' => '<div class="info"><p> Database backup saved as '
                            . $obj->get_filename() . ' </p></div>'
                    ),
                    'footer' => array('hidden' => true)
                )
            );
        } else {
            fx_show_metabox(
                array(
                    'body' => array(
                        'content' => '<div class="error"><p> Error with creating file  '
                            . $obj->get_filename() . ' </p></div>'
                    ),
                    'footer' => array('hidden' => true)
                )
            );
        }
    } elseif (isset($_FILES["filename"])) {
        $upload_result = $obj->upload();
        if (is_fx_error($upload_result)) {
            fx_show_metabox(
                array(
                    'body' => array('content' => $upload_result),
                    'footer' => array('hidden' => true)
                )
            );
        } else {
            fx_show_metabox(
                array(
                    'body' => array(
                        'content' => '<div class="info"><p> File "'
                            . $_FILES["filename"]['name'] . '" successfully uploaded! </p></div>'
                    ),
                    'footer' => array('hidden' => true)
                )
            );
        }
    } elseif ($_REQUEST['delete']) {
        if ($obj->delete($_REQUEST['name'])) {
            fx_show_metabox(
                array(
                    'body' => array(
                        'content' => '<div class="info"><p> File "'
                            . $_REQUEST['name'] . '" deleted! </p></div>'
                    ),
                    'footer' => array('hidden' => true)
                )
            );
        }
    }
    ?>

    <div class="metabox">
        <div class="header">
            <div></div>
            <h1>
                Backup
            </h1>
        </div>
        <div class="content">
            <form action="" method="post">
                <label>
                    Name
                    <input type="text" name="filename"
                           value="backup_<?php echo date('Y_m_d_-_H_i_s'); ?>">
                </label><br>
                <label>
                    Compress
                    <select name="compress">
                        <option value="none">None</option>
                        <option value="gzip">Gzip</option>
                        <option value="bzip2">Bzip2</option>
                    </select>
                </label>
                <br>
                <br>
                <button class="button green" name="backup" type="submit"
                        value="1"> Backup
                </button>
            </form>
        </div>
    </div>
    <div class="metabox">
        <div class="header">
            <h1>
                Restore
            </h1>
        </div>
        <div class="content">
            <div id="dbx_choose_container">
                <?php if (!$dropbox_keys['key']) { ?>
                    <h2>You can setup Dropbox sync <a
                            href="<?php echo CONF_SITE_URL; ?>/settings/settings_keys">here</a>
                    </h2>
                <?php } ?>
            </div>
            <div>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="file" name="filename">
                    <button class="button green" type="submit"> Upload</button>
                </form>
            </div>
            <hr>
            <div class="list">
                <?php
                $dir = CONF_UPLOADS_DIR . FX_Backup::$dir;
                $url = CONF_UPLOADS_URL . FX_Backup::$dir;
                $files = scandir($dir);
                if (count($files) == 2) {
                    ?>
                    <h1>No backups</h1>
                <?php } else { ?>
                    <table style="width: 100%;">
                        <thead>
                        <tr>
                            <th> File name</th>
                            <th> Created</th>
                            <th> Operations</th>
                        </tr>
                        </thead>
                        <tbody><?php
                        foreach ($files as $name) {
                            if ($name[0] == '.') {
                                continue;
                            } ?>
                            <tr>
                                <td><?php echo $name ?></td>
                                <td><?php echo date(
                                        $date_format,
                                        filectime($dir . $name)
                                    ); ?></td>
                                <td>
                                    <form id="restore" action="" method="post" style="display: inline-block;">
                                        <input type="hidden" name="restore" value="1">
                                        <input type="hidden" name="filename" value="<?php echo $name; ?>">
                                        <input type="submit" value="Restore" class="button green">
                                    </form>
                                    <form id="delete" action="" method="post" style="display: inline-block;">
                                        <input type="hidden" name="delete" value="1">
                                        <input type="hidden" name="name" value="<?php echo $name; ?>">
                                        <input type="submit" value="Delete" class="button red">
                                    </form>
                                    <a href="<?php echo $url . $name; ?>"
                                       target="_blank" class="button blue">
                                    Download
                                    </a>
                                    <?php if ($dropbox_keys['key']) { ?>
                                        <a href="<?php echo $url . $name; ?>"
                                           class="dropbox-saver"> Save to
                                            Dropbox </a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
