--
-- Name: watch ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE watch (
    pfid integer not null,
    date date not null,
    symb character varying(12) NOT NULL,
    comment text,
    volume numeric NOT NULL
);
ALTER TABLE public.watch OWNER TO postgres;
ALTER TABLE ONLY watch ADD CONSTRAINT watch_pkey PRIMARY KEY (pfid, symb, date);
