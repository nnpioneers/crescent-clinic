<?php
$c = new PDO('mysql:host=localhost;dbname=crescent_clinic', 'root', '');
$s = $c->query('SHOW COLUMNS FROM direct_sales');
print_r($s->fetchAll(PDO::FETCH_ASSOC));
