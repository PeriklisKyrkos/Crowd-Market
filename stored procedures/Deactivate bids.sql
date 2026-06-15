DELIMITER $$

-- Η διαδικασία αυτή βρίσκει τις ενεργές προσφορές οι οποίες έχουν
-- ξεπεράσει τις 7 ημέρες. Για καθεμία από αυτές τις προσφορές,
-- ελέγχεται εάν για το αντίστοιχο προϊόν πληρούνται οι συνθήκες
-- 5αi και 5αii. Εάν και οι δύο συνθήκες δεν πληρούνται,
-- η προσφορά απενεργοποιείται. Εάν πληρείται κάποια από τις δύο,
-- τότε η προσφροά παραμένει στο σύστημα.
-- Σε κάθε περίπτωση, στο τέλος της procedure, όλες οι ενεργές προσφορές
-- που έχουν συμπληρώσει 14 ημέρες, απενεργοποιούνται.

CREATE PROCEDURE RemoveBids()
    BEGIN
        DECLARE finished INTEGER DEFAULT 0;
        DECLARE Condition5ai INTEGER DEFAULT 0;
        DECLARE Condition5aii INTEGER DEFAULT 0;
        DECLARE curBid INTEGER;
        DECLARE curProduct INTEGER;
        DECLARE curPrice DECIMAL;

    	-- Ο cursor βρίσκει τα προϊόντα των προσφορών που 
        -- πρόκειται να απενεργοποιηθούν.
        -- Για καθένα από τα προϊόντα αυτά, θα υπολογίζονται
        -- τα κριτήρια 5.α.i και 5.α.ii
        
        DECLARE cur_bid_products CURSOR FOR 
            SELECT bids.bid_id, bids.bid_product, bids.bid_price
            FROM bids 
            WHERE DATEDIFF(CURRENT_DATE(), DATE(bids.bid_timestamp))>=7;
        
		DECLARE CONTINUE HANDLER 
        FOR NOT FOUND SET finished = 1;        
        
        OPEN cur_bid_products;
        
		getProduct: LOOP
			FETCH cur_bid_products INTO curBid, curProduct, curPrice;
            IF finished = 1 THEN 
                LEAVE getProduct;
			END IF;
		
            -- Εδώ ελέγχουμε εάν η τιμή της προσφοράς είναι 20% χαμηλότερη
            -- από το μέσο όρο της χτεσινής τιμής του προϊόντος 
            SELECT 	CASE WHEN pp_price-curPrice > pp_price*0.8 THEN 1
                        ELSE 0
                    END 
            INTO 	Condition5ai
            FROM 	product_price INNER JOIN
                    products ON products.prod_name=product_price.pp_product 
            WHERE 	products.prod_id=curProduct AND
                    DATE(product_price.pp_date) = CURDATE()-INTERVAL 1 DAY;

            -- Εδώ ελέγχουμε εάν η τιμή της προσφοράς είναι 20% χαμηλότερη
            -- από το μέσο όρο της τιμής του προϊόντος για την τελευταία εβδομάδα
			SELECT 	CASE WHEN AVG(pp_price)-curPrice > AVG(pp_price)*0.8 THEN 1
            			ELSE 0
                    END
            INTO 	Condition5aii
			FROM 	product_price INNER JOIN
					products ON products.prod_name=product_price.pp_product
			WHERE 	bid_product=curRoduct AND
					DATE(product_price.pp_date) BETWEEN CURDATE()-INTERVAL 7 DAY AND CURDATE()-INTERVAL 1 DAY;

			IF Condition5ai=0 AND Condition5aii=0 THEN
                UPDATE 	bids 
                SET 	bids.bid_active=FALSE
                WHERE 	bids.bid_id=curBid;
            END IF;
            
		END LOOP getProduct;
		CLOSE cur_bid_products;        
        
		-- Σε κάθε περίπτωση, εάν μία προσφορά ξεπεράσει τις δύο εβδομάδες
		-- και είναι ενεργή, τότε απενεργοποιείται
    	UPDATE 	bids 
    	SET 	bids.bid_active=FALSE
    	WHERE 	bids.bid_active=FALSE AND 
				DATEDIFF(CURRENT_DATE(), DATE(bids.bid_timestamp))>=14;
    END$$
	
DELIMITER ;


DELIMITER $$

-- Υπολογίζει το πλήθος των tokens που θα μοιραστούν τον τρέχοντα μήνα
-- και το καταχωρεί στον πίνακα monthly_tokens για τον τρέχοντα μήνα
-- και το τρέχον έτος.
-- Ο υπολογισμός αυτός γίνεται την 1η κάθε μήνα.

