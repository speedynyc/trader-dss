ALTER TABLE ONLY watch ADD CONSTRAINT constraint_pfid FOREIGN KEY (pfid) REFERENCES portfolios(pfid) MATCH FULL;
