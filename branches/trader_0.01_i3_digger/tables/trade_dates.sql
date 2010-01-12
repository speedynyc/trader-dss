--
-- Name: trade_dates; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE trade_dates (
    date date,
    exch character varying(6)
);
ALTER TABLE public.trade_dates OWNER TO postgres;
CREATE INDEX idx_date_exch ON trade_dates USING btree (date, exch);
