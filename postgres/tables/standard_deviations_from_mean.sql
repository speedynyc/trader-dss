--
-- Name: standard_deviations_from_mean; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE standard_deviations_from_mean
(
 date date NOT NULL,
 symb character varying(10) NOT NULL,
 exch character varying(6) NOT NULL,
 close_sd_10 numeric, -- The standard deviation of the close price over the last 10 days
 close_sd_20 numeric, -- The standard deviation of the close price over the last 20 days
 close_sd_30 numeric, -- The standard deviation of the close price over the last 30 days
 close_sd_50 numeric, -- The standard deviation of the close price over the last 50 days
 close_sd_100 numeric, -- The standard deviation of the close price over the last 100 days
 close_sd_200 numeric, -- The standard deviation of the close price over the last 200 days
 volume_sd_10 numeric, -- The standard deviation of the volume over the last 10 days
 volume_sd_20 numeric, -- The standard deviation of the volume over the last 20 days
 volume_sd_30 numeric, -- The standard deviation of the volume over the last 30 days
 volume_sd_50 numeric, -- The standard deviation of the volume over the last 50 days
 volume_sd_100 numeric, -- The standard deviation of the volume over the last 100 days
 volume_sd_200 numeric  -- The standard deviation of the volume over the last 200 days
 )
WITHOUT OIDS;
ALTER TABLE standard_deviations_from_mean OWNER TO postgres;
ALTER TABLE ONLY standard_deviations_from_mean ADD CONSTRAINT standard_deviations_from_mean_pkey PRIMARY KEY (date, symb, exch);
COMMENT ON COLUMN standard_deviations_from_mean.close_sd_10 IS 'The standard deviation of the close price over the last 10 days';
COMMENT ON COLUMN standard_deviations_from_mean.close_sd_20 IS 'The standard deviation of the close price over the last 20 days';
COMMENT ON COLUMN standard_deviations_from_mean.close_sd_30 IS 'The standard deviation of the close price over the last 30 days';
COMMENT ON COLUMN standard_deviations_from_mean.close_sd_50 IS 'The standard deviation of the close price over the last 50 days';
COMMENT ON COLUMN standard_deviations_from_mean.close_sd_100 IS 'The standard deviation of the close price over the last 100 days';
COMMENT ON COLUMN standard_deviations_from_mean.close_sd_200 IS 'The standard deviation of the close price over the last 200 days';
COMMENT ON COLUMN standard_deviations_from_mean.volume_sd_10 IS 'The standard deviation of the volume over the last 10 days';
COMMENT ON COLUMN standard_deviations_from_mean.volume_sd_20 IS 'The standard deviation of the volume over the last 20 days';
COMMENT ON COLUMN standard_deviations_from_mean.volume_sd_30 IS 'The standard deviation of the volume over the last 30 days';
COMMENT ON COLUMN standard_deviations_from_mean.volume_sd_50 IS 'The standard deviation of the volume over the last 50 days';
COMMENT ON COLUMN standard_deviations_from_mean.volume_sd_100 IS 'The standard deviation of the volume over the last 100 days';
COMMENT ON COLUMN standard_deviations_from_mean.volume_sd_200 IS 'The standard deviation of the volume over the last 200 days';

