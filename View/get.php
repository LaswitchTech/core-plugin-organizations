<?php
header('Content-Type: ' . $this->call('type'));
header('Content-Disposition: inline; filename="' . $this->call('name') . '"');
echo $this->call('content');
