--
-- Name: exchange; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE exchange (
 exch character varying(6) NOT NULL, -- Finance::Quote Exchange symbol
 "name" character varying(100) NOT NULL, -- Name of the Exchange
 curr_desc character varying(4), -- Standard acronym for the currency the exchange trades in
 curr_char character varying(1) -- The symbol used to represent the currency the exchange trades in
)
WITHOUT OIDS;

ALTER TABLE exchange OWNER TO postgres;
ALTER TABLE ONLY exchange ADD CONSTRAINT exchange_pkey PRIMARY KEY (exch);
COMMENT ON COLUMN exchange.exch IS 'Finance::Quote Exchange symbol';
COMMENT ON COLUMN exchange."name" IS 'Name of the Exchange';
COMMENT ON COLUMN exchange.curr_desc IS 'Standard acronym for the currency the exchange trades in';
COMMENT ON COLUMN exchange.curr_char IS 'The symbol used to represent the currency the exchange trades in';

