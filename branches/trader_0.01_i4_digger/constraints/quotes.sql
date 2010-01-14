ALTER TABLE ONLY quotes ADD CONSTRAINT quotes_pkey PRIMARY KEY (date, symb, exch);
ALTER TABLE ONLY quotes ADD CONSTRAINT constraint_stocks FOREIGN KEY (symb, exch) REFERENCES stocks(symb, exch) MATCH FULL;
