--
-- Name: trade_dates; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE trade_dates (
    date date,
    exch character varying(6),
    up_to_date boolean,
    volume numeric(12,0)
);
ALTER TABLE public.trade_dates OWNER TO postgres;
ALTER TABLE ONLY trade_dates ADD CONSTRAINT trade_dates_pkey PRIMARY KEY (date, exch);
