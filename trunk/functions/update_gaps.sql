--
-- Name: update_gaps(date, character varying, character varying, numeric, numeric); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_gaps(new_date date, new_symb character varying, new_exch character varying, new_low numeric, new_high numeric) RETURNS void
LANGUAGE plpgsql
AS $$
DECLARE
    yesterdays_quote RECORD;
    yesterdays_gap RECORD;
    v_gap gaps.gap%TYPE;
    v_days_since_gap_up gaps.days_since_gap_up%TYPE;
    v_days_since_gap_down gaps.days_since_gap_down%TYPE;
    v_last_gap gaps.last_gap%TYPE;
BEGIN
    -- get yesterday's details from the quotes table
    SELECT high AS high, low AS low INTO yesterdays_quote FROM quotes where date < new_date and symb = new_symb and exch = new_exch order by date desc limit 1;
    if not found then
        -- no yesterday found, must be the first record
        BEGIN
            INSERT INTO gaps ( date, symb, exch, gap, last_gap, days_since_gap_up, days_since_gap_down ) VALUES ( new_date, new_symb, new_exch, '0', '0', '0', '0' );
        EXCEPTION when unique_violation THEN
            update gaps set gap = '0', last_gap = '0', days_since_gap_up = '0', days_since_gap_down = '0' where date = new_date and symb = new_symb and exch = new_exch;
        END;
    else
        -- Get yesterday's gap to work out days_since_gap_up etc
        SELECT gap AS gap, last_gap AS last_gap, days_since_gap_up as days_since_gap_up, days_since_gap_down as days_since_gap_down INTO yesterdays_gap FROM gaps where date < new_date and symb = new_symb and exch = new_exch order by date desc limit 1;
        if ( yesterdays_quote.high < new_low ) then
            -- gap up
            v_gap := new_low - yesterdays_quote.high;
            v_last_gap = v_gap;
            v_days_since_gap_up := 1;
            if ( yesterdays_gap.days_since_gap_down <> 0 ) then
                v_days_since_gap_down := yesterdays_gap.days_since_gap_down + 1;
            else
                v_days_since_gap_down := 0;
            end if;
        elsif (yesterdays_quote.low > new_high) then
            -- gap down
            v_gap := new_high - yesterdays_quote.low;
            v_last_gap = v_gap;
            v_days_since_gap_down := 1;
            if ( yesterdays_gap.days_since_gap_up <> 0 ) then
                v_days_since_gap_up := yesterdays_gap.days_since_gap_up + 1;
            else
                v_days_since_gap_up := 0;
            end if;
        else
            v_gap = 0;
            v_last_gap = yesterdays_gap.last_gap;
            if ( yesterdays_gap.days_since_gap_down <> 0 ) then
                v_days_since_gap_down := yesterdays_gap.days_since_gap_down + 1;
            else
                v_days_since_gap_down := 0;
            end if;
            if ( yesterdays_gap.days_since_gap_up <> 0 ) then
                v_days_since_gap_up := yesterdays_gap.days_since_gap_up + 1;
            else
                v_days_since_gap_up := 0;
            end if;
        end if;
        BEGIN
            INSERT INTO gaps ( date, symb, exch, gap, last_gap, days_since_gap_up, days_since_gap_down) VALUES ( new_date, new_symb, new_exch, v_gap, v_last_gap, v_days_since_gap_up, v_days_since_gap_down);
        EXCEPTION when unique_violation THEN
            update gaps set gap = v_gap, last_gap = v_last_gap, days_since_gap_up = v_days_since_gap_up, days_since_gap_down = v_days_since_gap_down where date = new_date and symb = new_symb and exch = new_exch;
        END;
    end if;
END
$$;
ALTER FUNCTION public.update_gaps(new_date date, new_symb character varying, new_exch character varying, new_low numeric, new_high numeric) OWNER TO postgres;
