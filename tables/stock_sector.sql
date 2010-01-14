--
-- Name: stock_sector; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE stock_sector (
    symb character varying(10) NOT NULL,
    exch character varying(6) NOT NULL,
    sector_id numeric(7,0) NOT NULL
);
ALTER TABLE public.stock_sector OWNER TO postgres;
