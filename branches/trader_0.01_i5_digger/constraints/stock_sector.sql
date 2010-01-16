ALTER TABLE ONLY stock_sector ADD CONSTRAINT symb_exch_sect PRIMARY KEY (symb, exch, sector_id);
ALTER TABLE ONLY stock_sector ADD CONSTRAINT constraint_stocks FOREIGN KEY (symb, exch) REFERENCES stocks(symb, exch) MATCH FULL;