CREATE PROCEDURE SetMonthlyTokens()
    BEGIN
        DECLARE lastMonth INTEGER DEFAULT 0;
        DECLARE lastYear INTEGER DEFAULT 0;
		
		-- Βρίσκουμε το πλήθος των χρηστών (εκτός από τους διαχειριστές),
        -- υπολογίζουμε το πλήθος των tokens του μήνα και καταχωρούμε στον πίνακα
        -- monthly_tokens τον τρέχοντα μήνα, έτος και πλήθος tokens
        -- που πρόκειται να μοιραστούν στους χρήστες
		
		-- Πρώτα ελέγχουμε εάν έχει γίνει ξανά εισαγωγή tokens για τον 
		-- ίδιο μήνα του ίδιου έτους.
		SELECT	m_month, m_year
		INTO	lastMonth, lastYear
		FROM	monthly_tokens
		WHERE	m_month=MONTH(CURRENT_DATE) AND m_year=YEAR(CURRENT_DATE);
		
		IF (lastMonth=0 AND lastYear=0) THEN
			INSERT INTO monthly_tokens (m_month, m_year, m_tokens)
			VALUES	(	
				MONTH(CURRENT_DATE), 
				YEAR(CURRENT_DATE), 
				(
					SELECT 	COUNT(usr_id)*100
					FROM 	users
					WHERE	users.usr_role<>1
				)
			);
		END IF;
    END$$
	
DELIMITER ;

DELIMITER $$

-- Υπολογίζει το πλήθος των tokens που θα μοιραστούν τον τρέχοντα μήνα
-- και το καταχωρεί στον πίνακα tokens για τον τρέχοντα μήνα
-- και το τρέχον έτος.
-- Ο υπολογισμός αυτός γίνεται την τελευταία μέρα κάθε μήνα.

CREATE PROCEDURE DistributeTokens()
    BEGIN
        DECLARE finished INTEGER DEFAULT 0;
        DECLARE total_score INTEGER DEFAULT 0;
        DECLARE factor DECIMAL DEFAULT 0;
        DECLARE thisMonth INTEGER;
		DECLARE thisYear INTEGER;
		DECLARE curUser INTEGER;
		DECLARE user_monthly_score INTEGER DEFAULT 0;
		
		-- Δημιουργούμε έναν κέρσορα για τους χρήστες πλην διαχειριστών
		-- και για κάθε χρήστη πολλαπλασιάζουμε το μηνιαίο του σκορ
		-- με το συντελεστή factor που υπολογίστηκε προηγουμένως
		-- και το αποτέλεσμα αποθηκεύεται στον πίνακα tokens
		
		DECLARE cur_users CURSOR FOR
			SELECT 	usr_id
			FROM 	users
			WHERE	users.usr_role<>1;

		DECLARE CONTINUE HANDLER 
			FOR NOT FOUND SET finished = 1;        

		SELECT 	MONTH(CURRENT_DATE), YEAR(CURRENT_DATE)
		INTO	thisMonth, thisYear;
		
		-- Υπολογίζουμε σε πόσα tokens αντιστοιχεί κάθε βαθμός του
		-- μηνιαίου σκορ.
		SELECT 	t.m_tokens/s.total_score
		INTO	factor
		FROM 
		(
			SELECT monthly_tokens.m_tokens * 0.8
			FROM monthly_tokens
			WHERE monthly_tokens.m_id
			ORDER BY monthly_tokens.m_id DESC
			LIMIT 1
		) AS t,
		(
			 SELECT SUM(sc_monthly_score) AS total_score
			 FROM	score INNER JOIN
			 users ON users.usr_id=score.sc_user_id
			 WHERE users.usr_role<>1
		) AS s;

        OPEN cur_users;
        
		getUser: LOOP
			FETCH cur_users INTO curUser;
            IF finished = 1 THEN 
                LEAVE getUser;
			END IF;
			
			SELECT  sc_monthly_score
			INTO	user_monthly_score
			FROM	score
			WHERE	sc_user_id=curUser;
			
			INSERT 	INTO tokens (tok_month, tok_year, tok_tokens, tok_user_id)
			VALUES	(thisMonth, thisYear, CAST(user_monthly_score*factor AS INT), curUser);
			
		END LOOP getUser;
		CLOSE cur_users; 
	END;
	
DELIMITER ;
