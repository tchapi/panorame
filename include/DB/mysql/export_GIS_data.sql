SELECT CONCAT("(",`id`, ", GEOMFROMTEXT('", ASTEXT(`point`),  "',4326),", `elevation`,  ",", `is_deleted` ,"),") AS inserts 
FROM `vertices` LIMIT 100000000000;