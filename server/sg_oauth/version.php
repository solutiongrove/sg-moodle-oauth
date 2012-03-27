<?php

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of sg_oauth
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$plugin->version  = 2012032700;
$plugin->requires = 2010080300;  // Requires this Moodle version
$plugin->cron     = 0;           // Period for cron to check this module (secs)
