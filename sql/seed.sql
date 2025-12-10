-- DML Seed Scripts:

USE theatre_booking;

DELIMITER $$

DROP PROCEDURE IF EXISTS seed_demo_data;

CREATE PROCEDURE seed_demo_data()
BEGIN
  DECLARE i INT;
  DECLARE j INT;
  DECLARE k INT;
  DECLARE row_cnt INT;
  DECLARE col_cnt INT;
  DECLARE aud_id INT;
  DECLARE st_id INT;
  DECLARE cust_id INT;
  DECLARE thNum INT;
  DECLARE audNum INT;
  DECLARE movNum INT;
  DECLARE stNum INT;
  DECLARE seatNum INT;
  DECLARE cusNum INT;

  DELETE FROM ticket;
  DELETE FROM customer;
  DELETE FROM seat;
  DELETE FROM showtime;
  DELETE FROM movie;
  DELETE FROM auditorium;
  DELETE FROM theatre;

  -- 3 theatres
  INSERT INTO theatre (name, street, city, state, zipcode) VALUES
    ('CineMax Downtown', '123 Main St', 'Los Angeles', 'CA', '90015'),
    ('Sunset Plaza 16',  '500 Sunset Blvd', 'Los Angeles', 'CA', '90028'),
    ('Irvine Spectrum',  '31 Spectrum Center Dr', 'Irvine', 'CA', '92618');

  -- 10 auditoriums with different sizes
  
  SET thNum = (SELECT MIN(theatreid) FROM theatre);
  SELECT thNum;
  INSERT INTO auditorium (theatreid, name, rowcount, seatcount) VALUES
    (thNum, 'Auditorium 1',  8,  96),   -- 8 x 12
    (thNum, 'Auditorium 2', 10, 160),   -- 10 x 16
    (thNum, 'Auditorium 3', 12, 240),   -- 12 x 20
    (thNum + 1, 'Auditorium 4',  8,  96),
    (thNum + 1, 'Auditorium 5', 10, 160),
    (thNum + 1, 'Auditorium 6', 12, 240),
    (thNum + 2, 'Auditorium 7',  8,  96),
    (thNum + 2, 'Auditorium 8', 10, 160),
    (thNum + 2, 'Auditorium 9', 12, 240),
    (thNum + 2, 'Auditorium 10', 8, 96);

  SET audNum = (SELECT MIN(auditoriumid) FROM auditorium);
  SELECT audNum;
  SET i = 1;
  WHILE i <= 10 DO
    IF i = 1 THEN
      SET aud_id  = audNum; 
      SET row_cnt = 8;
      SET col_cnt = 12;
    ELSEIF i = 2 THEN
      SET aud_id  = 1 + audNum;
      SET row_cnt = 10;
      SET col_cnt = 16;
    ELSE
      SET aud_id  = 2 + audNum;
      SET row_cnt = 12;
      SET col_cnt = 20;
    END IF;

    SET j = 1;
    WHILE j <= row_cnt DO
      SET k = 1;
      WHILE k <= col_cnt DO
      SELECT i, j, k, row_cnt;
        INSERT INTO seat (auditoriumid, rownumber, seatnumber, seattype)
        VALUES (
          aud_id,
          j,
          k,
          CASE
            WHEN k <= 2 THEN 'ada'          -- first two seats per row
            WHEN k >= col_cnt - 1 THEN 'premium' -- last two as premium
            ELSE 'STANDARD'
          END
        );
        SET k = k + 1;
      END WHILE;
      SET j = j + 1;
    END WHILE;

    SET i = i + 1;
  END WHILE;

  -- 12 movies
  INSERT INTO movie (name, runtime, mpaa, releasedate) VALUES
    ('Spirited Away',          125, 'pg',   '2001-07-20'),
    ('Inception',              148, 'pg13', '2010-07-16'),
    ('The Dark Knight',        152, 'pg13', '2008-07-18'),
    ('Toy Story',               81, 'g',    '1995-11-22'),
    ('Finding Nemo',           100, 'g',    '2003-05-30'),
    ('The Matrix',             136, 'r',    '1999-03-31'),
    ('Interstellar',           169, 'pg13', '2014-11-07'),
    ('Avengers: Endgame',      181, 'pg13', '2019-04-26'),
    ('Parasite',               132, 'r',    '2019-05-30'),
    ('Coco',                   105, 'pg',   '2017-11-22'),
    ('Inside Out',              95, 'pg',   '2015-06-19'),
    ('Oppenheimer',            180, 'r',    '2023-07-21');

  -- 80 showtimes over a range of days, rotating movies & auditoriums
  SET i = 1;
  SET movNum = (SELECT MIN(movieid) FROM movie);
  WHILE i <= 80 DO
    SELECT (movNum + MOD(i-1, 12)), (audNum + MOD(i-1, 10));
    INSERT INTO showtime (movieid, auditoriumid, starttime, format, language, baseprice)
    VALUES (
      movNum + MOD(i-1, 12),                 -- movie 1..12
      audNum + MOD(i-1, 10),                 -- auditorium 1..10
      DATE_ADD(
        DATE_ADD('2025-10-20 12:00:00',
               INTERVAL FLOOR((i-1)/10) DAY),
               INTERVAL (MOD(i-1,10) * 2) HOUR),
      CASE MOD(i,3)
        WHEN 1 THEN '2d'
        WHEN 2 THEN '3d'
        ELSE 'imax'
      END,
      CASE MOD(i,2)
        WHEN 0 THEN 'sub'
        ELSE 'dub'
      END,
      15.00 + MOD(i,5)  -- between 15 and 19
    );
    SET i = i + 1;
  END WHILE;

  -- 60 customers
  SET i = 1;
  WHILE i <= 60 DO
    INSERT INTO customer (name, email, guestflag)
    VALUES (
      CONCAT('Customer ', i),
      CONCAT('customer', i, '@example.com'),
      0
    );
    SET i = i + 1;
  END WHILE;

  -- 400 tickets: 5 tickets per showtime for first 80 showtimes
  SET cusNum = (SELECT MIN(customerid) FROM customer);
  SET stNum = (SELECT MIN(showtimeid) FROM showtime);
  SET seatNum = (SELECT MIN(seatid) FROM seat);
  SET i = 1;                -- showtime counter
  WHILE i <= 80 DO
    SET j = 1;
    WHILE j <= 5 DO         -- 5 * 80 = 400 tickets
      SET cust_id = MOD((i-1)*5 + j - 1, 60)+cusNum;

      INSERT INTO ticket (showtimeid, seatid, customerid, price, discounttype, status)
      VALUES (
        i+stNum-1,
        MOD((i-1)*5 + j - 1, 500)+seatNum,
        cust_id,
        15.00,
        CASE MOD(j,3)
          WHEN 1 THEN 'NONE'
          WHEN 2 THEN 'STUDENT'
          ELSE 'SENIOR'
        END,
        'PURCHASED'
      );

      SET j = j + 1;
    END WHILE;
    SET i = i + 1;
  END WHILE;

END$$
DELIMITER ;
CALL seed_demo_data();
DROP PROCEDURE seed_demo_data;

