--
-- Name: view_everything; Type: VIEW; Schema: public; Owner: postgres
--
CREATE VIEW view_everything AS
    SELECT a.symb, a.name AS symb_name, a.exch, b.name AS exch_name, c.date, c.open, c.high, c.low, c."close", c.volume, d.gain_10, d.gain_20, d.gain_30, d.gain_50, d.gain_100, d.gain_200, e.close_ma_10, e.close_ma_20, e.close_ma_30, e.close_ma_50, e.close_ma_100, e.close_ma_200, f.close_sd_10, f.close_sd_20, f.close_sd_30, f.close_sd_50, f.close_sd_100, f.close_sd_200, f.volume_sd_10, f.volume_sd_20, f.volume_sd_30, f.volume_sd_50, f.volume_sd_100, f.volume_sd_200 FROM stocks a, exchange b, quotes c, gains d, simple_moving_averages e, standard_deviations_from_mean f WHERE (((((((((((((a.exch)::text = (b.exch)::text) AND ((a.symb)::text = (c.symb)::text)) AND ((a.exch)::text = (c.exch)::text)) AND ((a.symb)::text = (d.symb)::text)) AND ((a.exch)::text = (d.exch)::text)) AND (c.date = d.date)) AND ((a.symb)::text = (e.symb)::text)) AND ((a.exch)::text = (e.exch)::text)) AND (c.date = e.date)) AND ((a.symb)::text = (f.symb)::text)) AND ((a.exch)::text = (f.exch)::text)) AND (c.date = f.date));
ALTER TABLE public.view_everything OWNER TO postgres;

