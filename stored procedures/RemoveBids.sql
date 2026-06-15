DELIMITER $$

CREATE OR REPLACE PROCEDURE RemoveBids()

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