ALTER TABLE ONLY trades ADD CONSTRAINT constraint_pfid FOREIGN KEY (pfid) REFERENCES portfolios(pfid) MATCH FULL;
