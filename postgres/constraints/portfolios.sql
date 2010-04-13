ALTER TABLE ONLY portfolios ADD CONSTRAINT portfolios_uid FOREIGN KEY (uid) REFERENCES users(uid) MATCH FULL;
ALTER TABLE ONLY portfolios ADD CONSTRAINT portfolios_exch_fkey FOREIGN KEY (exch) REFERENCES exchange(exch);
