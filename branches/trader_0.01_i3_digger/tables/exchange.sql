--
-- Name: exchange; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE exchange (
    exch character varying(6) NOT NULL,
    name character varying(100) NOT NULL,
    curr_desc character varying(4),
    curr_char character varying(1)
);
ALTER TABLE public.exchange OWNER TO postgres;
COMMENT ON COLUMN exchange.exch IS 'Yahoo Exchange symbol';
COMMENT ON COLUMN exchange.name IS 'Name of the Exchange';
COMMENT ON COLUMN exchange.curr_desc IS 'Standard acronym for the currency the exchange trades in';
COMMENT ON COLUMN exchange.curr_char IS 'The symbol used to represent the currency the exchange trades in';
ALTER TABLE ONLY exchange ADD CONSTRAINT exchange_pkey PRIMARY KEY (exch);
