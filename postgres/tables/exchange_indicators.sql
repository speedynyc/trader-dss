--
-- Name: exchange_indicators; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE exchange_indicators (
 exch character varying(6) NOT NULL, -- exchange symbol
 date date NOT NULL, -- The date of the data
 advance integer, -- Number of advancing
 decline integer, -- Number of declining
 adv_dec_spread integer, -- (Number of advancing - Number of declining)
 adv_dec_line integer, -- (Number of advancing - Number of declining) + adv_dec_line from yesterday
 adv_dec_ratio numeric(9,4), -- (Number of advancing / Number of decling)
 CONSTRAINT constraint_exchange_indicators FOREIGN KEY (date, exch)
 REFERENCES trade_dates (date, exch) MATCH FULL
 ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITHOUT OIDS;

ALTER TABLE exchange_indicators OWNER TO postgres;
ALTER TABLE ONLY exchange_indicators ADD CONSTRAINT exchange_indicators_pkey PRIMARY KEY (date, exch);
COMMENT ON COLUMN exchange_indicators.exch IS 'exchange symbol';
COMMENT ON COLUMN exchange_indicators."date" IS 'The date of the data';
COMMENT ON COLUMN exchange_indicators.adv_dec_spread IS '(Number of advancing - Number of declining)';
COMMENT ON COLUMN exchange_indicators.adv_dec_line IS '(Number of advancing - Number of declining) + adv_dec_line from yesterday';
COMMENT ON COLUMN exchange_indicators.adv_dec_ratio IS '(Number of advancing / Number of decling)';

