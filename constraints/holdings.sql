ALTER TABLE ONLY holdings ADD CONSTRAINT constraint_pfid FOREIGN KEY (pfid) REFERENCES portfolios(pfid) MATCH FULL;
