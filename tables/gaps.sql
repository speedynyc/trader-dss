--
-- Name: gaps; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gaps
(
    date date NOT NULL,
    symb character varying(10) NOT NULL,
    exch character varying(6) NOT NULL,
    gap numeric(9,2),
    last_gap numeric(9,2),
    days_since_gap_up integer,
    days_since_gap_down integer
)
WITHOUT OIDS;
ALTER TABLE gaps OWNER TO postgres;
ALTER TABLE ONLY gaps ADD CONSTRAINT gaps_pkey PRIMARY KEY (date, symb, exch);
COMMENT ON COLUMN gaps.gap IS 'The gap between the previous close and current open';
COMMENT ON COLUMN gaps.last_gap IS 'The most recent gap or zero if there has not been one';
COMMENT ON COLUMN gaps.days_since_gap_up IS 'The count of days since the last gap up or zero';
COMMENT ON COLUMN gaps.days_since_gap_down IS 'The count of days since the last gap down or zero';
