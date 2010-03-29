--
-- Name: holdings ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE holdings (
    hid serial not null,
    pfid integer not null,
    symb character varying(10) NOT NULL,
    date date not null,
    price numeric(9,2) NOT NULL,
    volume numeric(12) NOT NULL,
    comment text
);
ALTER TABLE ONLY holdings ADD CONSTRAINT holdings_pkey PRIMARY KEY (hid);
ALTER TABLE public.holdings OWNER TO postgres;
