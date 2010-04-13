--
-- Name: update_trade_dates(character varying, date); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_trade_dates(new_exch character varying, new_date date) RETURNS void
LANGUAGE plpgsql
AS $$
    -- this function records the dates that an exchange traded
    BEGIN
        BEGIN
            insert into trade_dates ( exch, date, up_to_date ) VALUES ( new_exch, new_date, FALSE);
        EXCEPTION when unique_violation THEN
            -- mark that the exchange summary info needs to be updated
            update trade_dates set up_to_date = FALSE where exch = new_exch and date = new_date;
        END;
    END;
$$;
ALTER FUNCTION public.update_trade_dates(new_exch character varying, new_date date) OWNER TO postgres;

