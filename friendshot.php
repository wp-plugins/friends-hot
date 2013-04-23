<?php
/*
Plugin Name: Friends Hot
Plugin URI: http://www.tiandiyoyo.com
Description: Display new posts of friend's website.懒人专用Wordpress热友插件，用来在自己站点显示友情站点最新的更新文章标题。
Version: 1.0
Author: Tiandi
Author URI: http://www.tiandiyoyo.com
*/

function friendshot_optionpanel() { ?>
	<div class="wrap">
	    <?php screen_icon(); ?>
	    <h2>Friend Hot 设置</h2>			
	    <form method="post" action="" id="friendhotform">
	        <?php
            echo "显示友情站点最新的";
	            $abc = $_POST['friendhotcounts'];
				if (!empty($abc)
					&& check_admin_referer('check-update')
				)  {
					update_option('friendhot_counts',$abc); ?> 
					<input type="text" name="friendhotcounts" id="friendhotcounts" value= <?php echo $abc; ?> size=3 />篇文章。
				<?php } else if(get_option('friendhot_counts') == null) {?>
                    <input type="text" name="friendhotcounts" id="friendhotcounts" value = 1 size=3 />篇文章。
                <?php } else { ?>
				<input type="text" name="friendhotcounts" id="friendhotcounts" value= <?php echo get_option('friendhot_counts') ;?>  size=3 />篇文章。
				<?php } ?>
				(文章数量取决于友站的Feed设置，抓取速度受数量影响，建议设定为1-2，最大值不应该超过友站Feed内的文章数。)
			  
			<?php 
				echo "<br><br>首行显示插件作者博客的最新更新：";
				$abc = get_option('checkfortiandi');
				?>
				<input type="hidden" name="tiandibloghid" value="yes" />
				<?php
				if ($_POST['tiandibloghid'] == 'yes')  {
					if(!empty($_POST['tiandiblog'])) 
						$abc = 'tt';
                    else 
						$abc = 't';
				}
				
					if(!empty($abc) && $abc == 'tt') {
						update_option('checkfortiandi',$abc); ?> 
						<input type="checkbox" name="tiandiblog" value="tt" checked/>
					<?php }
					else if(!empty($abc) && $abc == 't') {
						update_option('checkfortiandi',$abc); ?> 
						<input type="checkbox" name="tiandiblog" value="tt" />
					<?php }
					else { ?>
						<input type="checkbox" name="tiandiblog" value="tt" checked/>
				    <?php }
              
				 echo "<br><br>显示抓取时间：";
				$abc = get_option('checkforcurltime');

				if ($_POST['tiandibloghid'] == 'yes')  {
					if(!empty($_POST['displaycurtime'])) 
						$abc = 'qq';
                    else 
						$abc = 'q';
				}
				
					if(!empty($abc) && $abc == 'qq') {
						update_option('checkforcurltime',$abc); ?> 
						<input type="checkbox" name="displaycurtime" value="qq" checked/>
					<?php }
					else if(!empty($abc) && $abc == 'q') {
						update_option('checkforcurltime',$abc); ?> 
						<input type="checkbox" name="displaycurtime" value="qq" />
					<?php }
					else { ?>
						<input type="checkbox" name="displaycurtime" value="qq" />
				    <?php }

	            echo "<br><Br>页面缓存时间(限定最小值30秒)：";
	            $abc = $_POST['cachetime'];
				if (!empty($abc)
					&& check_admin_referer('check-update')
				)  {
					update_option('cachetimecount',$abc);
					$curl_content = get_transient('mycurlcached');
				    if(!empty($curl_content)){
						set_transient('mycurlcached',$curl_content,$abc >30 ? $abc:30);
					}
					?> 
					<input type="text" name="cachetime" id="cachetime" value= <?php echo $abc; ?> size=3 />秒。
				<?php } else if(get_option('cachetimecount') == null) {?>
                    <input type="text" name="cachetime" id="cachetime" value = 14400 size=5 />秒。
                <?php } else { ?>
				<input type="text" name="cachetime" id="cachetime" value= <?php echo get_option('cachetimecount') ;?>  size=5 />秒。
				<p>插件使用：
<br>1.调用前先确认哪些好友的站点支持本插件。请先在浏览器内输入友链+”/feed”，确定好友站点是否支持Feed。比如千丝海阁的Feed地址为http://www.tiandiyoyo.com/feed，如果能正常显示，则说明支持本插件。非WP结构的博客应该都不支持。
<br>2.调用方法为在文章内用[gfns]来调用，可使用参数cat，使用方法为[gfns cat="好友"] ，则表示显示链接分类中"好友"分类下的所有链接的最新更新，不加参数则默认调用“热友”目录下的所有链接。
<br>3.建议不要在首页直接使用Friends Hot，而是新建一个页面，在该页面内调用。
<br>4.将支持本插件的站点链接分类目录改为“热友”或者上面第二步内你设置的参数cat的目录。
<br>5.第一次使用时，即点第三步生成的页面时由于没有缓存信息，可能生成的会比较慢（当然如果你配置完了，不去点这个页面，而是等到15分钟后，系统会自动生成缓存页面）。
				<?php }
              submit_button(); 
			  wp_nonce_field('check-update'); 
			  ?>
	    </form>
	</div>
	<?php
}


