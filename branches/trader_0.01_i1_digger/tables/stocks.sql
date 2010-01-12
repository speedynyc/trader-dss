--
-- Name: stocks; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE stocks (
    symb character varying(10) NOT NULL,
    name character varying(100) NOT NULL,
    exch character varying(6) NOT NULL,
    first_quote date,
    last_quote date
);
ALTER TABLE public.stocks OWNER TO postgres;
ALTER TABLE ONLY stocks ADD CONSTRAINT stocks_pkey PRIMARY KEY (symb, exch);
ALTER TABLE ONLY stocks ADD CONSTRAINT constraint_exchange FOREIGN KEY (exch) REFERENCES exchange(exch) MATCH FULL;
