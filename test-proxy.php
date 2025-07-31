<?php
  // Log to a specific file we know exists
  file_put_contents("/home/doakst7/public_html/wp-content/uploads/proxy-test.log", date("Y-m-d H:i:s") . " - Proxy test script accessed\n", FILE_APPEND);
  echo "Proxy test successful";
?>
