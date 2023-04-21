<?php

if(!class_exists("QuetzalShsApi")){

class QuetzalShsApi {

    private $admin;

    public function __construct($admin){
        $this->admin = $admin;

        add_action('rest_api_init', function() {
            register_rest_route('v1', 'quetzal_shs_endpoint', array(
                'methods' => array('POST'),
                'callback' => function($request) { return $this->search_endpoint($request); }
            ));
        });

        add_shortcode("simple_html_search_bar", [$this, "search_bar"]);
        add_shortcode("simple_html_search_results", [$this, "search_results"]);
    }

    public function search_bar($atts){
        ob_start();

        ?>
        <div class="container">
            <div class="col-md-12">

            <div id="quetzal_shs_search_bar">
                <div id="quetzal_shs_results_template" style="display:none"><?php $this->admin->read_html("quetzal_shs_result_1") ?></div>
                <form onsubmit="return false">
                    <?php $this->admin->read_html("quetzal_shs_bar_1") ?>
                </form>
            </div>

            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public function search_results(){
        ob_start();

        ?>
            <div id="quetzal_shs_results"></div>
        <?php

        return ob_get_clean();
    }

    private function search_endpoint($request) {
        $args = array(
            'posts_per_page' => 9999,
            //"paged" => 1,
            'post_type' => 'post',
    
            'tax_query' => array (
                'relation' => 'AND',
                /*array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => $data['tag'],
                    'operator' => 'AND'
                ),
                array(
                    'taxonomy' => 'category',
                    'field'    => 'name',
                    'terms'    => $data['rivista'],
                    'operator' => 'AND'
                ),*/
            
            ),
    
            'meta_query' => array(
                'relation' => 'AND',
                /*array(
                    'key'     => 'autore',
                    'value'   => $data['autore'],
                    'compare'=> 'LIKE' 
                ),
                array(
                    'key'     => 'anno',
                    'value'   => $data['anno'],
                    'compare'=> 'LIKE' 
                ),*/
            )
        );
        
        if ($request->has_param("limit"))
            $args["posts_per_page"] = $request->get_param("limit");

        if ($request->has_param("page"))
            $args["paged"] = $request->get_param("page");

        if ($request->has_param("post_type"))
            $args["post_type"] = $request->get_param("post_type");

        if ($request->has_param("author_name"))
            $args["author_name"] = $request->get_param("author_name");

        if ($request->has_param("title_like"))
            $args["title_like"] = $request->get_param("title_like");       
        
        $tax_queries = quetzal_get_tax_queries($request);
        if (count($tax_queries) > 0)
            array_push($args["tax_query"], $tax_queries);
        
        $meta_queries = quetzal_get_meta_queries($request);
        if (count($meta_queries) > 0)
            array_push($args["meta_query"], $meta_queries);
    
        add_filter('posts_where', 'quetzal_title_filter', 10, 2);
        $q = new WP_Query($args);
        $posts = $q->get_posts();
        remove_filter('posts_where', 'quetzal_title_filter', 10, 2);
        $a = [];

        foreach($posts as $p){
            array_push($a, ["title" => html_entity_decode(str_replace("& #8217","'", get_the_title($p->ID))),
                    "author_name" => get_the_author_meta( 'display_name', get_post_field('post_author', $p->ID) ),
                    "permalink" => get_the_permalink($p->ID),
                    //"body" => $p->post_content, 
                    "body" => wp_strip_all_tags($p->post_content), 
                    "excerpt" => $p->post_excerpt,
                    "thumbnail" => get_the_post_thumbnail_url($p->ID),
                    "category" => get_the_category($p->ID),
                    "tag" => get_the_tags($p->ID),
                    "meta" => get_post_meta($p->ID),
                    "max_num_pages" => $q->max_num_pages,
                    "paged" => $q->get("paged") ? $q->get("paged") : 1
            ]);
        }
    
        return $a;
    }

}
}

?>
