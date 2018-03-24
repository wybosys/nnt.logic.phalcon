<?php

namespace App\Model;

define('IS_LOCAL', file_exists(APP_DIR . '~local'));
define('IS_DEVELOP', file_exists(APP_DIR . '~develop'));
define('IS_RELEASE', file_exists(APP_DIR . '~release'));
define('IS_DISTRIBUTION', file_exists(APP_DIR . '~distribution'));
define('IS_DEBUG', IS_RELEASE && !IS_DISTRIBUTION);
