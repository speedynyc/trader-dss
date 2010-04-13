ALTER TABLE ONLY cart ADD CONSTRAINT constraint_pfid FOREIGN KEY (pfid) REFERENCES portfolios(pfid) MATCH FULL;
