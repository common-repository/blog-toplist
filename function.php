<?php

//class for widget
class Btl_Widget extends WP_Widget {
	function Btl_Widget() {
        $widget_ops = array( 'description' => __( 'Display Blog Toplist in your sidebar.') );
        $this->WP_Widget(false, __('Blog Toplist'), $widget_ops);
	}

	function form($instance) {
		// outputs the options form on admin
		$defaults = array( 
				'title' => 'Web/Blog Toplist',
				'lang' => 'en'
			);
		
		$instance = wp_parse_args((array) $instance, $defaults);
   ?>
        <p>
			<label><?php _e('Title:') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked($instance['alexa'], 'on'); ?> id="<?php echo $this->get_field_id('alexa'); ?>" name="<?php echo $this->get_field_name('alexa'); ?>" />
			<label for="<?php echo $this->get_field_id('follow'); ?>"><?php _e('Show Alexa', 'appthemes') ?></label>
			<br />
			<input class="checkbox" type="checkbox" <?php checked($instance['technorati'], 'on'); ?> id="<?php echo $this->get_field_id('technorati'); ?>" name="<?php echo $this->get_field_name('technorati'); ?>" />
			<label for="<?php echo $this->get_field_id('connect'); ?>"><?php _e('Show Technorati', 'appthemes') ?></label>
			<br />
			<input class="checkbox" type="checkbox" <?php checked($instance['pagerank'], 'on'); ?> id="<?php echo $this->get_field_id('pagerank'); ?>" name="<?php echo $this->get_field_name('pagerank'); ?>" />
			<label for="<?php echo $this->get_field_id('connect'); ?>"><?php _e('Show Pagerank', 'appthemes') ?></label>
		</p>
   <?php
	}

	function update($new_instance, $old_instance) {
		// processes widget options to be saved
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['alexa'] = $new_instance['alexa'];
		$instance['technorati'] = $new_instance['technorati'];		
		$instance['pagerank'] = $new_instance['pagerank'];		
		return $instance;
	}

	function widget($args, $instance) {
        extract($args);

		$title = apply_filters('widget_title', $instance['title'] );
		$newin = isset( $instance['newin'] ) ? $instance['newin'] : false;
		$alexa = isset($instance['alexa']) ? $instance['alexa'] : false;
		$technorati = isset($instance['technorati']) ? $instance['technorati'] : false;
		$pagerank = isset($instance['pagerank']) ? $instance['pagerank'] : false;
		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;
		
		if (function_exists("btlWidget")) echo btlWidget(10,$alexa,$technorati,$pagerank);

		echo $after_widget;		
	}

}

//for widget display
function btlWidget($limit,$show1,$show2,$show3){
	global $wpdb;
	$relatedw = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."blogtoplist where blog_status='1' order by blog_rank_alexa desc limit $limit;");

	$retval .= '<table width="100%" cellpadding="2" cellspacing="2" class="btl_table">';
	$retval .= '<tr><th>Rank</th><th>Web/Blog</th>';
	if($show1=="on"){ $retval .= '<th title="Alexa">AX</th>';}else{ $retval .= '';}
	if($show2=="on"){ $retval .= '<th title="Technorati">TR</th>';}else{ $retval .= '';}
	if($show3=="on"){ $retval .= '<th title="Pagerank">PR</th>';}else{ $retval .= '';}
	$retval .= '</tr>';

	if ( $relatedw ) {
		$ii=0;
		foreach($relatedw as $r) {
			$ii++;
			$ii % 2 == 0 ? $bgclr = "row1" : $bgclr = "row2";
			if (strlen($r->blog_descr) > 20){
			$descr = substr($r->blog_descr, 0, 20)."...";
			}else{
			$descr = $r->blog_descr;
			}
			$retval .= '<tr class="'.$bgclr.'"><td align="center">'.$ii.'</td><td><a href="http://'.$r->blog_url.'" title="'.$r->blog_title.'" target="_blanl">'.$r->blog_url.'</a></td>';
			$bra = number_format($r->blog_rank_alexa,0);
			$brt = number_format($r->blog_rank_techno,0);
			$brp = number_format($r->blog_rank_page,0);
			if($show1=="on"){ $retval .= '<td align="center"><a href="http://www.alexa.com/siteinfo/http://www.'.$r->blog_url.'" target="_blank">'.$bra.'</a></td>';}else{ $retval .= '';}
			if($show2=="on"){ $retval .= '<td align="center">'.$brt.'</td>';}else{ $retval .= '';}
			if($show3=="on"){ $retval .= '<td align="center">'.$brp.'</td>';}else{ $retval .= '';}
			$retval .= '</tr>';
		}
	} else {
		$retval .= '
<tr class="row1"><td colspan="6" align="center">No related links found</td></tr>';
	}

	$retval .= '</table>';
	$retval .= '<div class="plugin"><a href="http://wordpress.org/extend/plugins/blog-toplist/" target="_blank">Blog Toplist - WP Plugins</a></div><div style="clear:both"></div>';
	
	return $retval;
}


