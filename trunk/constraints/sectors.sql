ALTER TABLE ONLY sectors ADD CONSTRAINT sector_exch_fkey FOREIGN KEY (exch) REFERENCES exchange(exch);
