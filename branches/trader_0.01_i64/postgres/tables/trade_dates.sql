--
-- Name: trade_dates; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE trade_dates (
    date date,
    exch character varying(6),
    up_to_date boolean
);
ALTER TABLE public.trade_dates OWNER TO postgres;
ALTER TABLE ONLY trade_dates ADD CONSTRAINT trade_dates_pkey PRIMARY KEY (date, exch);
COMMENT ON COLUMN trade_dates."date" IS 'The date of the data';
COMMENT ON COLUMN trade_dates.exch IS 'exchange symbol';
COMMENT ON COLUMN trade_dates.up_to_date IS 'Has a quote been added or changed since the exchange indicator for this date was calculated';