//meta pattern
function getMetaTitle($content){
	$pattern = "|<[\s]*title[\s]*>([^<]+)<[\s]*/[\s]*title[\s]*>|Ui";
	if(preg_match($pattern, $content, $match))
	return $match[1];
	else
	return false;
}

//delete actions from listing page
function btl_delete($bid) {
	global $wpdb;
	$todel = $wpdb->query("DELETE FROM ".$wpdb->prefix."blogtoplist WHERE id='".$bid."';");
	if(!$todel){
		echo '<div class="error"><p><strong>Error!</strong> Problem to delete blog.</p></div>';
	}else{
		echo '<div class="updated"><p><strong>Delete success.</strong></p></div>';
	}
}

//edit actions from listing page
function btl_edit($buid) {
	global $wpdb;
	
	$binfo =  $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."blogtoplist where id='".$buid."';");
	$buser = get_userdata($binfo->user_id);
	$b_user = $buser->user_login;
	$b_url = $binfo->blog_url;

	if($_POST){
		$ibid = $_POST['blog_id'];	
		$burl = $_POST['blog_url'];	
		$bstatus = $_POST['blog_status'];
		$bupdate = $wpdb->query("UPDATE ".$wpdb->prefix."blogtoplist SET blog_url='".$burl."', blog_status='".$bstatus."' where id='".$buid."';");
		if(!$bupdate){
			$content .= '<div class="error"><p><strong>Error!</strong> Problem to update blog.</p></div>';
		}else{
			$content .= "
			<script type=\"text/javascript\"> 
			setTimeout(\"location='?page=blog-toplist'\", 2000);
			</script>
			<div class=\"updated\"><p><strong>Editing success. Please wait..</strong></p></div>";
		}
	}	
	$content.=<<<EOF
			<FORM id="btlup" action="" method="POST">
			<table class="form-table ad-meta-table">
				<tr>
					<th style="width: 20%"><label><strong>Submitted 
					By :</strong></label></th>
					<td>$b_user</td>
					<td><a href="?page=blog-toplist" title="Cancel" class="button-secondary cancel">Cancel</a></td>
				</tr>
				<tr>
					<th style="width: 20%"><label><strong>Blog URL :</strong></label></th>
					<td>
					<div>http://www.<input type="text" id="blog_url" name="blog_url" value="$b_url" style="width:200px;"></div>
					</td>
					<td><input type="submit" name="b_edit" id="b_edit" class="button-primary" value="Edit Blog" accesskey="s"  /></td>
				</tr>
				<tr>
					<th style="width: 20%"><label><strong>Blog Status:</strong></label></th>
					<td>
EOF;
	$content.="	
					<select name=\"blog_status\">
						<option value=\"0\" ". selected( $binfo->blog_status, 0 ,false) .">Pending</option>
						<option value=\"1\" ". selected( $binfo->blog_status, 1 ,false ) .">Active</option>
						<input type=\"hidden\" id=\"blog_id\" name=\"blog_id\" value=\"".$bid."\">
					</select>					
	";
	$content.=<<<EOF
					</td>
					<td>&nbsp;</td>
				</tr>
			</table>
			</FORM>
EOF;

	return $content;
}

