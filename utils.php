<?php

if(!function_exists("quetzal_get_tax_queries") && 
    !function_exists("quetzal_get_meta_queries") && 
    !function_exists("quetzal_title_filter")){

function quetzal_get_tax_queries($request) {
    $a = [];
    $r = $request->get_body_params();
    foreach($r as $key => $value){
        if (strlen($key) > 9)
        {
            if(substr($key, 0, 9) == "tax_query")
            {
                /* 
                    controllo prima che value sia valorizzato,
                    se viene definito un campo input tax_query_pippo ma poi non viene valorizzato durante la ricerca,
                    va escluso dalla ricerca lato server altrimenti cercherà per tax_query_pippo con valore vuoto e non ritornerà risultati
                */
                if ($value != "")
                {
                    array_push($a, 
                        [
                            "taxonomy" => substr($key, 10, strlen($key)), 
                            "terms" => $value,
                            "field" => "slug",
                            "operator" => "AND"
                        ]
                    );
                }
            }
        }
    }
    return $a;
}

function quetzal_get_meta_queries($request) {
    $a = [];
    $r = $request->get_body_params();
    foreach($r as $key => $value){
        if (strlen($key) > 10)
        {
            if(substr($key, 0, 10) == "meta_query")
            {
                // controllo prima che value sia valorizzato, solita logica di tax_query
                if ($value != "")
                {
                    array_push($a, 
                        [
                            "key" => substr($key, 11, strlen($key)), 
                            "value" => $value,
                            "compare" => "LIKE"
                        ]
                    );
                }
            }
        }
    }
    return $a;
}

function quetzal_title_filter( $where, $wp_query ){
    global $wpdb;
    if ( $search_term = $wp_query->get( 'title_like' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $search_term ) ) . '%\'';
    }
	return $where;
}

}

?>