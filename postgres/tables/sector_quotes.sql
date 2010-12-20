--
-- Name: sector_quotes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE sector_quotes (
    count integer,
    date date NOT NULL,
    exch character varying(6) NOT NULL,
    open numeric,
    high numeric,
    low numeric,
    close numeric,
    volume numeric,
    sector_id integer NOT NULL
);
ALTER TABLE public.sector_quotes OWNER TO postgres;
ALTER TABLE ONLY sector_quotes ADD CONSTRAINT sector_quotes_pkey PRIMARY KEY (sector_id, exch, date);
