<?php
/**
* Plugin Name: Simple Html Search
* Description: A complete customizable search bar and results with html/css (support at fabiopallini01@gmail.com)
* Version: 1.0
* Author: Fabio Pallini 
* Author URI: https://github.com/fabiopallini/
*/

require_once "utils.php";
require_once "includes/QuetzalShsAdmin.php";
require_once "includes/QuetzalShsApi.php";

$quetzal_shs_admin = new QuetzalShsAdmin(__FILE__);
$quetzal_shs_api = new QuetzalShsApi($quetzal_shs_admin);

?>
