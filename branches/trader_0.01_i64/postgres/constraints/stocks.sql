ALTER TABLE ONLY stocks ADD CONSTRAINT constraint_exchange FOREIGN KEY (exch) REFERENCES exchange(exch) MATCH FULL;
