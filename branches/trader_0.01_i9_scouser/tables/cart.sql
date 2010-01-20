--
-- Name: cart ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE cart (
    pfid integer not null,
    date date not null,
    symb character varying(10) NOT NULL,
    comment varchar(100),
    volume numeric(12) NOT NULL
);
ALTER TABLE public.cart OWNER TO postgres;
ALTER TABLE ONLY cart ADD CONSTRAINT cart_pkey PRIMARY KEY (pfid, symb, date);
