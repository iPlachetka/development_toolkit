  <div class="sixteen columns">
	<!-- <h1 class="remove-bottom" style="margin-top: 40px">Skeleton</h1> -->
	<!-- <h5>Version 1.0.3</h5>   -->
	<p id="pursue_quote">
		<?= $text; ?>
	</p>  
	<hr class="faded"/>   
	<? if(!empty($from_user)): ?>
	<div style="text-align: right;">
	    <a href="http://twitter.com/#!/<?= $from_user; ?>"><img style="float: right; margin-right: 10px;" src="<?= $profile_image_url; ?>"/></a> 
	   
	 	<div style="float: right;  margin-right: 10px;">
	 	<a href="http://twitter.com/#!/<?= $from_user; ?>"><?= $name; ?> <small><?= twitter_name($from_user); ?></small></a> <br/>of <?= $location; ?> @ <?= get_relative_time($created_at); ?> 
		</div>
		<!-- 
		<a href="http://twitter.com/#!/<?= $from_user; ?>/statuses/<?= $id_str; ?>">View this Tweet</a>  
		from <?= html_entity_decode($source); ?>
		<a id="tweet" title="Lorem ipsum dolor sit amet, consectetur adipiscing elit." href="http://twitter.com/intent/tweet?original_referer=http%3A%2F%2Flocalhost%3A8888">Tweet</a>
		-->  
		
	</div>   
	<? endif; ?>
	
</div>