--
-- Name: sectors; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE sectors (
    sector_id serial not null,
    exch character varying(6) NOT NULL,
    name character varying(100) NOT NULL
);
ALTER TABLE public.sectors OWNER TO postgres;
ALTER TABLE ONLY sectors ADD CONSTRAINT sector_pkey PRIMARY KEY (sector_id, exch);
