--
-- Name: stock_sector; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE stock_sector (
    symb character varying(12) NOT NULL,
    exch character varying(6) NOT NULL,
    sector_id numeric NOT NULL
);
ALTER TABLE public.stock_sector OWNER TO postgres;
ALTER TABLE ONLY stock_sector ADD CONSTRAINT symb_exch_sect PRIMARY KEY (symb, exch, sector_id);
