--
-- Name: update_gaps(date, character varying, character varying, numeric, numeric); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_gaps(new_date date, new_symb character varying, new_exch character varying, new_low numeric, new_high numeric) RETURNS void
LANGUAGE plpgsql
AS $$
DECLARE
    yesterdays_quote RECORD;
    yesterdays_gap RECORD;
    v_gap_sum_10 RECORD;
    v_gap_sum_20 RECORD;
    v_gap_sum_30 RECORD;
    v_gap_sum_50 RECORD;
    v_gap_sum_100 RECORD;
    v_gap_sum_200 RECORD;
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
            INSERT INTO gaps ( date, symb, exch, gap, last_gap, days_since_gap_up, days_since_gap_down, gap_sum_10, gap_sum_20, gap_sum_30, gap_sum_50, gap_sum_100, gap_sum_200 )
                VALUES ( new_date, new_symb, new_exch, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0' );
        EXCEPTION when unique_violation THEN
            update gaps set gap = '0', last_gap = '0', days_since_gap_up = '0', days_since_gap_down = '0',
                gap_sum_10 = '0', gap_sum_20 = '0', gap_sum_30 = '0', gap_sum_50 = '0', gap_sum_100 = '0', gap_sum_200 = '0'
                where date = new_date and symb = new_symb and exch = new_exch;
        END;
    else
        -- Get yesterday's gap to work out days_since_gap_up etc
        SELECT gap AS gap, last_gap AS last_gap, days_since_gap_up as days_since_gap_up, days_since_gap_down as days_since_gap_down INTO yesterdays_gap FROM 
            gaps where date < new_date and symb = new_symb and exch = new_exch order by date desc limit 1;
        -- Calculate the sum of the gaps over previous days
        SELECT sum(topN.gap) AS gap_sum INTO v_gap_sum_200 FROM 
            (SELECT gap FROM gaps WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 200) AS topN;
        SELECT sum(topN.gap) AS gap_sum INTO v_gap_sum_100 FROM 
            (SELECT gap FROM gaps WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 100) AS topN;
        SELECT sum(topN.gap) AS gap_sum INTO v_gap_sum_50 FROM 
            (SELECT gap FROM gaps WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 50) AS topN;
        SELECT sum(topN.gap) AS gap_sum INTO v_gap_sum_30 FROM 
            (SELECT gap FROM gaps WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 30) AS topN;
        SELECT sum(topN.gap) AS gap_sum INTO v_gap_sum_20 FROM 
            (SELECT gap FROM gaps WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 20) AS topN;
        SELECT sum(topN.gap) AS gap_sum INTO v_gap_sum_10 FROM 
            (SELECT gap FROM gaps WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 10) AS topN;
        -- work out today's gap
        if ( yesterdays_quote.high < new_low ) then
            -- gap up
            v_gap := new_low - yesterdays_quote.high;
            v_last_gap = v_gap;
            v_days_since_gap_up := 1;
            v_days_since_gap_down := gap_increment(yesterdays_gap.days_since_gap_down);
        elsif (yesterdays_quote.low > new_high) then
            -- gap down
            v_gap := new_high - yesterdays_quote.low;
            v_last_gap = v_gap;
            v_days_since_gap_down := 1;
            v_days_since_gap_up := gap_increment(yesterdays_gap.days_since_gap_up);
        else
            -- no gap today
            v_gap = 0;
            v_last_gap = yesterdays_gap.last_gap;
            v_days_since_gap_down := gap_increment(yesterdays_gap.days_since_gap_down);
            v_days_since_gap_up := gap_increment(yesterdays_gap.days_since_gap_up);
        end if;
        BEGIN
            INSERT INTO gaps ( date, symb, exch, gap, last_gap, days_since_gap_up, days_since_gap_down, gap_sum_10, gap_sum_20, gap_sum_30, gap_sum_50, gap_sum_100, gap_sum_200 )
                VALUES
                ( new_date, new_symb, new_exch, v_gap, v_last_gap, v_days_since_gap_up, v_days_since_gap_down, v_gap_sum_10.gap_sum + v_gap, v_gap_sum_20.gap_sum + v_gap, v_gap_sum_30.gap_sum + v_gap, v_gap_sum_50.gap_sum + v_gap, v_gap_sum_100.gap_sum + v_gap, v_gap_sum_200.gap_sum + v_gap );
        EXCEPTION when unique_violation THEN
            update gaps set gap = v_gap, last_gap = v_last_gap, days_since_gap_up = v_days_since_gap_up, days_since_gap_down = v_days_since_gap_down, gap_sum_10 = v_gap_sum_10.gap_sum, gap_sum_20 = v_gap_sum_20.gap_sum, gap_sum_30 = v_gap_sum_30.gap_sum, gap_sum_50 = v_gap_sum_50.gap_sum, gap_sum_100 = v_gap_sum_100.gap_sum, gap_sum_200 = v_gap_sum_200.gap_sum
                where date = new_date and symb = new_symb and exch = new_exch;
        END;
    end if;
END
$$;
ALTER FUNCTION public.update_gaps(new_date date, new_symb character varying, new_exch character varying, new_low numeric, new_high numeric) OWNER TO postgres;

-- We increment the days since the last gap as long and there has ever been a gap.
--    That means that we don't increment '0' but do increment every thing else
CREATE or replace FUNCTION gap_increment(v_yesterday_day_count gaps.days_since_gap_up%TYPE) returns gaps.days_since_gap_up%TYPE
    LANGUAGE plpgsql
    AS $$
    BEGIN
        if (v_yesterday_day_count <> 0) then
            return v_yesterday_day_count + 1;
        else
            return 0;
        end if;
    END;
$$;
ALTER FUNCTION gap_increment(v_yesterday_day_count gaps.days_since_gap_up%TYPE) OWNER TO postgres;
