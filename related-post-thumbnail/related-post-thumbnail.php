<?php
/*
Plugin Name: Related Posts with Thumbnail
Plugin URI: http://www.dynamick.it/related-post-with-thumbnail-942.html
Description: Returns a list of the related entries based on active/passive keyword matches and show the first available image in the post. It can generate a thumbnail of custom dimensions. Based on an original plugin of Alexander Malov & Mike Lu (v. 2.02)
Version: 1.1
Author: <a href="http://www.dynamick.it">Michele Gobbi</a> 
*/


// CONFIGURATION 
$destinationDir="/mat/thumb/";  //  <-- this is the cache dir. Make sure it exists! 
$defaultImageWidth=235; 
$defaultImageHeight=61; 
$defaultImageType=1; //0=stretch image, 1=resize&crop image, 2=resize image 
$defaultLimit=5;
$defaultLen=10;
$defaultBefore_title = '<h6>'; 
$defaultAfter_title = '</h6>'; 
$defaultBefore_post = '<p>'; 
$defaultAfter_post = '</p>'; 
$defaultShow_pass_post = false; 
$defaultShow_excerpt = false;
$no_related_post = 'No related posts';

// Begin setup
if(!class_exists("Image_Toolbox")) include_once ("class.ImageToolbox.php");

global $ran_plugin;
if (! isset($ran_plugin)) {
  $ran_plugin = true;
  if (isset($_REQUEST['setup'])) // Setup is initiated using related-posts.php?setup
  {
    global $file_path, $user_level, $wpdb;
    require_once( dirname(__FILE__) . '/../../../' . 'wp-config.php');
  	require_once( dirname(__FILE__) . '/../../../' . '/wp-includes/classes.php');
  	require_once( dirname(__FILE__) . '/../../../' . '/wp-includes/functions.php');
      
    if (isset($user_level) && $user_level < 8)
      die ("Sorry, you must be at least a level 8 user."); // Make sure that user has sufficient priveleges

    // SQL query to setup the actual full-text index
		require(dirname(__FILE__).'/../../../' .'wp-config.php');
		global $table_prefix;
		$sql_run = 'ALTER TABLE `'.$table_prefix.'posts` ADD FULLTEXT `post_related` ( `post_name` ,'
        . ' `post_content` )';
		$sql_result = $wpdb->query($sql_run);
  	die ("Congratulations! Full text index was created successfully!");
  }
}
// End setup

