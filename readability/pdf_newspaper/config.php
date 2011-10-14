<?php
// PDF Newspaper options
$options->allow_full_text_option = true;
$options->full_text_service_url = 'http://localhost/full-text/makefulltextfeed.php?url='; // e.g. http://fivefilters.org/content-only/makefulltextfeed.php?url=
$options->full_text_service_url_with_key = 'http://localhost/full-text/makefulltextfeed.php?url=';

// Specify API keys
// ----------------
// The service can be accessed in two ways: free and key holder access.
// Users can be given API keys which they can use to identify themselves
// to the service to bypass some of the restrictions imposed in free mode.
// If you want everyone to access the service in the same way, you can
// leave this array empty.
$options->api_keys = array('');
?>