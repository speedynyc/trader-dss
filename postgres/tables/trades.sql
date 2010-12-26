--
-- Name: trades ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE trades (
    trid serial not null,
    pfid integer not null,
    hid integer not null,
    symb character varying(12) NOT NULL,
    date date not null,
    price numeric NOT NULL,
    volume numeric NOT NULL,
    tr_type char(1) NOT NULL,
    comment text
);
ALTER TABLE public.trades OWNER TO postgres;
ALTER TABLE ONLY trades ADD CONSTRAINT trades_pkey PRIMARY KEY (trid);
