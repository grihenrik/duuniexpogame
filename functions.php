<?php
function showArray(){
    $result = array(
            "playerToken" => "{$token}",
            0=> array( "question" => "Is there a difference between a jungle and a rain forest?",
            "choices" => ["No difference","Some difference", "Completely different"]),
            1=> array( "question"=>"What is the world's most common religion?",
            "choices" => ["Christianity", "Buddhism", "Hinduism", "Islam"]),
            2=> array(
            "question" => "What is the second largest country (in size) in the world?",
            "choices" => ["USA", "China", "Canada", "Russia"]));
    foreach ($result as $key => $value) {
        echo("<p>Key {$key} Value {$value}</p>");
    }

}

add_action('rest_api_init', function(){
     register_rest_route(DUUNIEXPO_REST_ROUTE,'/show-array/',array(
         'methods' => 'GET',
        'callback' => 'showArray'));
 });
?>