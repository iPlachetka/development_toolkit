<html>
<head>
<title>OPML Parser Example</title>
</head>
<body>
<form action="index.php" method="post" name="form1" target="_self" id="form1">
  <label>URL of OPML file
  <input name="url" type="text" id="url" value='http://www.opmlmanager.com/opml/phpmanual.opml' size="60" maxlength="255"/>
  </label>
  <p>
    <label>
    <input type="submit" name="Submit" value="Submit" />
    </label>
  </p>
</form>
<?php
require_once('iam_opml_parser.php');

if($_POST['url']!='')
{
	$parser = new IAM_OPML_Parser();
	echo $parser->displayOPMLContents($_POST['url']);
}
?>
</body>
</html>