<?php
require_once "./generation_utils.php";

if(isset($_GET['id'])) {
    generate_build_zip($_GET['id']);
}

