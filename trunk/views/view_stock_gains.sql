--
-- TOC entry 1215 (class 1259 OID 16416)
-- Dependencies: 1287 5
-- Name: view_stock_gains; Type: VIEW; Schema: public; Owner: postgres
--
CREATE VIEW view_stock_gains AS
    SELECT a.date, a.symb, a.exch, a."close", a.volume, b.d_10, b.c_10, b.gain_10, b.d_20, b.c_20, b.gain_20, b.d_30, b.c_30, b.gain_30, b.d_50, b.c_50, b.gain_50, b.d_100, b.c_100, b.gain_100, b.d_200, b.c_200, b.gain_200 FROM quotes a, gains b WHERE (((a.date = b.date) AND ((a.symb)::text = (b.symb)::text)) AND ((a.exch)::text = (b.exch)::text)) ORDER BY a.date;
ALTER TABLE public.stock_gains OWNER TO postgres;
