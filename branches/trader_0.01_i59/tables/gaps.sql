--
-- Name: gaps; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gaps
(
    date date NOT NULL,
    symb character varying(10) NOT NULL,
    exch character varying(6) NOT NULL,
    gap numeric(9,2)
)
WITHOUT OIDS;
ALTER TABLE gaps OWNER TO postgres;
ALTER TABLE ONLY gaps ADD CONSTRAINT gaps_pkey PRIMARY KEY (date, symb, exch);
COMMENT ON COLUMN gaps.gap IS 'The gap between the previous close and current open';
