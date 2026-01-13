<?php
$log = '/tmp/upload-test.log';
file_put_contents($log, date('c') . " - hit\n", FILE_APPEND);
header('Content-Type: application/json');
file_put_contents($log, date('c') . " - POST keys: " . json_encode(array_keys($_POST)) . " FILES: " . json_encode(array_keys($_FILES)) . "\n", FILE_APPEND);
if (!isset($_FILES['file-input'])) {
    echo json_encode(['status'=>'error','message'=>"No file in \$_FILES (expected 'file-input')"]);
    exit;
}
$f = $_FILES['file-input'];
file_put_contents($log, date('c') . " - FILE meta: " . json_encode($f) . "\n", FILE_APPEND);
echo json_encode(['status'=>'ok','file'=>$f]);
?>
