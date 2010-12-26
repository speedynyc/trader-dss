--
-- Name: indicators; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE indicators
(
 date date NOT NULL, -- The date of the data
 symb character varying(12) NOT NULL, -- The symbol code used by Finance::Quote
 exch character varying(6) NOT NULL, -- The Exchange code
 wpr_10 numeric, -- Williams %R calculated over 10 days.
 wpr_20 numeric, -- Williams %R calculated over 20 days.
 wpr_30 numeric, -- Williams %R calculated over 30 days.
 wpr_50 numeric, -- Williams %R calculated over 50 days.
 wpr_100 numeric, -- Williams %R calculated over 100 days.
 wpr_200 numeric, -- Williams %R calculated over 200 days.
 mapr_10 numeric, -- moving_averages.ma_10_sum / moving_averages.ma_10_run. It provides a sortable measure of the stock against it's 10 day moving average.
 mapr_20 numeric, -- moving_averages.ma_20_sum / moving_averages.ma_20_run. It provides a sortable measure of the stock against it's 20 day moving average.
 mapr_30 numeric, -- moving_averages.ma_30_sum / moving_averages.ma_30_run. It provides a sortable measure of the stock against it's 30 day moving average.
 mapr_50 numeric, -- moving_averages.ma_50_sum / moving_averages.ma_50_run. It provides a sortable measure of the stock against it's 50 day moving average.
 mapr_100 numeric, -- moving_averages.ma_100_sum / moving_averages.ma_100_run. It provides a sortable measure of the stock against it's 100 day moving average.
 mapr_200 numeric, -- moving_averages.ma_200_sum / moving_averages.ma_200_run. It provides a sortable measure of the stock against it's 200 day moving average.
 mcad numeric, 
 mcad_signal numeric, 
 mcad_histogram numeric, 
 CONSTRAINT indicators_pkey PRIMARY KEY (date, symb, exch),
 CONSTRAINT constraint_indicators FOREIGN KEY (symb, exch)
 REFERENCES stocks (symb, exch) MATCH FULL
 ON UPDATE NO ACTION ON DELETE NO ACTION
 )
WITHOUT OIDS;
ALTER TABLE indicators OWNER TO postgres;
COMMENT ON COLUMN indicators.date IS 'The date of the data';
COMMENT ON COLUMN indicators.symb IS 'The symbol code used by Finance::Quote';
COMMENT ON COLUMN indicators.exch IS 'The Exchange code';
COMMENT ON COLUMN indicators.wpr_10 IS 'Williams %R calculated over 10 days.';
COMMENT ON COLUMN indicators.wpr_20 IS 'Williams %R calculated over 20 days.';
COMMENT ON COLUMN indicators.wpr_30 IS 'Williams %R calculated over 30 days.';
COMMENT ON COLUMN indicators.wpr_50 IS 'Williams %R calculated over 50 days.';
COMMENT ON COLUMN indicators.wpr_100 IS 'Williams %R calculated over 100 days.';
COMMENT ON COLUMN indicators.wpr_200 IS 'Williams %R calculated over 200 days.';
COMMENT ON COLUMN indicators.mapr_10 IS 'moving_averages.ma_10_sum / moving_averages.ma_10_run. It provides a sortable measure of the stock against it''s 10 day moving average.';
COMMENT ON COLUMN indicators.mapr_20 IS 'moving_averages.ma_20_sum / moving_averages.ma_20_run. It provides a sortable measure of the stock against it''s 20 day moving average.';
COMMENT ON COLUMN indicators.mapr_30 IS 'moving_averages.ma_30_sum / moving_averages.ma_30_run. It provides a sortable measure of the stock against it''s 30 day moving average.';
COMMENT ON COLUMN indicators.mapr_50 IS 'moving_averages.ma_50_sum / moving_averages.ma_50_run. It provides a sortable measure of the stock against it''s 50 day moving average.';
COMMENT ON COLUMN indicators.mapr_100 IS 'moving_averages.ma_100_sum / moving_averages.ma_100_run. It provides a sortable measure of the stock against it''s 100 day moving average.';
COMMENT ON COLUMN indicators.mapr_200 IS 'moving_averages.ma_200_sum / moving_averages.ma_200_run. It provides a sortable measure of the stock against it''s 200 day moving average.';
COMMENT ON COLUMN indicators.mcad IS 'MCAD = moving_averages.ema_12 - moving_averages.ema_26';
COMMENT ON COLUMN indicators.mcad_signal IS '9 day exponential moving average of MCAD';
COMMENT ON COLUMN indicators.mcad_histogram IS 'MCAD - mcad_signal';
