ALTER TABLE ONLY quotes ADD CONSTRAINT constraint_stocks FOREIGN KEY (symb, exch) REFERENCES stocks(symb, exch) MATCH FULL;
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_constraint_low_gt_high check (low <= high);
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_volume_gt_zero check (volume > 0);
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_open_gt_zero check (open > 0);
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_close_gt_zero check (close > 0);
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_low_gt_zero check (low > 0);
ALTER TABLE ONLY quotes ADD CONSTRAINT stocks_high_gt_zero check (high > 0);
