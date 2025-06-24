<?php

// Set Content Type
$types = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/bmp'
];
$type = $this->call('type');
if(!in_array($type, $types)){
    $type = 'application/octet-stream';
}
header('Content-Type: ' . $type);

// Set Content Disposition
header('Content-Disposition: inline; filename="' . $this->call('name') . '"');

// Output the file content
echo $this->call('content');