// Begin Related Posts
function related_posts($limit='', $len='', $before_title = '', $after_title = '', $before_post = '', $after_post = '', $show_pass_post = "", $show_excerpt = "") {
  global $defaultImageWidth, $defaultImageHeight, $defaultImageType, $defaultLimit, $defaultLen, $defaultBefore_title, $defaultAfter_title, $defaultBefore_post, $defaultAfter_post, $defaultShow_pass_post, $defaultShow_excerpt;
  global $wpdb, $post;

	// Get option values from the parameters or options page or default values
	if ($limit=="" or $limit==0) $limit = get_option('rp_limit');
  if ($limit=="" or $limit==0) $limit = $defaultLimit;

	if ($len=="" or $len==0) $len = get_option('rp_len');
  if ($len=="" or $len==0) $len = $defaultLen;
  
	if ($before_title == "") $before_title = stripslashes(get_option('rp_before_title'));
	if ($before_title == "") $before_title = $defaultBefore_title;
  
  if ($after_title == "") $after_title = stripslashes(get_option('rp_after_title'));
  if ($after_title == "") $after_title = $defaultAfter_title;
  
	if ($before_post == "") $before_post = stripslashes(get_option('rp_before_post'));
  if ($before_post == "") $before_post = $defaultBefore_post;
  
	if ($after_post == "") $after_post = stripslashes(get_option('rp_after_post'));
  if ($after_post == "") $after_post = $defaultAfter_post;
  
	if ($show_pass_post == "") $show_pass_post = get_option('rp_show_pass_post');
  if ($show_pass_post == "") $show_pass_post = $defaultShow_pass_post;

	if ($show_excerpt == "") $show_excerpt = get_option('rp_show_excerpt');
  if ($show_excerpt == "") $show_excerpt = $defaultShow_excerpt;
	
	if ($imageWidth == "") $imageWidth = get_option('rp_imageWidth');
  if ($imageWidth == "") $imageWidth = $defaultImageWidth;
	
	if ($imageHeight == "") $imageHeight = get_option('rp_imageHeight');
  if ($imageHeight == "") $imageHeight = $defaultImageHeight;
	
	if ($imageType == "") $imageType = get_option('rp_imageType');
  if ($imageType == "") $imageType = $defaultImageType;
	
	// Fetch keywords
	$postcustom = get_post_custom_values('keyword');
	if (!empty($postcustom)) {
		$values = array_map('trim', $postcustom);
		$terms = implode($values, ' ');
	} else {
    $terms = str_replace('-', ' ', $post->post_name);
  }

	// Make sure the post is not from the future
	$time_difference = get_settings('gmt_offset');
	$now = gmdate("Y-m-d H:i:s",(time()+($time_difference*3600)));
	
	// Primary SQL query
    $sql = "SELECT ID, post_title, post_content,"
         . "MATCH (post_name, post_content) "
         . "AGAINST ('$terms') AS score "
         . "FROM $wpdb->posts WHERE "
         . "MATCH (post_name, post_content) "
         . "AGAINST ('$terms') "
		 . "AND post_date <= '$now' "
         . "AND (post_status IN ( 'publish',  'static' ) && ID != '$post->ID') ";
    if ($show_pass_post=='false') { $sql .= "AND post_password ='' "; }
    $sql .= "ORDER BY score DESC LIMIT ".$limit;
    $results = $wpdb->get_results($sql);
    $output = '';
    if ($results) {
		foreach ($results as $result) {
			$title = stripslashes(apply_filters('the_title', $result->post_title));
			$permalink = get_permalink($result->ID);
      $post_content = strip_tags($result->post_content);
			$post_content = stripslashes($post_content);
      //************************* DYNAMICK ******************************
      $entryStart=0;
      $imgStart=strpos($result->post_content,"src=\"",$entryStart);
      $imgStop=strpos($result->post_content,"\"",$imgStart+7);
      $img=substr($result->post_content,$imgStart+5,$imgStop-$imgStart-5);
      if (strpos($img,"http://")!==false) $img=substr($img,strpos($img,"/",8));
      
      $pathinfoArr=pathinfo($img);
      if (intval($imgStart)==0 or ($pathinfoArr["extension"]!="jpg" and $pathinfoArr["extension"]!="gif" and $pathinfoArr["extension"]!="png")) $img="";
      else {
        if (!file_exists(getenv("DOCUMENT_ROOT").$img)) return;
        $dest=$destinationDir.$imageWidth."x".$imageHeight."-".basename($img);
        $dest=preg_replace ('/\.(gif|jpg|png)/', '', $dest).".jpg";
        
        if (!file_exists(getenv("DOCUMENT_ROOT").$dest)) {
          list($w, $h, $t, $a) = getimagesize(getenv("DOCUMENT_ROOT").$img);
          $thumbnail=new Image_Toolbox(getenv("DOCUMENT_ROOT").$img);
          $thumbnail->setResizeMethod('resize');
          $thumbnail->newOutputSize($imageWidth,$imageHeight,$imageType,false,'#FFFFFF');
          // you can add a layer on your image like a logo, a fade or a frame.
          // $thumbnail->addImage('./img/logo.png');
          // $thumbnail->blend('right','bottom');
          $thumbnail->save(getenv("DOCUMENT_ROOT").$dest,"jpg");
        }

        $img="<a href=\"$permalink\" rel=\"bookmark\" title=\"Permanent Link: $title \" class=\"relatedImgLink\">\n";
        $img.="<img src=\"".$dest."\" width=\"".$imageWidth."\" height=\"".$imageHeight."\" alt=\"$title\" class=\"relatedImg\"/></a>";

      }

      $output .="<li>";
		}
		echo "<ul>".$output."</ul>";
	} else {
        echo $before_title.$no_related_post.$after_title;
    }
}
// End Related Posts


// Begin Keywords
function find_keywords($id) {
	global $wpdb;
	$content = $wpdb->get_var("SELECT post_content FROM $wpdb->posts WHERE ID = '$id'");
	if (preg_match_all('/<!--kw=([\s\S]*?)-->/i', $content, $matches, PREG_SET_ORDER)) {
		$test = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '$id' AND meta_key = 'keyword'");
		if (!empty($test)) {
			$output = explode(' ', $test);
		} else {
			$output = array();
		}
		foreach($matches as $match) {
			$output = array_merge($output, explode(' ', $match[1]));
		}
		$output = array_unique($output);
		$keywords = implode(' ', $output);
		if (!empty($test)) {
      		$results=  $wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '$keywords' WHERE post_id = '$id' AND meta_key = 'keyword'");
		} else {
			$results = $wpdb->query("INSERT INTO $wpdb->postmeta (post_id,meta_key,meta_value) VALUES ('$id', 'keyword', '$keywords')");
		}
		$content = format_to_post(balanceTags(preg_replace("/<!--kw=([\s\S]*?)-->/i", "<!--$1-->", $content)));
		$results = $wpdb->query("UPDATE $wpdb->posts SET post_content = '$content' WHERE ID = '$id'");
	}
	return $id;
}

