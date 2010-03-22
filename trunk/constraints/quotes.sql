ALTER TABLE ONLY quotes ADD CONSTRAINT constraint_stocks FOREIGN KEY (symb, exch) REFERENCES stocks(symb, exch) MATCH FULL;
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_constraint_low_gt_high check (low <= high);
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_volume_gt_zero check (volume > 0);
