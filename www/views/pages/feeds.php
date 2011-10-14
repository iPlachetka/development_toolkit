<? foreach(@$alternate as $alt): ?> 
<? if(@$alt['type'] == 'text/html'): ?>
	<h2><a href="<?= @$alt['href']; ?>"><?= @$title; ?></a></h2>  
<? endif; ?> 
<? endforeach; ?>  

<p>
	Source <a href="<?= @$origin['htmlUrl']; ?>"><?= $origin['title']; ?></a>
	<br/>
	Author: <?= @$author; ?>   
	<br/>
	Published: <?= @get_relative_time($published); ?>      
</p>
       
<div>
	<?= @$content['content']; ?>
	<? if(@$alt['type'] == 'text/html'): ?>
		<a href="<?= @$alt['href']; ?>"><?= @$alt['href']; ?></a>  
	<? endif; ?>
</div>	  