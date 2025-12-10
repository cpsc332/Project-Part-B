USE theatre_booking;

-- 1. Top-N movies by tickets sold in the last 30 days
CREATE OR REPLACE VIEW vw_top_movies_last_30_days AS
SELECT
  m.movieid,
  m.name AS movie_name,
  COUNT(CASE WHEN t.status IN ('PURCHASED','USED') THEN 1 END) AS tickets_sold
FROM movie m
JOIN showtime s   ON s.movieid = m.movieid
LEFT JOIN ticket t ON t.showtimeid = s.showtimeid
WHERE s.starttime >= NOW() - INTERVAL 30 DAY
GROUP BY m.movieid, m.name;

-- 2. Upcoming sold-out showtimes per theatre
CREATE OR REPLACE VIEW vw_upcoming_sold_out_showtimes AS
SELECT
  th.theatreid,
  th.name AS theatrename,
  s.showtimeid,
  m.name AS movie_name,
  s.starttime
FROM showtime s
JOIN movie m       ON m.movieid = s.movieid
JOIN auditorium a  ON a.auditoriumid = s.auditoriumid
JOIN theatre th    ON th.theatreid = a.theatreid
JOIN (
  SELECT showtimeid, COUNT(*) AS seats_sold
  FROM ticket
  WHERE status IN ('RESERVED','PURCHASED','USED')
  GROUP BY showtimeid
) t ON t.showtimeid = s.showtimeid
JOIN (
  SELECT auditoriumid, COUNT(*) AS total_seats
  FROM seat
  GROUP BY auditoriumid
) seat_counts ON seat_counts.auditoriumid = a.auditoriumid
WHERE s.starttime >= NOW()
  AND t.seats_sold >= seat_counts.total_seats;

-- 3. Theatre utilization report: % seats sold per showtime next 7 days
CREATE OR REPLACE VIEW vw_theatre_utilization_next_7_days AS
SELECT
  th.theatreid,
  th.name AS theatre_name,
  s.showtimeid,
  m.name AS movie_name,
  s.starttime,
  seat_counts.total_seats,
  COALESCE(sales.seats_sold, 0) AS seats_sold,
  ROUND(COALESCE(sales.seats_sold,0) / seat_counts.total_seats * 100, 1) AS pct_sold
FROM showtime s
JOIN movie m       ON m.movieid = s.movieid
JOIN auditorium a  ON a.auditoriumid = s.auditoriumid
JOIN theatre th    ON th.theatreid = a.theatreid
JOIN (
  SELECT auditoriumid, COUNT(*) AS total_seats
  FROM seat
  GROUP BY auditoriumid
) seat_counts ON seat_counts.auditoriumid = a.auditoriumid
LEFT JOIN (
  SELECT showtimeid, COUNT(*) AS seats_sold
  FROM ticket
  WHERE status IN ('RESERVED','PURCHASED','USED')
  GROUP BY showtimeid
) sales ON sales.showtimeid = s.showtimeid
WHERE s.starttime >= NOW()
  AND s.starttime < NOW() + INTERVAL 7 DAY;

DELIMITER $$

CREATE TRIGGER trg_ticket_before_insert
BEFORE INSERT ON ticket
FOR EACH ROW
BEGIN
  DECLARE seat_taken INT;

  SELECT COUNT(*)
  INTO seat_taken
  FROM ticket
  WHERE showtimeid = NEW.showtimeid
    AND seatid     = NEW.seatid
    AND status IN ('RESERVED','PURCHASED','USED');

  IF seat_taken > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Seat already sold or reserved for this showtime';
  END IF;
END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sell_ticket(
  IN  p_showtime_id  INT,
  IN  p_seat_id      INT,
  IN  p_customer_id  INT,
  IN  p_discount_code VARCHAR(64),
  OUT p_ticket_id    INT
)
BEGIN
  DECLARE v_exists INT;
  DECLARE v_base_price DECIMAL(6,2);
  DECLARE v_seat_type VARCHAR(10);
  DECLARE v_price DECIMAL(6,2);

  -- Seat belongs to auditorium?
  SELECT COUNT(*)
  INTO v_exists
  FROM showtime st
  JOIN seat se ON se.auditoriumid = st.auditoriumid
  WHERE st.showtimeid = p_showtime_id
    AND se.seatid = p_seat_id;

  IF v_exists = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Seat does not belong to this showtime auditorium';
  END IF;

  -- Seat already taken?
  SELECT COUNT(*)
  INTO v_exists
  FROM ticket
  WHERE showtimeid = p_showtime_id
    AND seatid     = p_seat_id
    AND status IN ('RESERVED','PURCHASED','USED');

  IF v_exists > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Seat already sold or reserved';
  END IF;

  -- Fetch seat price + type
  SELECT st.baseprice, se.seattype
  INTO v_base_price, v_seat_type
  FROM showtime st
  JOIN seat se ON se.auditoriumid = st.auditoriumid
  WHERE st.showtimeid = p_showtime_id
    AND se.seatid = p_seat_id;

  SET v_price = v_base_price;

  -- Seat type adjustment
  IF v_seat_type = 'PREMIUM' THEN
    SET v_price = v_price * 1.20;
  ELSEIF v_seat_type = 'ADA' THEN
    SET v_price = v_price * 0.90;
  END IF;

  -- Discount adjustment
  IF p_discount_code IS NOT NULL THEN
    CASE UPPER(p_discount_code)
      WHEN 'STUDENT' THEN SET v_price = v_price * 0.80;
      WHEN 'SENIOR'  THEN SET v_price = v_price * 0.85;
      WHEN 'CHILD'   THEN SET v_price = v_price * 0.75;
    END CASE;
  END IF;

  -- Insert ticket
  INSERT INTO ticket (showtimeid, seatid, customerid, price, discounttype, status)
  VALUES (p_showtime_id, p_seat_id, p_customer_id, v_price, p_discount_code, 'PURCHASED');

  SET p_ticket_id = LAST_INSERT_ID();

  SELECT p_ticket_id AS ticket_id;
END$$

DELIMITER ;
