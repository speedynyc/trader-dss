ALTER TABLE ONLY standard_deviations_from_mean ADD CONSTRAINT constraint_standard_deviations_from_mean_quotes FOREIGN KEY (symb, exch, date) REFERENCES quotes(symb, exch, date) MATCH FULL;
