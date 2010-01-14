--
-- Name: sectors; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE sectors (
    sector_id numeric(7,0) DEFAULT nextval(('sequence_sector'::text)::regclass) NOT NULL,
    exch character varying(6) NOT NULL,
    name character varying(100) NOT NULL
);
ALTER TABLE public.sectors OWNER TO postgres;
