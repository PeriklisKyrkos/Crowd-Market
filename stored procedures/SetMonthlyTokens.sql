DELIMITER $$

CREATE OR REPLACE PROCEDURE SetMonthlyTokens()

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