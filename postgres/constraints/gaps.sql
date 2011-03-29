ALTER TABLE ONLY gaps ADD CONSTRAINT constraint_gaps_quotes FOREIGN KEY (symb, exch, date) REFERENCES quotes(symb, exch, date) MATCH FULL;
