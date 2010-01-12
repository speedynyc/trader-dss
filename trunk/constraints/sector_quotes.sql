ALTER TABLE ONLY sector_quotes ADD CONSTRAINT sector_quotes_pkey PRIMARY KEY (sector_id, exch, date);
ALTER TABLE ONLY sector_quotes ADD CONSTRAINT sector_quotes_sector_id_fkey FOREIGN KEY (sector_id, exch) REFERENCES sectors(sector_id, exch);
