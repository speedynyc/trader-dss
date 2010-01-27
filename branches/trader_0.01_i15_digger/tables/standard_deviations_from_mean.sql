--
-- Name: standard_deviations_from_mean; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE standard_deviations_from_mean (
    date date NOT NULL,
    symb character varying(10) NOT NULL,
    exch character varying(6) NOT NULL,
    close_sd_10 numeric(9,4),
    close_sd_20 numeric(9,4),
    close_sd_30 numeric(9,4),
    close_sd_50 numeric(9,4),
    close_sd_100 numeric(9,4),
    close_sd_200 numeric(9,4),
    volume_sd_10 numeric(9,4),
    volume_sd_20 numeric(9,4),
    volume_sd_30 numeric(9,4),
    volume_sd_50 numeric(9,4),
    volume_sd_100 numeric(9,4),
    volume_sd_200 numeric(9,4)
);
ALTER TABLE public.standard_deviations_from_mean OWNER TO postgres;
ALTER TABLE ONLY standard_deviations_from_mean ADD CONSTRAINT standard_deviations_from_mean_pkey PRIMARY KEY (date, symb, exch);
