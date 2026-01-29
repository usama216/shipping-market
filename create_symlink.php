<?php
$target = __DIR__ . '/../storage/app/public';
$link = __DIR__ . '/storage';
if (!file_exists($link)) {
    symlink($target, $link);
    echo 'Symlink created successfully!';
} else {
    echo 'Symlink already exists.';
}