function friendshot_read_news_from_url($url,$display) {
	set_time_limit(0);
	$counts = get_option('friendhot_counts');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//	curl_setopt($ch, CURLOPT_REFERER, bloginfo('url'));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true); 
	$contents = curl_exec($ch);
	$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($response_code <> '200') 
		$message = "无法获得数据，错误代码:". $response_code. "<br>" ;
	else {
	for($i=0;$i<$counts;$i++) {
		$contents = strstr($contents,"<item>");
		$contents = strstr($contents,"<title>");
		$len = strpos($contents,"</title>");
		$topicname = substr($contents,7,$len);  
		$contents = strstr($contents,"<link>");
		$len = strpos($contents,"</link>");		
		$topicurl = substr($contents,6,$len-6);  
		$message = $message . "<a href = " .$topicurl. " target=_blank>" .$topicname. "</a></a>";
		$contents = strstr($contents,"<pubDate>");
		$len = strpos($contents,"</pubDate>");
		$topicdate = substr($contents,13,$len-28);  
		$message = $message . " " .$topicdate . "<br>";
	}
	}
	if($display == 'false')
		return $message;
	else 
		echo $message;
}
function friendshot_get_friend_news($atts) {
	extract(shortcode_atts(array(
	"cat" => '热友'), $atts));
	$mybook = get_bookmarks(array(
				'orderby'        => 'name',
				'order'          => 'ASC',
				'category_name'  => $cat
                          ));
	if(!empty($mybook))
		update_option('curlgetbook',$mybook);
	if (get_option('checkfortiandi') == 'tt') {
		echo "千丝海阁<br>";
		if(get_option('checkforcurltime') == 'qq') {
			$bt = time();
		}
		friendshot_read_news_from_url("http://www.tiandiyoyo.com/feed/","true");
		if(get_option('checkforcurltime') == 'qq') {
			echo "time " . (time() - $bt) ." s.<br>";
		}
	}
	$curl_content = get_transient('mycurlcached');
	if(false === $curl_content){
		echo "<br>以下为网站实时信息：<br>";
		foreach ($mybook as $mybooks) {
			$curl_content = $curl_content . $mybooks->link_name."<br>";
			if(get_option('checkforcurltime') == 'qq') {
				$bt = time();
			}
			$curl_content = $curl_content . friendshot_read_news_from_url($mybooks->link_url."/feed","false");
			if(get_option('checkforcurltime') == 'qq') {
				$curl_content = $curl_content . "time " . (time() - $bt) ." s.<br>";
			}
		}	
		$mytime = get_option('cachetimecount');
		if($mytime < 30 )
			$mytime = 30;
		set_transient('mycurlcached',$curl_content,$mytime);
		update_option('fhupdate',time());
	}
	else {
		echo "<br>以下为友链的缓存信息：";
		$abc = get_option('fhupdate');
		if(!empty($abc)) 
			echo "(缓存时间在" . date("Y-m-d H:i:s", $abc + 8 * 3600) . ")"; 	
		echo "<br>";
	}
	echo $curl_content;
}

add_shortcode('gfns','friendshot_get_friend_news');

add_filter('cron_schedules', 'cron_add_minute'); 
function cron_add_minute( $schedules )
{
	$schedules['mins'] = array(
		'interval' => 900, 
		'display' => __('15 minutes')
	);
	return $schedules;
}

if (!wp_next_scheduled('friendshot_hook')) {
    wp_schedule_event( time(), 'mins', 'friendshot_hook' );
}

add_action( 'friendshot_hook', 'friendshot_hook_fun' );

function friendshot_hook_fun() {

	$mybook = get_option('curlgetbook');
	if(empty($mybook)) return;
	$curl_content = get_transient('mycurlcached');
	if(false === $curl_content){
		foreach ($mybook as $mybooks) {
			$curl_content = $curl_content . $mybooks->link_name."<br>";
			if(get_option('checkforcurltime') == 'qq') {
				$bt = time();
			}
			$curl_content = $curl_content . friendshot_read_news_from_url($mybooks->link_url."/feed","false");
			if(get_option('checkforcurltime') == 'qq') {
				$curl_content = $curl_content . "time " . (time() - $bt) ." s.<br>";
			}
		}	
		$mytime = get_option('cachetimecount');
		if($mytime < 30 )
			$mytime = 30;
		set_transient('mycurlcached',$curl_content,$mytime);
		update_option('fhupdate',time());
	}
}

function friendshot_deactivation(){
	wp_clear_scheduled_hook('myupdate_hook');
}

register_deactivation_hook(basename(__FILE__),' friendshot_deactivation');


function friendshot_admin_actions() {
    add_options_page("Friendshot", "Friends hot", 1, "Friends-hot", "friendshot_optionpanel"); 
}
    add_action('admin_menu', 'friendshot_admin_actions');  
?>