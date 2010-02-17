--
-- Name: update_trade_dates(character varying, date); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_trade_dates(new_exch character varying, new_date date) RETURNS void
LANGUAGE plpgsql
AS $$
    -- this functino records the dates that an exchange traded
    BEGIN
        BEGIN
            insert into trade_dates ( exch, date ) VALUES ( new_exch, new_date);
        EXCEPTION when unique_violation THEN
        -- do nothing because the record is already there
        END;
    END;
$$;
ALTER FUNCTION public.update_trade_dates(new_exch character varying, new_date date) OWNER TO postgres;

