--
-- Name: quotes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE quotes (
    date date NOT NULL,
    symb character varying(10) NOT NULL,
    exch character varying(6) NOT NULL,
    open numeric(9,2) NOT NULL,
    high numeric(9,2) NOT NULL,
    low numeric(9,2) NOT NULL,
    close numeric(9,2) NOT NULL,
    volume numeric(12,0) NOT NULL,
    adj_close numeric(9,2)
);
ALTER TABLE public.quotes OWNER TO postgres;
ALTER TABLE ONLY quotes ADD CONSTRAINT quotes_pkey PRIMARY KEY (date, symb, exch);
CREATE INDEX idx_symb_exch ON quotes USING btree (symb, exch);
create index idx_quotes_symb_exch_date on quotes (symb, exch, date);
