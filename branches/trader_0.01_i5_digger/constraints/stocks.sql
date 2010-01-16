ALTER TABLE ONLY stocks ADD CONSTRAINT stocks_pkey PRIMARY KEY (symb, exch);
ALTER TABLE ONLY stocks ADD CONSTRAINT constraint_exchange FOREIGN KEY (exch) REFERENCES exchange(exch) MATCH FULL;
