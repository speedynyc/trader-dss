ALTER TABLE ONLY indicators ADD CONSTRAINT constraint_indicators_quotes FOREIGN KEY (symb, exch, date) REFERENCES quotes(symb, exch, date) MATCH FULL;
