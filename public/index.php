<?php

//die('<style>body,html{margin:0;padding:0;width:100%;height:100%;overflow:hidden}</style><iframe style="width:100%;height:100%" src="https://www.youtube.com/embed/kL5DDSglM_s?autoplay=1" frameborder="0" allowfullscreen></iframe>');

/*

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
