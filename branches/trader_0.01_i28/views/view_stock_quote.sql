--
-- Name: view_stock_quote; Type: VIEW; Schema: public; Owner: postgres
--
CREATE VIEW view_stock_quote AS
    SELECT a.symb, a.name, b.date, b.exch, b.open, b.high, b.low, b."close", b.volume FROM stocks a, quotes b WHERE (((a.symb)::text = (b.symb)::text) AND ((a.exch)::text = (b.exch)::text));
ALTER TABLE public.view_stock_quote OWNER TO postgres;
