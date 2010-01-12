ALTER TABLE ONLY indicators ADD CONSTRAINT indicators_pkey PRIMARY KEY (date, symb, exch);
ALTER TABLE ONLY indicators ADD CONSTRAINT constraint_indicators FOREIGN KEY (symb, exch) REFERENCES stocks(symb, exch) MATCH FULL;
