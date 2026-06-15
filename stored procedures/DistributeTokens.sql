DELIMITER $$
CREATE OR REPLACE PROCEDURE DistributeTokens()
BEGIN
        DECLARE finished INTEGER DEFAULT 0;
        DECLARE total_score INTEGER DEFAULT 0;
        DECLARE factor DECIMAL DEFAULT 0;
        DECLARE availableTokens DECIMAL DEFAULT 0;
        DECLARE totalScore INTEGER DEFAULT 0;

        DECLARE thisMonth INTEGER DEFAULT 0;
		DECLARE thisYear INTEGER DEFAULT 0;
		DECLARE curUser INTEGER DEFAULT 0;
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
		
		-- Πρώτα βρίσκουμε το πλήθος των tokens που θα μοιραστούν
		SELECT monthly_tokens.m_tokens * 0.8
		INTO availableTokens
		FROM monthly_tokens
		WHERE monthly_tokens.m_id
		ORDER BY monthly_tokens.m_id DESC
		LIMIT 1;

		-- Υπολογίζουμε το σύνολο πόντων που έχουν συγκεντρώσει
		-- όλοι οι χρήστες μέσα στο μήνα
		SELECT SUM(sc_monthly_score)
		INTO totalScore
		FROM	score INNER JOIN
		users ON users.usr_id=score.sc_user_id
		WHERE users.usr_role<>1;

		SET factor=availableTokens/totalScore;
		
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

END$$
DELIMITER ;