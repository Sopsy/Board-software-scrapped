<?php /*>
<h1>Oh noes! What happened!?</h1>

<p>
    You see this message because something has really gone wrong.<br />
    Please be patient and try again soon, we'll try to figure out what happened.
</p>
<!-- PHP is not executing scripts! -->

<!-- */

define('PUBLIC_PATH', __DIR__);
require('../YFW/Bootstrap.php');

$bootstrap = new \YFW\Bootstrap();
$bootstrap->setErrorPage('YBoard/View/BasicError');
$bootstrap->run('YBoard');

// -->
