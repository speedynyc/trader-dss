ALTER TABLE ONLY gaps ADD CONSTRAINT constraint_gaps_stocks FOREIGN KEY (symb, exch) REFERENCES stocks(symb, exch) MATCH FULL;
ALTER TABLE ONLY gaps ADD CONSTRAINT constraint_gaps_quotes FOREIGN KEY (symb, exch, date) REFERENCES quotes(symb, exch, date) MATCH FULL;
