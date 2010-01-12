--
-- Name: update_trade_dates(character varying, date); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE FUNCTION update_trade_dates(new_exch character varying, new_date date) RETURNS void
    LANGUAGE plpgsql
    AS $$
    DECLARE
        rec_trade_date RECORD;
    BEGIN
    -- record the dates on which a stock was traded. Not sure why...
    select exch, date into rec_trade_date from trade_dates where date = new_date and exch = new_exch;
    IF NOT FOUND THEN
        insert into trade_dates ( exch, date ) values ( new_exch, new_date);                                        
    END IF;
END;
$$;
ALTER FUNCTION public.update_trade_dates(new_exch character varying, new_date date) OWNER TO postgres;

