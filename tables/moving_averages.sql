--
-- Name: moving_averages; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE moving_averages
(
 date date NOT NULL,
 symb character varying(10) NOT NULL,
 exch character varying(6) NOT NULL,
 close_ma_10 numeric(9,2), -- Moving average on the close price over the last 10 trading days
 close_ma_20 numeric(9,2), -- Moving average on the close price over the last 20 trading days
 close_ma_30 numeric(9,2), -- Moving average on the close price over the last 30 trading days
 close_ma_50 numeric(9,2), -- Moving average on the close price over the last 50 trading days
 close_ma_100 numeric(9,2), -- Moving average on the close price over the last 100 trading days
 close_ma_200 numeric(9,2), -- Moving average on the close price over the last 200 trading days
 volume_ma_10 numeric(12), -- Moving average on the volume over the last 10 trading days
 volume_ma_20 numeric(12), -- Moving average on the volume over the last 20 trading days
 volume_ma_30 numeric(12), -- Moving average on the volume over the last 30 trading days
 volume_ma_50 numeric(12), -- Moving average on the volume over the last 50 trading days
 volume_ma_100 numeric(12), -- Moving average on the volume over the last 100 trading days
 volume_ma_200 numeric(12), -- Moving average on the volume over the last 200 trading days
 ma_10_diff numeric(9,2), -- The difference between the close price and the 10 day moving average. (close price - 10 day moving average of the close price)
 ma_20_diff numeric(9,2), -- The difference between the close price and the 20 day moving average. (close price - 20 day moving average of the close price)
 ma_30_diff numeric(9,2), -- The difference between the close price and the 30 day moving average. (close price - 30 day moving average of the close price)
 ma_50_diff numeric(9,2), -- The difference between the close price and the 50 day moving average. (close price - 50 day moving average of the close price)
 ma_100_diff numeric(9,2), -- The difference between the close price and the 100 day moving average. (close price - 100 day moving average of the close price)
 ma_200_diff numeric(9,2), -- The difference between the close price and the 200 day moving average. (close price - 200 day moving average of the close price)
 ma_10_dir integer, -- sign(ma_10_diff)
 ma_20_dir integer, -- sign(ma_20_diff)
 ma_30_dir integer, -- sign(ma_30_diff)
 ma_50_dir integer, -- sign(ma_50_diff)
 ma_100_dir integer, -- sign(ma_100_diff)
 ma_200_dir integer, -- sign(ma_200_diff)
 ma_10_run integer, -- The number of days since ma_10_diff changed signs, or the number of days that the close price has been above or below the moving average.
 ma_20_run integer, -- The number of days since ma_20_diff changed signs, or the number of days that the close price has been above or below the moving average.
 ma_30_run integer, -- The number of days since ma_30_diff changed signs, or the number of days that the close price has been above or below the moving average.
 ma_50_run integer, -- The number of days since ma_50_diff changed signs, or the number of days that the close price has been above or below the moving average.
 ma_100_run integer, -- The number of days since ma_100_diff changed signs, or the number of days that the close price has been above or below the moving average.
 ma_200_run integer, -- The number of days since ma_200_diff changed signs, or the number of days that the close price has been above or below the moving average.
 ma_10_sum numeric(12,2), -- The sum of ma_10_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.
 ma_20_sum numeric(12,2), -- The sum of ma_20_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.
 ma_30_sum numeric(12,2), -- The sum of ma_30_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.
 ma_50_sum numeric(12,2), -- The sum of ma_50_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.
 ma_100_sum numeric(12,2), -- The sum of ma_100_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.
 ma_200_sum numeric(12,2),  -- The sum of ma_200_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.
 ema_12 numeric(9,2),
 ema_26 numeric(9,2)
)
WITHOUT OIDS;
ALTER TABLE moving_averages OWNER TO postgres;
ALTER TABLE ONLY moving_averages ADD CONSTRAINT moving_averages_pkey PRIMARY KEY (date, symb, exch);
COMMENT ON COLUMN moving_averages.close_ma_10 IS 'Moving average on the close price over the last 10 trading days';
COMMENT ON COLUMN moving_averages.close_ma_20 IS 'Moving average on the close price over the last 20 trading days';
COMMENT ON COLUMN moving_averages.close_ma_30 IS 'Moving average on the close price over the last 30 trading days';
COMMENT ON COLUMN moving_averages.close_ma_50 IS 'Moving average on the close price over the last 50 trading days';
COMMENT ON COLUMN moving_averages.close_ma_100 IS 'Moving average on the close price over the last 100 trading days';
COMMENT ON COLUMN moving_averages.close_ma_200 IS 'Moving average on the close price over the last 200 trading days';
COMMENT ON COLUMN moving_averages.volume_ma_10 IS 'Moving average on the volume over the last 10 trading days';
COMMENT ON COLUMN moving_averages.volume_ma_20 IS 'Moving average on the volume over the last 20 trading days';
COMMENT ON COLUMN moving_averages.volume_ma_30 IS 'Moving average on the volume over the last 30 trading days';
COMMENT ON COLUMN moving_averages.volume_ma_50 IS 'Moving average on the volume over the last 50 trading days';
COMMENT ON COLUMN moving_averages.volume_ma_100 IS 'Moving average on the volume over the last 100 trading days';
COMMENT ON COLUMN moving_averages.volume_ma_200 IS 'Moving average on the volume over the last 200 trading days';
COMMENT ON COLUMN moving_averages.ma_10_diff IS 'The difference between the close price and the 10 day moving average. (close price - 10 day moving average of the close price)';
COMMENT ON COLUMN moving_averages.ma_20_diff IS 'The difference between the close price and the 20 day moving average. (close price - 20 day moving average of the close price)';
COMMENT ON COLUMN moving_averages.ma_30_diff IS 'The difference between the close price and the 30 day moving average. (close price - 30 day moving average of the close price)';
COMMENT ON COLUMN moving_averages.ma_50_diff IS 'The difference between the close price and the 50 day moving average. (close price - 50 day moving average of the close price)';
COMMENT ON COLUMN moving_averages.ma_100_diff IS 'The difference between the close price and the 100 day moving average. (close price - 100 day moving average of the close price)';
COMMENT ON COLUMN moving_averages.ma_200_diff IS 'The difference between the close price and the 200 day moving average. (close price - 200 day moving average of the close price)';
COMMENT ON COLUMN moving_averages.ma_10_dir IS 'sign(ma_10_diff)';
COMMENT ON COLUMN moving_averages.ma_20_dir IS 'sign(ma_20_diff)';
COMMENT ON COLUMN moving_averages.ma_30_dir IS 'sign(ma_30_diff)';
COMMENT ON COLUMN moving_averages.ma_50_dir IS 'sign(ma_50_diff)';
COMMENT ON COLUMN moving_averages.ma_100_dir IS 'sign(ma_100_diff)';
COMMENT ON COLUMN moving_averages.ma_200_dir IS 'sign(ma_200_diff)';
COMMENT ON COLUMN moving_averages.ma_10_run IS 'The number of days since ma_10_diff changed signs, or the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_20_run IS 'The number of days since ma_20_diff changed signs, or the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_30_run IS 'The number of days since ma_30_diff changed signs, or the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_50_run IS 'The number of days since ma_50_diff changed signs, or the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_100_run IS 'The number of days since ma_100_diff changed signs, or the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_200_run IS 'The number of days since ma_200_diff changed signs, or the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_10_sum IS 'The sum of ma_10_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_20_sum IS 'The sum of ma_20_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_30_sum IS 'The sum of ma_30_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_50_sum IS 'The sum of ma_50_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_100_sum IS 'The sum of ma_100_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ma_200_sum IS 'The sum of ma_200_diff since it last changed signs, or the total of the difference between the close price and the moving average for the number of days that the close price has been above or below the moving average.';
COMMENT ON COLUMN moving_averages.ema_12 IS 'The 12 day Exponential Moving Average';
COMMENT ON COLUMN moving_averages.ema_26 IS 'The 26 day Exponential Moving Average';

