<?php $c = new PDO("sqlite:hospital.db"); print_r($c->query("SELECT * FROM generic_mappings WHERE generic_name LIKE '%AB - NEXT%'")->fetchAll(PDO::FETCH_ASSOC)); ?>
