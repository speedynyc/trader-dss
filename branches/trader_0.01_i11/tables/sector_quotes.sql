--
-- Name: sector_quotes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE sector_quotes (
    count integer,
    date date NOT NULL,
    exch character varying(6) NOT NULL,
    open numeric(9,2),
    high numeric(9,2),
    low numeric(9,2),
    close numeric(9,2),
    volume numeric(12,0),
    sector_id numeric(7,0) NOT NULL
);
ALTER TABLE public.sector_quotes OWNER TO postgres;
ALTER TABLE ONLY sector_quotes ADD CONSTRAINT sector_quotes_pkey PRIMARY KEY (sector_id, exch, date);
