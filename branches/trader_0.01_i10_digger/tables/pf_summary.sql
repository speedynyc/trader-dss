--
-- Name: pf_summary ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE pf_summary (
    pfid integer not null,
    date date not null,
    pot numeric(9,2) NOT NULL,
    value numeric(9,2) NOT NULL,
    tot numeric(9,2) NOT NULL
);
ALTER TABLE public.pf_summary OWNER TO postgres;
CREATE INDEX idx_pf_summary_pfid_date ON pf_summary (pfid, date);
