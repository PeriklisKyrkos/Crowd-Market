SET GLOBAL event_scheduler = ON;

-- Προγραμματίζουμε τη βάση να εκτελεί κάθε μέρα
-- τη διαδικασία απενεργοποίησης προσφορών
-- στις 23:59:59
CREATE EVENT DisableBids
    ON SCHEDULE EVERY 24 HOUR
    STARTS '2023-09-12 23:59:59'
    ON COMPLETION NOT PRESERVE ENABLE
DO 
  CALL RemoveBids();
