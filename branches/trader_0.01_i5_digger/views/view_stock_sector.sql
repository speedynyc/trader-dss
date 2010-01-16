--
-- Name: view_stock_sector; Type: VIEW; Schema: public; Owner: postgres
--
CREATE VIEW view_stock_sector AS
    SELECT stock_sector.symb, stocks.name AS stock_name, stock_sector.sector_id, sectors.name AS sector_name FROM stock_sector, stocks, sectors WHERE (((stock_sector.symb)::text = (stocks.symb)::text) AND (stock_sector.sector_id = sectors.sector_id));
ALTER TABLE public.view_stock_sector OWNER TO postgres;
