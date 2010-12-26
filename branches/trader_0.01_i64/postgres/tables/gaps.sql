--
-- Name: gaps; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gaps
(
    date date NOT NULL,
    symb character varying(12) NOT NULL,
    exch character varying(6) NOT NULL,
    gap numeric,
    last_gap numeric,
    gap_sum_10 numeric,
    gap_sum_20 numeric,
    gap_sum_30 numeric,
    gap_sum_50 numeric,
    gap_sum_100 numeric,
    gap_sum_200 numeric,
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
COMMENT ON COLUMN gaps.gap_sum_10 IS 'The sum of the gaps in the last 10 traded days';
COMMENT ON COLUMN gaps.gap_sum_20 IS 'The sum of the gaps in the last 20 traded days';
COMMENT ON COLUMN gaps.gap_sum_30 IS 'The sum of the gaps in the last 30 traded days';
COMMENT ON COLUMN gaps.gap_sum_50 IS 'The sum of the gaps in the last 50 traded days';
COMMENT ON COLUMN gaps.gap_sum_100 IS 'The sum of the gaps in the last 100 traded days';
COMMENT ON COLUMN gaps.gap_sum_200 IS 'The sum of the gaps in the last 200 traded days';
