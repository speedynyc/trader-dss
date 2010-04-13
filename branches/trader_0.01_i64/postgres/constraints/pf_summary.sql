ALTER TABLE ONLY pf_summary ADD CONSTRAINT constraint_pfid FOREIGN KEY (pfid) REFERENCES portfolios(pfid) MATCH FULL;
