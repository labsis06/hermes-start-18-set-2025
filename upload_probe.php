<?php
// upload_probe.php
header('Content-Type: text/plain; charset=utf-8');

echo "file_uploads=" . ini_get('file_uploads') . PHP_EOL;
echo "upload_max_filesize=" . ini_get('upload_max_filesize') . PHP_EOL;
echo "post_max_size=" . ini_get('post_max_size') . PHP_EOL;
echo "sys_get_temp_dir()=" . sys_get_temp_dir() . PHP_EOL;
echo "is_writable(tmp)=" . (is_writable(sys_get_temp_dir()) ? 'yes':'no') . PHP_EOL;
echo "time=" . date('c') . PHP_EOL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "---- POST RECEIVED ----" . PHP_EOL;
    echo "FILES keys: " . implode(',', array_keys($_FILES)) . PHP_EOL;
    foreach ($_FILES as $key => $info) {
        echo "[$key] ";
        if (is_array($info['name'])) {
            echo "multiple=" . count($info['name']) . PHP_EOL;
            foreach ($info['name'] as $i => $n) {
                echo "  #$i name={$n} err={$info['error'][$i]} size={$info['size'][$i]}" . PHP_EOL;
            }
        } else {
            echo "single name={$info['name']} err={$info['error']} size={$info['size']}" . PHP_EOL;
        }
    }
    exit;
}
?>
<!doctype html>
<meta charset="utf-8">
<h1>Upload Probe</h1>
<form method="post" enctype="multipart/form-data">
  <p><input type="file" name="probe_files[]" multiple></p>
  <p><button>Invia</button></p>
</form>
