--
-- Name: portfolios; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE portfolios (
    pfid serial not null,
    name character varying(100) NOT NULL,
    exch character varying(6) NOT NULL,
    uid integer not null,
    opening_balance numeric,
    parcel numeric,
    tax_rate numeric,
    commission numeric,
    working_date date not null,
    hide_names char(1),
    stop_loss smallint,
    auto_stop_loss char(1),
    unique (uid, exch, name)
);
ALTER TABLE public.portfolios OWNER TO postgres;
ALTER TABLE ONLY portfolios ADD CONSTRAINT portfolios_pkey PRIMARY KEY (pfid);
