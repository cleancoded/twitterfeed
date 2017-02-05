<?php
/**
 * @package James_Twitter_Feed
 * @version V1.0
 * @copyright James
 */

/*
Plugin Name: James Twitter Feed
Plugin URI: http://www.cleancoded.com
Plugin Descripton: Show Twitter feed from multiple users/screens/hashtags
Author: James
Version: 1.0
Author URI: http://cleancoded.com
*****Not suggested to be used for commercial projects or for production projects as this plugin is created only for testing purpose, licensed under GPLv2 as said in WordpRess and anyone in the universe are allowed to copy, use, sell in any form*****
*/


/** 
* we are using Abraham PHP lib for fetching tweets, 
* recommended lib on Twitter apis
*/
//include the lib autoload, check composer.json file located in plugin root folder
require 'vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Tweet Widget Class
 */
class Tweet_Widget extends WP_Widget
{
    public $james_tweets_lib;
    function __construct()
    {
        //include our widget name and short description
        parent::__construct(false, __('Display Twitter Feeds', 'james'), array(
            'descripton' => __('Show or let your users request what tweets to display')
        ));
    }
    
    public function init_twitter_api_lib($instance)
    {
        # code...
        //init the twitter apis
        //get the keys from widget settings given by the user
       
        $consumer_key = $instance['twitter_consumer_key'];
        $consumer_secret = $instance['twitter_consumer_secret'];
        $access_token = $instance['twitter_access_token'];
        $access_token_secret = $instance['twitter_access_token_secret'];
        $this->james_tweets_lib = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
        $content = $this->james_tweets_lib->get("account/verify_credentials");
    }


    public function form($instance)
    {
        // code...
        /*
        * Displays the form in admin panel widgets
        */
        $defaults = array(
            'count' => 3,
            'query' => 'twitter',
            'title' => 'Twitter Feeds',
            'twitter_consumer_key'=>'',
            'twitter_consumer_secret'=>'',
            'twitter_access_token'=>'',
            'twitter_access_token_secret'=>'',
        );
        
        $values = wp_parse_args($instance, $defaults);
        ob_start();
		?>
		<p><label for='<?php echo $this->get_field_id("twitter_consumer_key");?>'>
            <?php _e("Twitter Consumer Key:", "james"); ?>
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('twitter_consumer_key');?>" id="<?php echo $this->get_field_id('twitter_consumer_key');?>" value="<?php echo $values['twitter_consumer_key'];?>" />

        </label>
        <label for='<?php echo $this->get_field_id("twitter_consumer_secret");?>'>
            <?php _e("Twitter Consumer Secret:", "james"); ?>
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('twitter_consumer_secret');?>" id="<?php echo $this->get_field_id('twitter_consumer_secret');?>" value="<?php echo $values['twitter_consumer_secret'];?>" />

        </label>
        <label for='<?php echo $this->get_field_id("twitter_access_token");?>'>
            <?php _e("Twitter Access Token:", "james"); ?> 
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('twitter_access_token');?>" id="<?php echo $this->get_field_id('twitter_access_token');?>" value="<?php echo $values['twitter_access_token'];?>" />

        </label>
        <label for='<?php echo $this->get_field_id("twitter_access_token_secret");?>'>
            <?php _e("Tiwtter Access Token Sceret:", "james"); ?>               
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('twitter_access_token_secret');?>" id="<?php echo $this->get_field_id('twitter_access_token_secret');?>" value="<?php echo $values['twitter_access_token_secret'];?>" />

        </label>
        <label for='<?php echo $this->get_field_id("title");?>'>
			<?php _e("Widget Title:", "james"); ?>               
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('title');?>" id="<?php echo $this->get_field_id('title');?>" value="<?php echo $values['title'];?>" />

		</label>
		<label for='<?php echo  $this->get_field_id("query"); ?>'>
			<?php _e("Users (Enter comma, seperated list)"); ?>              
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('query');?>" id="<?php echo $this->get_field_id('query');?>" value="<?php echo $values['query'];?>" />

		</label></p>
        <?php
        ob_end_flush();
        
    }
    public function update($new_instance, $old_instance)
    {
        // code...
        /* Save the widget settings */
        $instance = array();
        $instance['title'] = !empty($new_instance['title'])?$new_instance['title']:"";
        $instance['query'] = !empty($new_instance['query'])?$new_instance['query']:"";
          $instance['twitter_consumer_key'] = !empty($new_instance['twitter_consumer_key'])?$new_instance['twitter_consumer_key']:"";
        $instance['twitter_consumer_secret'] = !empty($new_instance['twitter_consumer_secret'])?$new_instance['twitter_consumer_secret']:"";
          $instance['twitter_access_token'] = !empty($new_instance['twitter_access_token'])?$new_instance['twitter_access_token']:"";
        $instance['twitter_access_token_secret'] = !empty($new_instance['twitter_access_token_secret'])?$new_instance['twitter_access_token_secret']:"";
          $instance['count'] = !empty($new_instance['count'])?$new_instance['count']:"";
        return $instance;
    }
    public function widget($args, $instance)
    {
        // code...
        //displays the widget content on frontend
        $tweets = $this->get_tweets($args['widget_id'], $instance)['james_tweets'];
        echo $args['before_widget'];
        echo $args['before_title']. $instance['title'] . $args['after_title'];
        foreach($tweets->statuses as $tweet_content){
            // var_dump($tweet_content->text);
            echo "<p>";
            echo $tweet_content->text;
            echo "</p>";
            echo "<strong>Tweeted on: ".$tweet_content->created_at." | Tweet By: <a href=".$tweet_content->user->url.">".$tweet_content->user->name."</a></strong><hr/>";
        };
        echo $args['after_widget'];
    }
    public function retrieve_tweets($widget_id, $instance)
    {
        // code...
        //retreive the results from Twitter using our above included lib 
        //split and make query string
        $query_string= "";
        $query_string_array = explode(',', $instance['query']);
        $j = 1;
        foreach ($query_string_array as $value) {
            if(!($j == count($query_string_array))):
            # code...
                $query_string .= "from:" . $value . ", OR ";
            else:
                $query_string .= "from:" . $value;
            endif;
            $j++;
        }
        $tweets_list = $this->james_tweets_lib->get("search/tweets", ["q"=>$query_string, "count"=>'']);
        return $tweets_list;
    }

    public function save_tweets($widget_id, $instance)
    {
        # code...
        $tweets_to_update = $this->retrieve_tweets($widget_id, $instance);
        $tweets = array('james_tweets'=>$tweets_to_update, 'tweets_updated_time'=>time() +( 60*60 ) );
        update_option('james_tweets_' . $widget_id, $tweets);
        return $tweets;     
    }
    
    public function get_tweets($widget_id, $instance)
    {
        // code..
        $tweets = get_option("james_tweets_".$widget_id);
        // var_dump($tweets);
        if(empty($tweets) || empty($tweets['james_tweets'])){
         if(empty($this->james_tweets_lib) || $this->james_tweets_lib == ""):
            $this->init_twitter_api_lib($instance);
        endif;
    }
        //get tweets from db or get them withy new api call if time is more than 60 minutes
        if(empty($tweets) || time() > $tweets['tweets_updated_time']) :
            $tweets = $this->save_tweets($widget_id, $instance);
        endif;
         // var_dump($tweets);
        return $tweets;
    }
}
function load_widget_for_tweets(){
    register_widget("Tweet_Widget");
}
//register the widget
add_action("widgets_init", 'load_widget_for_tweets');