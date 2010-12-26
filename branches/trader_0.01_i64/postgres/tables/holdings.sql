--
-- Name: holdings ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE holdings (
    hid serial not null,
    pfid integer not null,
    symb character varying(12) NOT NULL,
    date date not null,
    price numeric NOT NULL,
    volume numeric NOT NULL,
    comment text
);
ALTER TABLE ONLY holdings ADD CONSTRAINT holdings_pkey PRIMARY KEY (hid);
ALTER TABLE public.holdings OWNER TO postgres;
