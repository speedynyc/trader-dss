--
-- Name: deposits ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE deposits (
    pfid integer not null,
    comment varchar(100),
    value numeric(9,2) NOT NULL,
    date date not null
);
ALTER TABLE public.deposits OWNER TO postgres;
CREATE INDEX idx_deposits_pfid_date ON deposits (pfid, date);
