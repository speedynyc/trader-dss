--
-- Name: quotes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE quotes (
    date date NOT NULL,
    symb character varying(12) NOT NULL,
    exch character varying(6) NOT NULL,
    open numeric NOT NULL,
    high numeric NOT NULL,
    low numeric NOT NULL,
    close numeric NOT NULL,
    volume numeric NOT NULL,
    adj_close numeric
);
ALTER TABLE public.quotes OWNER TO postgres;
ALTER TABLE ONLY quotes ADD CONSTRAINT quotes_pkey PRIMARY KEY (date, symb, exch);
CREATE INDEX idx_symb_exch ON quotes USING btree (symb, exch);
create index idx_quotes_symb_exch_date on quotes (symb, exch, date);
