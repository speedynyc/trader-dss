--
-- Name: portfolios; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE portfolios (
    pfid serial not null,
    name character varying(100) NOT NULL,
    uid integer not null,
    parcel numeric(9,2),
    start_date date not null,
    working_date date not null
);
ALTER TABLE public.portfolios OWNER TO postgres;
ALTER TABLE ONLY portfolios ADD CONSTRAINT portfolios_pkey PRIMARY KEY (pfid);