// End Keywords

// Begin Related Posts Options

function rp_subpanel() {
  if (isset($_POST['update_rp'])) {
    $option_limit = $_POST['limit'];
    $option_len = $_POST['len'];
    $option_before_title = $_POST['before_title'];
    $option_after_title = $_POST['after_title'];
    $option_before_post = $_POST['before_post'];
    $option_after_post = $_POST['after_post'];
    $option_show_pass_post = $_POST['show_pass_post'];
    $option_show_excerpt = $_POST['show_excerpt'];
    $option_imageWidth = $_POST['imageWidth'];
    $option_imageHeight = $_POST['imageHeight'];
    $option_imageType = $_POST['imageType'];

    update_option('rp_limit', $option_limit);
    update_option('rp_len', $option_len);
    update_option('rp_before_title', $option_before_title);
    update_option('rp_after_title', $option_after_title);
    update_option('rp_before_post', $option_before_post);
    update_option('rp_after_post', $option_after_post);
    update_option('rp_show_pass_post', $option_show_pass_post);
    update_option('rp_show_excerpt', $option_show_excerpt);

    update_option('rp_imageWidth', $option_imageWidth);
    update_option('rp_imageHeight', $option_imageHeight);
    update_option('rp_imageType', $option_imageType);
    
    ?> <div class="updated"><p>Options saved!</p></div> <?php
  }
  ?>

	<div class="wrap">
		<h2>Related Posts Thumbnail Options</h2>
    <script type="text/javascript"><!--
    google_ad_client = "pub-0492991953798066";
    google_ad_width = 728;
    google_ad_height = 90;
    google_ad_format = "728x90_as";
    google_ad_type = "text_image";
    //2007-11-16: Related Post Thumbnail
    google_ad_channel = "3029355243";
    google_color_border = "FFFFFF";
    google_color_bg = "FFFFFF";
    google_color_link = "005aab";
    google_color_text = "4A4A4A";
    google_color_url = "4A4A4A";
    //-->
    </script>
    <script type="text/javascript"
      src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
    </script>
    
		<form method="post">
		<fieldset class="options">
		<table>
			<tr>
				<td><label for="limit">How many related posts would you like to show?</label>:</td>
				<td><input name="limit" type="text" id="limit" value="<?php echo get_option('rp_limit'); ?>" size="2" /></td>
			</tr>
		 	<tr>
        <td><label for="before_title">Before</label> / <label for="after_title">After (Post Title) </label>:</td>
				<td><input name="before_title" type="text" id="before_title" value="<?php echo htmlspecialchars(stripslashes(get_option('rp_before_title'))); ?>" size="10" /> / <input name="after_title" type="text" id="after_title" value="<?php echo htmlspecialchars(stripslashes(get_option('rp_after_title'))); ?>" size="10" /><em><small> For example: &lt;li&gt;&lt;/li&gt; or &lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>
			<tr>
				<td>Show excerpt?</td>
        <td>
          <select name="show_excerpt" id="show_excerpt">
            <option <?php if(get_option('rp_show_excerpt') == 'false') { echo 'selected'; } ?> value="false">False</option>
            <option <?php if(get_option('rp_show_excerpt') == 'true') { echo 'selected'; } ?> value="true">True</option>
          </select>
				</td> 
			</tr>
			<tr>
				<td><label for="len">Excerpt length (No. of words):</label></td>
				<td><input name="len" type="text" id="len" value="<?php echo get_option('rp_len'); ?>" size="2" /> 
			</tr>
			<tr>
				<td><label for="before_post">Before</label> / <label for="after_post">After</label> (Excerpt):</td>
				<td><input name="before_post" type="text" id="before_post" value="<?php echo htmlspecialchars(stripslashes(get_option('rp_before_post'))); ?>" size="10" /> / <input name="after_post" type="text" id="after_post" value="<?php echo htmlspecialchars(stripslashes(get_option('rp_after_post'))); ?>" size="10" /><em><small> For example: &lt;li&gt;&lt;/li&gt; or &lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>

			<tr>
				<td><label for="thumb">Thumbnail size</label>:</td>
				<td>
        Width: <input name="imageWidth" type="text" id="imageWidth" value="<?php echo htmlspecialchars(stripslashes(get_option('rp_imageWidth'))); ?>" size="10" /> 
        - Height: <input name="imageHeight" type="text" id="imageHeight" value="<?php echo htmlspecialchars(stripslashes(get_option('rp_imageHeight'))); ?>" size="10" />
        - Type: <select name="imageType">
          	<option <?php if(get_option('rp_imageType') == '')  { echo 'selected'; } ?> value="">default</option>
          	<option <?php if(get_option('rp_imageType') == '0') { echo 'selected'; } ?> value="0">0 - stretch</option>
          	<option <?php if(get_option('rp_imageType') == '1') { echo 'selected'; } ?> value="1">1 - resize + crop</option>
          	<option <?php if(get_option('rp_imageType') == '2') { echo 'selected'; } ?> value="2">2 - resize</option>
        	</select> 
        
				</td>
			</tr>

			<tr>
				<td><label for="show_pass_post">Show password protected posts?</label></td>
				<td>
        	<select name="show_pass_post" id="show_pass_post">
          	<option <?php if(get_option('rp_show_pass_post') == 'false') { echo 'selected'; } ?> value="false">False</option>
          	<option <?php if(get_option('rp_show_pass_post') == 'true') { echo 'selected'; } ?> value="true">True</option>
        	</select> 
				</td>
			</tr>
		</table>
		</fieldset>

		<p><div class="submit"><input type="submit" name="update_rp" value="<?php _e('Save!', 'update_rp') ?>"  style="font-weight:bold;" /></div></p>
        
		</form>       
		
    </div>
    
    <div class="wrap">
      <h2>Instruction</h2>
      <p>Follow these steps:<br/><br/>
          <script type="text/javascript"><!--
          google_ad_client = "pub-0492991953798066";
          google_ad_width = 234;
          google_ad_height = 60;
          google_ad_format = "234x60_as";
          google_ad_type = "text_image";
          //2007-11-16: Related Post Thumbnail
          google_ad_channel = "3029355243";
          google_color_border = "FFFFFF";
          google_color_bg = "FFFFFF";
          google_color_link = "005aab";
          google_color_text = "4A4A4A";
          google_color_url = "4A4A4A";
          //-->
          </script>
          <script type="text/javascript"
          src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
          </script>
      </p>          
      <ol>
        <li>
          If it is the first time, run the SQL script just below.
        </li>
        <li>
          Customize the options in the above box or leave the defaults.
        </li>
        <li>
          Include the lines in example/style.css in your template style.css.<br/>
          Modify them to fit your html structure (DOM) and taste.
        </li>
        <li>
          Move the example/relatedImage.jpg image into your images/ template folder.<br/>
          There's the original psd, so you can adatp it to your design.
        </li>

        <li>
          Insert this line into your template:<br/>
          <strong>&lt;?=related_posts()?&gt;</strong>
        </li>  
      </ol>
      <p>You'll obtain something like <a href="http://www.dynamick.it/related-post-with-thumbnail-942.html" target="_blank">www.dynamick.it</a></p>       
      
      </p>
    </div>

    <div class="wrap">   
      <h2>SQL Index Table Setup</h2>
		  <p>If this is your first time installing this plugin you will have to run <a href="../wp-content/plugins/related-posts.php?setup" onclick="window.open(this.href, 'popupwindow', 'width=400,height=150,scrollbars,resizable'); return false;">this script</a> (opens a new window) in order to create the index table required by the plugin. If this fails, please refer to the readme on how to create it manually.</p>
    </div>
    
    
<?php } 

// End Related Posts Options

function rp_admin_menu() {
	if (function_exists('add_options_page')) {
		//add_options_page('FeedBurner', 'FeedBurner', 8, basename(__FILE__), 'ol_feedburner_options_subpanel');
    add_options_page(__('Related Posts Thumbnail Options'), __('Related Posts Thumbnail Options'), 8, __FILE__, 'rp_subpanel');
	}

}

add_action('edit_post', 'find_keywords', 1);
// add_action('publish_post', 'find_keywords', 1);
add_action('admin_menu', 'rp_admin_menu');

?>
