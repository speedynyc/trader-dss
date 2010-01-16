--
-- Name: indicators; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE indicators (
    date date NOT NULL,
    symb character varying(10) NOT NULL,
    exch character varying(6) NOT NULL,
    wpr_10 numeric(9,2),
    wpr_20 numeric(9,2),
    wpr_30 numeric(9,2),
    wpr_50 numeric(9,2),
    wpr_100 numeric(9,2),
    wpr_200 numeric(9,2),
    mapr_10 numeric(9,2),
    mapr_20 numeric(9,2),
    mapr_30 numeric(9,2),
    mapr_50 numeric(9,2),
    mapr_100 numeric(9,2),
    mapr_200 numeric(9,2)
);
ALTER TABLE public.indicators OWNER TO postgres;
