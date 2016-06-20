<?php /*

<style>body{overflow:hidden;margin-top:-30px;}</style>
<h1>Oh noes! What happened!?</h1>

<p style="margin-bottom:100000px;">
    You see this message because something has really gone wrong.<br />
    Please be patient and try again soon, we'll try to figure out what happened.
</p>
<!-- PHP is not executing scripts! -->

*/
putenv('APPLICATION_ENVIRONMENT=development');
define('PUBLIC_PATH', __DIR__);
require('../YBoard/Bootstrap.php');
