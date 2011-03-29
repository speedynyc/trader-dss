ALTER TABLE ONLY moving_averages ADD CONSTRAINT constraint_moving_averages_quotes FOREIGN KEY (symb, exch, date) REFERENCES quotes(symb, exch, date) MATCH FULL;
