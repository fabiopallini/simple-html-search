<?php

if(!class_exists("QuetzalShsAdmin")){

class QuetzalShsAdmin {
    private $file;
    private $plugin_table;

    public function __construct($file){
        $this->file = $file;
        global $wpdb;
        $this->plugin_table = $wpdb->base_prefix . "quetzal_simple_html_search";

        add_action('admin_enqueue_scripts', [$this, 'load_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'load_scripts']);
        add_action('plugins_loaded', function(){ 
            add_action('admin_menu', [$this, 'init_menu']); 
        });

        add_action('rest_api_init', function() {
            register_rest_route('v1', 'quetzal_shs_save_html', array(
                'methods' => array('POST'),
                'callback' => function($request) 
                { 
                    $r = $request->get_json_params();
                    if(wp_verify_nonce($r['nonce'], 'wp_rest'))
                    {
                        if($r["bar_name"] && $r["html_code"]){
                            $this->save_html($r["bar_name"], $r['html_code']);
                            return $r["html_code"];
                        }
                    }
                    return wp_die("unauthorized", "Error", ["response" => 401]);
                 }
            ));
        });

        register_activation_hook($this->file, [$this, 'activation']);
        //register_deactivation_hook($this->file, [$this, 'uninstall']);
    }

    public function load_scripts() {
        // JS bootstrap
        wp_register_script('Quetzal_shs_bootstrap', plugin_dir_url($this->file) . 'lib/bootstrap.bundle.min.js');
        wp_enqueue_script('Quetzal_shs_bootstrap');
        // CSS bootstrap
        wp_register_style('Quetzal_shs_bootstrap', plugin_dir_url($this->file) . 'lib/bootstrap.min.css');
        wp_enqueue_style('Quetzal_shs_bootstrap');

        //css style.css
        wp_register_style("Quetzal_shs_style", plugin_dir_url($this->file) . "style.css");
        wp_enqueue_style("Quetzal_shs_style");

        // ace c9
        wp_register_script('shs_acec9', plugin_dir_url($this->file) . 'lib/acec9/ace.js');
        wp_enqueue_script('shs_acec9');

        wp_register_script( 'Quetzal_shs_main_js' , plugin_dir_url($this->file) . 'main.js' );
        wp_localize_script( 'Quetzal_shs_main_js' , 'options' , array( 'rest_url' => get_rest_url(), "nonce" => wp_create_nonce('wp_rest') ) );
        wp_enqueue_script( 'Quetzal_shs_main_js' );
    }

    public function init_menu(){
        add_menu_page('Simple Search', 'Simple Html Search', 'manage_options', 'simplehtmlsearch', [$this, 'view_options'], '', 100);
    }

    public function view_options(){
        ob_start();
        ?>

        <div class="container quetzal_shs_codeviewer">

            <div class="row mt-5">

            <p>A basic usage example is available on <a href="https://github.com/fabiopallini/simple-html-search" target="_blank">Github</a></p>

                <div class="col-md-12 code">
                    <div class="ace_editor" id="quetzal_shs_bar_1"><?php $this->read_html("quetzal_shs_bar_1") ?></div>
                    <div class="quetzal_shs_toolbar mb-2">
                        <button class="btn btn-success" onclick="quetzal_shs_save_html('quetzal_shs_bar_1')">Save</button>
                        <span class="quetzal-shs-label-shortcode">shortcode [simple_html_search_bar]</span>
                    </div>
                    <div id="preview_quetzal_shs_bar_1"><?php $this->read_html("quetzal_shs_bar_1") ?></div>
                </div>

            </div>

            <div class="row mt-5">

                <div class="col-md-12 code">                
                    <div class="ace_editor" id="quetzal_shs_result_1"><?php $this->read_html("quetzal_shs_result_1") ?></div>
                    <div class="quetzal_shs_toolbar mb-2">
                        <button class="btn btn-success" onclick="quetzal_shs_save_html('quetzal_shs_result_1')">Save</button>
                        <span class="quetzal-shs-label-shortcode">shortcode [simple_html_search_results]</span>
                    </div>
                    <div id="preview_quetzal_shs_result_1"><?php $this->read_html("quetzal_shs_result_1") ?></div>
                </div>

            </div>  
        
        </div>

        <script>
            acec9_init("quetzal_shs_bar_1");
            acec9_init("quetzal_shs_result_1");

            function acec9_init(name){
                // convert all html tags <p> <div> etc in &alt;p &alt;div and </p> </div> in &alt;/p &atl;/div
                let el = document.querySelector("#"+name);
                if(el)
                    el.innerHTML = el.innerHTML.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");

                var editor = ace.edit(name);
                editor.setTheme("ace/theme/monokai");
                editor.setShowPrintMargin(false);
                //editor.session.setMode("ace/mode/javascript");
                //editor.session.setMode("ace/mode/css");
                editor.session.setMode("ace/mode/html");
            }
        </script>

        <?php
    
        echo ob_get_clean();
    }
    
    public function activation(){
        global $wpdb;
        // set the default character set and collation for the table
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE `{$this->plugin_table}` (
            id bigint(50) NOT NULL AUTO_INCREMENT,
            bar_name varchar(255),
            html_code TEXT, 
            PRIMARY KEY (id)
        ) $charset_collate;";	

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        $is_error = empty( $wpdb->last_error );
        return $is_error;
    }
    
    public function uninstall(){
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS `{$this->plugin_table}`";
        $wpdb->query($sql);
    }

    public function read_html($bar_name){
        global $wpdb;
        $sql = "SELECT * FROM `{$this->plugin_table}` WHERE bar_name = %s";
        $res = $wpdb->get_results($wpdb->prepare($sql, $bar_name));
        if(count($res) > 0)
            echo($res[0]->html_code);
    }

    private function save_html($bar_name, $html_code){
        global $wpdb;
    
        $sql = "SELECT * FROM `{$this->plugin_table}` WHERE bar_name = %s";

        $res = $wpdb->get_results($wpdb->prepare($sql, $bar_name));
        if(count($res) > 0)
            $wpdb->update( $this->plugin_table, array('html_code' => $html_code), array('bar_name' => $bar_name) );
        else 
            $wpdb->insert( $this->plugin_table, array("bar_name" => $bar_name, "html_code" => $html_code));
    }
}

}

?>