//update actions from listing page
function btl_update($bid) {
	global $wpdb;
	
	$burl = $wpdb->get_row("SELECT blog_url FROM ".$wpdb->prefix."blogtoplist where id='".$bid."';");
	//get url data
	$aurl = $burl->blog_url;
	$gurl = 'http://www.'.$burl->blog_url;
	//echo $gurl;
	$content = file_get_contents($gurl);
	$title = getMetaTitle($content);
	$meta_data= get_meta_tags($gurl);
	//echo $title."<br>";
	//echo $meta_data['description'];

	$response = "http://data.alexa.com/data?cli=10&dat=snbamz&url=$aurl";
	$xml = file_get_contents($response);
	$sxml = simplexml_load_string($xml);
	$data = json_encode($sxml);
	
	$userdata = json_decode($data,true);
	$mydata = $userdata['SD']['1']['POPULARITY']['@attributes'];
	//echo $mydata['TEXT'];
	if($mydata['TEXT']==""){
		$adalexa = 'n/a';
	}else{
		$adalexa = $mydata['TEXT'];
	}
	
	$toupdate = $wpdb->query("UPDATE ".$wpdb->prefix."blogtoplist SET blog_title='".$title."', blog_descr='".$meta_data['description']."',blog_rank_alexa='".$adalexa."',blog_status='1' where id='".$bid."';");
	if(!$toupdate){
		echo '<div class="error"><p><strong>Error!</strong> Problem to update blog.</p></div>';
	}else{
		echo '<div class="updated"><p><strong>Update and review success.</strong></p></div>';
	}
}

function btl_help($contextual_help, $screen_id, $screen) {
	if (preg_match("/blog-toplist/i", $screen_id)) {
		$contextual_help = '<h2>'.__('Blog Toplist Help Center.').'</h2>'.
		'<p>'.sprintf( __("If you're having problems with this plugin, please refer to its <a href='%s' target='_blank'>FAQ page</a>."), 'http://wordpress.org/extend/plugins/blog-toplist/faq/' ).'</p>';
	}
	return $contextual_help;
}

function btl_page_scripts() {
	if (isset($_GET['page']) && $_GET['page'] == 'blog-toplist-settings') {
		wp_enqueue_script('postbox');
		wp_enqueue_script('dashboard');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('media-upload');
	}
}

function btl_page_styles() {
	if (isset($_GET['page']) && $_GET['page'] == 'blog-toplist-settings') {
		wp_enqueue_style('dashboard');
		wp_enqueue_style('thickbox');
		wp_enqueue_style('global');
		wp_enqueue_style('wp-admin');
	}
}

function btl_postbox($id, $title, $content) {
?>
	<div id="<?php echo $id; ?>" class="postbox">
		<div class="handlediv" title="Click to toggle"><br /></div>
		<h3 class="hndle"><span><?php echo $title; ?></span></h3>
		<div class="inside">
			<?php echo $content; ?>
		</div>
	</div>
<?php
}	

