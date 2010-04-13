--
-- Name: watch ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE watch (
    pfid integer not null,
    date date not null,
    symb character varying(10) NOT NULL,
    comment text,
    volume numeric(12) NOT NULL
);
ALTER TABLE public.watch OWNER TO postgres;
ALTER TABLE ONLY watch ADD CONSTRAINT watch_pkey PRIMARY KEY (pfid, symb, date);
