<?php $conn = new PDO('sqlite::memory:'); try { $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); echo 'Success'; } catch(Exception $e) { echo 'Error: ' . $e->getMessage(); }
