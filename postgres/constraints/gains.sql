ALTER TABLE ONLY gains ADD CONSTRAINT constraint_gains_quotes FOREIGN KEY (symb, exch, date) REFERENCES quotes(symb, exch, date) MATCH FULL;
