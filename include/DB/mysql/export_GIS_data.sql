DROP FUNCTION IF EXISTS `exportGIS`;

SET SESSION group_concat_max_len = 100000000000;# MySQL returned an empty result set (i.e. zero rows).

DELIMITER $$
CREATE FUNCTION exportGIS()
RETURNS BLOB
NOT DETERMINISTIC
BEGIN

  DECLARE export LONGBLOB;

  SET @export := (SELECT GROUP_CONCAT(CONCAT("(",`id`, ", GEOMFROMTEXT('", ASTEXT(`point`),  "',4326),", `elevation`,  ",", `is_deleted` ,")")) AS inserts 
FROM `vertices`);

  RETURN @export;

END$$

DELIMITER ;
SELECT exportGIS();

-- OU 

SELECT CONCAT("(",`id`, ", GEOMFROMTEXT('", ASTEXT(`point`),  "',4326),", `elevation`,  ",", `is_deleted` ,"),") AS inserts 
FROM `vertices` LIMIT 100000000000;