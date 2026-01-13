<?php
file_put_contents('/tmp/test-php-access.log', date('c') . " - test-php accessed\n", FILE_APPEND);
phpinfo();
?>
