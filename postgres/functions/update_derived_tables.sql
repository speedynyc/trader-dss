--
-- Name: update_derived_tables(); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_derived_tables() RETURNS "trigger"
LANGUAGE plpgsql
AS $$
BEGIN
    PERFORM update_moving_averages(NEW.date, NEW.symb, NEW.exch, NEW.adj_close, NEW.volume);
    PERFORM update_gains(NEW.date, NEW.symb, NEW.exch, NEW.adj_close);
    PERFORM update_gaps(NEW.date, NEW.symb, NEW.exch, NEW.low, NEW.high);
    PERFORM update_stock_dates(NEW.symb, NEW.exch, NEW.date);
    PERFORM update_trade_dates(NEW.exch, NEW.date);
    PERFORM update_williams_pcr(NEW.date, NEW.symb, NEW.exch, NEW.adj_close);
    -- Work out the max/min over the same periods
    -- Work out the trading range over the same periods
    RETURN NEW;
END
$$;
ALTER FUNCTION public.update_derived_tables() OWNER TO postgres;

