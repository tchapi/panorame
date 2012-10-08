DELIMITER $$
CREATE FUNCTION consolidate()
RETURNS INTEGER
NOT DETERMINISTIC
BEGIN

  DECLARE start_lat, start_lng, dest_lat, dest_lng DOUBLE;
  DECLARE start_alt, dest_alt INTEGER;
  DECLARE distance, new_distance DOUBLE;
  DECLARE grade, new_grade INTEGER;

  DECLARE count INTEGER;

  SET @count := 0;

  WHILE EXISTS(SELECT e.`id` FROM `edges` e WHERE e.`is_dirty` = 1 AND e.`is_deleted` = 0) DO

    SELECT e.`id`, Y(vf.`point`), X(vf.`point`), vf.`elevation`, 
           Y(vt.`point`), X(vt.`point`), vt.`elevation`, 
           e.`distance`, e.`grade`
    INTO @id, @start_lat, @start_lng, @start_alt, 
           @dest_lat, @dest_lng, @dest_alt, 
           @distance, @grade
    FROM `edges` e
      JOIN `vertices` vf ON (e.`from_id` = vf.`id`)
      JOIN `vertices` vt ON (e.`to_id` = vt.`id`)
      WHERE e.`is_dirty` = 1 AND e.`is_deleted` = 0 LIMIT 1;

    SET @new_distance := 6371030 * acos( 
        cos(radians( @start_lat ))
      * cos(radians( @dest_lat ))
      * cos(radians( @start_lng ) - radians( @dest_lng ))
      + sin(radians( @start_lat )) 
      * sin(radians( @dest_lat))
    );

    SET @new_grade := CAST(@dest_alt AS SIGNED) - CAST(@start_alt AS SIGNED);

    UPDATE `edges` SET `distance` = @new_distance, `grade` = @new_grade, `is_dirty` = 0 WHERE `id` = @id;

    SET @count := @count + 1;

  END WHILE;

  RETURN @count;

END$$

DELIMITER ;