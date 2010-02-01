--
-- Name: trades ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE trades (
    trid serial not null,
    pfid integer not null,
    symb character varying(10) NOT NULL,
    date date not null,
    price numeric(9,2) NOT NULL,
    volume numeric(12) NOT NULL,
    comment varchar(100)
);
CREATE INDEX idx_trades_trid ON trades (trid);
ALTER TABLE public.trades OWNER TO postgres;
ALTER TABLE ONLY trades ADD CONSTRAINT trades_pkey PRIMARY KEY (pfid, symb, date);
