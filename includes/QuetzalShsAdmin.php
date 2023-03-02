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

        // highlight.js
        wp_register_style('Quetzal_shs_highlightjs_css', plugin_dir_url($this->file) . 'lib/highlight-dark.min.css');
        wp_enqueue_style('Quetzal_shs_highlightjs_css');
        wp_register_script('Quetzal_shs_hightlight_js', plugin_dir_url($this->file) . 'lib/highlight.min.js');
        wp_enqueue_script('Quetzal_shs_hightlight_js');

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

                <div class="col-md-7 code">
                    <div class="quetzal_shs_toolbar">
                        <button class="btn btn-success" onclick="quetzal_shs_save_html('quetzal_shs_bar_1')">Save</button>
                        <span class="quetzal-shs-label-shortcode">shortcode [simple_html_search_bar]</span>
                    </div>
                    <textarea placeholder="search bar code..." class="quetzal_shs_textarea" id="quetzal_shs_bar_1"><?php $this->read_html("quetzal_shs_bar_1") ?></textarea>
                </div>

                <div class="col-md-5">
                    <p>Preview search bar</p>
                    <div id="preview_quetzal_shs_bar_1"><?php $this->read_html("quetzal_shs_bar_1") ?></div>
                </div>

            </div>


            <div class="row mt-5">

                <div class="col-md-7 code">
                    <div class="quetzal_shs_toolbar">
                        <button class="btn btn-success" onclick="quetzal_shs_save_html('quetzal_shs_result_1')">Save</button>
                        <span class="quetzal-shs-label-shortcode">shortcode [simple_html_search_results]</span>
                    </div>
                    <textarea placeholder="result code..." class="quetzal_shs_textarea" id="quetzal_shs_result_1"><?php $this->read_html("quetzal_shs_result_1") ?></textarea>    
                </div>

                <div class="col-md-5">
                    <p>Preview single result object from search</p> 
                    <div id="preview_quetzal_shs_result_1"><?php $this->read_html("quetzal_shs_result_1") ?></div>
                </div>

            </div>  
        
        </div>

        <div class="container quetzal_shs_codesample">

            <h5 class="mt-5">Code examples</h5>

            <h6>Search bar sample</h6>
            <div class="row">
                <div class="col-md-10">
                    <p>
                        The search bar can be customized with multiple filters, potentially with any filter, 
                        the code beside shows a basic example filtering by title, name="tile_like" means that the input doesn't need
                        to be exact to the title value but just similar or containig some words.<br><br>
                        The post_type is literally the post type, usually "post", or "page" etc, you may refer to "Post Type Parameters" on official
                        Wordpress's documentation.<br>
                        You may also need to filter by category or tag with name="tax_query_category" and name="tax_query_post_tag" as shown beside.
                        <br><br>
                        What is tax_query_category? or tax_query_post_tag?<br>
                        tax_query rapresents the taxonomy keyword that Wordpress support as a filter, you can filter by any taxsonomy you need.
                        <br><br>
                        What does do meta_query_my_attribute_name?<br>
                        meta_query is another keyword, you need that when you have a custom field you need to filter on, for instance you may have created a new custom 
                        field with famous plugin ACF named my_attribute_name, that's when meta_query comes in.<br><br>

                        name="limit" with value="5" sets the results to the first 5, any &lt;input&gt; may be hidden also.
                    </p>

                    <pre class="quetzal_shs_pre"><code class="language-html" id="quetzal_shs_codesample1"><div style="border: 1px solid lightgray; 
border-radius: 5px; padding: 15px; 
-webkit-box-shadow: 3px 3px 5px 0px rgba(0,0,0,0.5); 
box-shadow: 3px 3px 5px 0px rgba(0,0,0,0.5);">

    <input class="mb-2" type="text" name="title_like" placeholder="title" style="width: 130px">

    <input class="mb-2" name="post_type" placeholder="post type" style="width:130px">

    <input class="mb-2" name="author_name" placeholder="author" style="width:130px">

    <input class="mb-2" type="text" name="tax_query_category" placeholder="category" style="width:130px">

    <input class="mb-2" type="text" name="tax_query_post_tag" placeholder="tag" style="width:130px">

    <input class="mb-2" type="text" name="meta_query_my_attribute_name" placeholder="my attribute" style="width:130px">

    <input hidden name="limit" value="5">

    <button style="display: block;
        margin: 20px auto 10px auto;
        background-color: steelblue; color: white;" class="btn" onclick="quetzal_shs_ajax()">
        Search
    </button>
</div>
</code></pre>
                </div>
            </div> <!-- row -->

            <h6>Result sample</h6>

            <div class="row">
                <div class="col-md-10">
                    <p>
                        On the result side, any layout can be done with multiple &lt;div&gt; and custom styles.<br>
                        You must specify what field you need to show as output, you can achive this through the "id" attribute.<br>
                        For instance if you need to display the title or the author name, you have to define an "id" with the specified
                        name, and so forth.
                    </p>

                    <pre class="quetzal_shs_pre"><code class="language-html" id="quetzal_shs_codesample2"><div style="border: 1px solid lightgray; 
margin-bottom: 40px; text-align:center; padding: 5px;
-webkit-box-shadow: 3px 3px 5px 0px rgba(0,0,0,0.5); 
box-shadow: 3px 3px 5px 0px rgba(0,0,0,0.5);">

    <p><a href="" id="title" style="font-size:16px;
    text-decoration: none;">Post title with link</a></p>

    <p id="category__name" style="background-color: lightblue">Post categories list</p>

    <p id="tag__name" style="background-color: lightgreen">Post tags list</p>

    <p id="meta__my_attribute">the post's custom attribute</p>

    <p id="body">the post's whole content</p>

    <p id="excerpt" style="font-style: italic">the post's excerpt</p>

    <label>Author:</label>
    <p id="author_name">Author's name</p>

    <img id="thumbnail" width=100 height=auto>

    <!--<img id="thumbnail" src="placeholder_url.jpg" width=100 height=auto>-->

</div>
</code></pre>
                </div>
            </div><!-- row -->

        </div> <!-- container -->

        <script>
                quetzal_shs_admin_init();
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