function btl_config(){
	
	if($_POST){
		$opt_ar = $_POST['opt_ar'];
		update_option( 'btl_ar', $opt_ar );	
		$opt_tr = $_POST['opt_tr'];	
		update_option( 'btl_tr', $opt_tr );	
		$opt_pr = $_POST['opt_pr'];	
		update_option( 'btl_pr', $opt_pr );	
		$discon .= "
		<script type=\"text/javascript\"> 
		setTimeout(\"location='?page=blog-toplist-settings'\", 2000);
		</script>
		<div class=\"updated\"><p><strong>Editing success. Please wait..</strong></p></div>";
	}else{
	$discon .= "<label><strong>Display Settings</strong></label>
	<p>Enable/Disable show ranking for shortcode [bloglist top=\"10\"]</p>";
	$discon .= "<form id=\"btlupop\" action=\"\" method=\"POST\"><table><tr>";
	$discon .= "<td><label>Show Alexa Ranking :</label></td>";
	$discon.="	
					<td>
					<select name=\"opt_ar\">
						<option value=\"off\" ". selected( get_option('btl_ar'), 'off' ,false) .">No</option>
						<option value=\"on\" ". selected( get_option('btl_ar'), 'on' ,false ) .">Yes</option>
					</select>&nbsp;&nbsp;&nbsp;
					</td>					
	";
	$discon .= "<td><label>Show Technorati Ranking :</label></td>";
	$discon.="	
					<td>
					<select name=\"opt_tr\">
						<option value=\"off\" ". selected( get_option('btl_tr'), 'off' ,false) .">No</option>
						<option value=\"on\" ". selected( get_option('btl_tr'), 'on' ,false ) .">Yes</option>
					</select>&nbsp;&nbsp;&nbsp;
					</td>					
	";
	$discon .= "<td><label>Show PageRank :</label></td>";
	$discon.="	
					<td>
					<select name=\"opt_pr\">
						<option value=\"off\" ". selected( get_option('btl_pr'), 'off' ,false) .">No</option>
						<option value=\"on\" ". selected( get_option('btl_pr'), 'on' ,false ) .">Yes</option>
					</select>
					</td>					
	";
	$discon .= "</tr><tr><td colspan=\"6\"><input type=\"submit\" name=\"b_edit_op\" id=\"b_edit_op\" class=\"button-primary\" value=\"Edit Display\" /></td></tr></table></form>";
	}
	return $discon;
}

//for settings page
function btl_settings() {
    
	$blogid = $_GET['blog'];
	?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br/></div>
        <h2>Blog Toplist - Settings</h2>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container" style="width: 70%;">
	<?php
	if($blogid!=""){
	?>
                        <div id="normal-sortables" class="meta-box-sortables">
                            <?php btl_postbox('btl-set','Edit Blog',btl_edit($blogid));?>
                        </div>
	<?php
    }else{
	?> 
                        <div id="normal-sortables" class="meta-box-sortables">
                            <?php btl_postbox('btl-set','Blog Toplist Infomation',btl_config());?>
                        </div>
	<?php
	}
	?>
                    </div>
                    <div class="postbox-container" style="width: 25%;">
                        <div id="side-sortables" class="meta-box-sortables">
                            <?php btl_postbox('btl-like','Found a bugs?','<p>If you\'ve found a bug in this plugin, please submit it into <a href="http://wordpress.org/tags/blog-toplist?forum_id=10" target="_blank">forum</a> with a clear description</p>');?>
                        </div>
                    </div>
                </div>
                <form style="display: none" method="get" action="">
                    <p>
                    <input type="hidden" id="closedpostboxesnonce" name="closedpostboxesnonce" value="83015ad129" />
                    <input type="hidden" id="meta-box-order-nonce" name="meta-box-order-nonce" value="91881bfd90" />
                    </p>
                </form>
                <div class="clear">
            </div>
        </div>
    </div>
    <?php
}

function chk_db(){
	global $wpdb;
	
	$tochkdb = $wpdb->get_results("SHOW COLUMNS FROM ".$wpdb->prefix."blogtoplist;");
	$selcol = $tochkdb[5]->Field;
	$selcolvalue = $tochkdb[5]->Default;
	if($selcolvalue=="0"){
		$wpdb->query("ALTER TABLE `".$wpdb->prefix."blogtoplist` MODIFY COLUMN `blog_rank_alexa` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'n/a',
 MODIFY COLUMN `blog_rank_techno` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'n/a',
 MODIFY COLUMN `blog_rank_page` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'n/a';");
	}else{
 		$wpdb->query("UPDATE ".$wpdb->prefix."blogtoplist SET blog_rank_alexa='n/a',blog_rank_techno='n/a',blog_rank_page='n/a' where blog_rank_alexa='0' or blog_rank_techno='0' or blog_rank_page='0';");
	}
	//option exist
	if(get_option('btl_ar')==""){
		add_option( 'btl_ar', 'on', '', 'yes' );
	}elseif(get_option('btl_tr')==""){
		add_option( 'btl_tr', 'on', '', 'yes' );
	}elseif(get_option('btl_pr')==""){
		add_option( 'btl_pr', 'on', '', 'yes' );
	}
}
?>