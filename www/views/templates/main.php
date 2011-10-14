<!doctype html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>

	<!-- Basic Page Needs
  ================================================== -->
	<meta charset="utf-8" />
	<title><?= $page_name; ?> <?= $name; ?> <?= twitter_name($from_user); ?></title>
	<meta name="description" content="">
	<meta name="author" content="">
	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	
	<!-- Mobile Specific Metas
  ================================================== -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" /> 
	
	<!-- CSS
  ================================================== -->
	<link rel="stylesheet" href="/styles/base.css">
	<link rel="stylesheet" href="/styles/skeleton.css">
	<link rel="stylesheet" href="/styles/layout.css">
	
	<!-- Favicons
	================================================== -->
	<link rel="shortcut icon" href="/images/favicon.ico">
	<link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/images/apple-touch-icon-72x72.png" />
	<link rel="apple-touch-icon" sizes="114x114" href="/images/apple-touch-icon-114x114.png" />
	
</head>
<body>





	<!-- Primary Page Layout
	================================================== -->
	
	<!-- Delete everything in this .container and get started on your own site! -->
    <div id="distance"></div>
	<div class="container" id="content">	   
     
		<?= $content_for_layout; ?>

	</div><!-- container -->

		
	<div id="results"></div>   
		
		
	<!-- JS
	================================================== -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.js"></script>
	<script>window.jQuery || document.write("<script src='/js/jquery-1.5.1.min.js'>\x3C/script>")</script>
	<script src="/js/app.js"></script>  
	<script src="/js/twiiter.js"></script>
	
	
<!-- End Document
================================================== -->
</body>
</html>