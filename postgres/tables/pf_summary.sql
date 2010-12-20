--
-- Name: pf_summary ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE pf_summary (
    pfid integer not null,
    date date not null,
    cash_in_hand numeric NOT NULL,
    holdings numeric NOT NULL
);
ALTER TABLE public.pf_summary OWNER TO postgres;
CREATE INDEX idx_pf_summary_pfid_date ON pf_summary (pfid, date);
