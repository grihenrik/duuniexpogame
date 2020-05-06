<?php

/**
 * Plugin name: Duuniexpo plugin
 * Author: Henrik Gripenberg
 * Description: This plugin creates an API endpoint for the duuniexpo game
 * Version: 0.0.1
 * License: MIT
 * 
 * Copyright 2019 Henrik Gripenberg
 * Permission is hereby granted, free of charge, to any person obtaining a 
 * copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 * 
 */
 
 define( 'DUUNIEXPO_PATH', dirname( __FILE__ ) );
 define( 'DUUNIEXPO_URL', plugin_dir_url( __FILE__ ));
 define( 'DUUNIEXPO_REST_ROUTE', 'duuniexpo/v1');
 
 global $wpdb;
 define('DUUNIEXPO_GAME', $wpdb->prefix.'watu_master');
 define('DUUNIEXPO_PLAYERS', $wpdb->prefix.'watu_players');
 define('DUUNIEXPO_QUESTIONS', $wpdb->prefix.'watu_question');
 define('DUUNIEXPO_ANSWERS', $wpdb->prefix.'watu_answer');
 define('DUUNIEXPO_GRADES', $wpdb->prefix.'watu_grading');
 define('DUUNIEXPO_TAKINGS', $wpdb->prefix.'watu_takings');
 include(DUUNIEXPO_PATH.'functions.php');
 $wpdb->show_errors();
/**
 * This function gets the user nickname and creates a token for the user
 * Then it returns the token with the questions and answers to the 
 * client on the API endpoint. 
 * In this version no checking is done for other than that the nickname
 * does not contain any illegal characers.
 */
 function get_new_user($data){
    global $wpdb;
    // First, sanitize the nickname
    $nick=stripslashes($data['nick']);
    // Second, create a token for the nick
    $token = getToken(15);
    $date= new DateTime('NOW');;
    $dt = $date->format('d.m.Y H:i:s.u');
    $pathID=2;
    $exam_id=$pathID;
    $wpdb->query("call sp_New_Player('$token','$nick',2,2)");
    
    /**
     * Build the questions and answers string for the user
     * it should be in the form of
     * {
     *  "playerToken":"dskfsjdkfjds",
     *       "0":{
     *           "question":"Is there a difference between a jungle and a rain forest?",
     *           "choices": ["No difference","Some difference", "Completely different"]
     *       },
     *       "1":{
     *           "question":"What is the world's most common religion?",
     *           "choices":["Christianity", "Buddhism", "Hinduism", "Islam"]
     *       },
     *       "2":{
     *           "question":"What is the second largest country (in size) in the world?",
     *           "choices":["USA", "China", "Canada", "Russia"]
     *       }
     *  }
     * 
     * and so on
     */
    // Third, Get the question and answer array from the database
    $all_questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".DUUNIEXPO_QUESTIONS." WHERE exam_id=%d ", $exam_id));
    $all_choices = $wpdb->get_results($wpdb->prepare("select a.question_id,q.question,a.ID,a.answer from ".DUUNIEXPO_ANSWERS." a join ".DUUNIEXPO_QUESTIONS." q on a.question_id=q.ID"));
    $result = array(
        "playerToken" => "{$token}",
        0=> array( "question" => "Is there a difference between a jungle and a rain forest?",
        "choices" => ["No difference","Some difference", "Completely different"]),
        1=> array( "question"=>"What is the world's most common religion?",
        "choices" => ["Christianity", "Buddhism", "Hinduism", "Islam"]),
        2=> array(
        "question" => "What is the second largest country (in size) in the world?",
        "choices" => ["USA", "China", "Canada", "Russia"]));
     
     // Fourth, present the array on the API endpoint as result*/
    return json_encode($result);     
 } 
/**
 * This function takes the token a question id and and answer id
 * then it asks the database if the answer is correct.
 * on return it returns the token with the value true or false depending on 
 * the result from the database.
 */
 function get_answer($data){
     global $wpdb;
     // Sanitize input before doing queries
     $token = unserialize(stripslashes($data['token']));
     $question = unserialize(stripslashes($data['question']));
     $answer = unserialize(stripslashes($data['answer']));
     // Check that the token exists
     $sql = "SELECT * FROM `wpzx_watu_players`";
     $user = $wpdb->get_row($wpdb->prepare(sql." where token=%s",$token));
     if(empty($user)) return __('User not found','duuniexpo');
     // Check question - answer pair
     // Make a request to see if the token is correct
     $sql="select correct from ".DUUNIEXPO_ANSWERS." ans join ".DUUNIEXPO_QUESTIONS." que on que.id=ans.question_id";
     $result = $wpdb->get_row($wpdb->prepare($sql." where id=%s and answerId=%s",$question,$answer));
     // Return true for correct and false for false
     if(empty($result)) return false;
     if(!$result)return false;
     return true;
 }
 /**
  * This function checks if the nick that the user has chosen already exists in the db
  * returns false if it does otherwise true
  */
 function checkNick($data){
    global $wpdb;
    $checkNick = stripslashes($data['nick']);
    // make query to see if nick exists
    // return true if the nick does not exist or false if exists
    $res= $wpdb->get_row($wpdb->prepare("select nick from ".DUUNIEXPO_PLAYERS." where nick=%s ",$checkNick));
    //var_dump($res);
    if($res==NULL) {return json_encode(array("nick_available" => 1));}
    return json_encode(array("nick_available" => 0));
 }
 
 add_action('rest_api_init', function(){
     register_rest_route(DUUNIEXPO_REST_ROUTE,'/new-player/(?P<nick>[a-zA-Z0-9%]+)',array(
         'methods' => 'GET',
        'callback' => 'get_new_user'));
 });
 add_action('rest_api_init', function(){
     register_rest_route(DUUNIEXPO_REST_ROUTE,'/check-nick/(?P<nick>[a-zA-Z0-9%]+)',array(
         'methods' => 'GET',
        'callback' => 'checkNick'));
 });
 add_action('rest_api_init', function(){
     register_rest_route(DUUNIEXPO_REST_ROUTE,'/answer/(?P<token>[a-zA-Z0-9]+)/(?P<question>[a-zA-Z0-9]+)/(?P<answer>\d+)',array(
         'methods' => 'GET',
        'callback' => 'get_answer'));
 });

function getToken($length){
     $token = "";
     $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
     $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
     $codeAlphabet.= "0123456789";
     $max = strlen($codeAlphabet); // edited

    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[random_int(0, $max-1)];
    }

    return $token;
}
 
?>
