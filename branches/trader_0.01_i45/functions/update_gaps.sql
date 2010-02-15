--
-- Name: update_gaps(date, character varying, character varying, numeric, numeric); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_gaps(new_date date, new_symb character varying, new_exch character varying, new_low numeric, new_high numeric) RETURNS void
LANGUAGE plpgsql
AS $$
DECLARE
    yesterday RECORD;
    v_gap numeric(9,2);
BEGIN
    -- get yesterday's details from the quotes table
    SELECT high AS high, low AS low INTO yesterday FROM quotes where date < new_date and symb = new_symb and exch = new_exch order by date desc limit 1;
    if not found then
        -- no yesterday found, must be the first record
        BEGIN
            INSERT INTO gaps ( date, symb, exch, gap ) VALUES ( new_date, new_symb, new_exch, '0' );
        EXCEPTION when unique_violation THEN
            update simple_moving_averages set gap = '0' where date = new_date and symb = new_symb and exch = new_exch;
        END;
    else
        -- if we've gaped up, the gap should be positive
        if ( yesterday.high < new_low ) then
            v_gap := new_low - yesterday.high;
        elsif (yesterday.low > new_high) then
            v_gap := new_high - yesterday.low;
        else
            v_gap = 0;
        end if;
        BEGIN
            INSERT INTO gaps ( date, symb, exch, gap) VALUES ( new_date, new_symb, new_exch, v_gap);
        EXCEPTION when unique_violation THEN
            update simple_moving_averages set gap = v_gap where date = new_date and symb = new_symb and exch = new_exch;
        END;
    end if;
END
$$;
ALTER FUNCTION public.update_gaps(new_date date, new_symb character varying, new_exch character varying, new_low numeric, new_high numeric) OWNER TO postgres;
