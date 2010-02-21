--
-- Name: update_williams_pcr(date, character varying, character varying, numeric); Type: FUNCTION; Schema: public; Owner: postgres
--
-- $Id$
--
CREATE or replace FUNCTION update_williams_pcr(new_date date, new_symb character varying, new_exch character varying, new_close numeric) RETURNS void
LANGUAGE plpgsql
AS $$
    DECLARE
        wpr10 RECORD;
        wpr20 RECORD;
        wpr30 RECORD;
        wpr50 RECORD;
        wpr100 RECORD;
        wpr200 RECORD;
    wpr10n numeric(9,2);
    wpr10d numeric(9,2);
    wpr10v numeric(9,2);
    wpr20n numeric(9,2);
    wpr20d numeric(9,2);
    wpr20v numeric(9,2);
    wpr30n numeric(9,2);
    wpr30d numeric(9,2);
    wpr30v numeric(9,2);
    wpr50n numeric(9,2);
    wpr50d numeric(9,2);
    wpr50v numeric(9,2);
    wpr100n numeric(9,2);
    wpr100d numeric(9,2);
    wpr100v numeric(9,2);
    wpr200n numeric(9,2);
    wpr200d numeric(9,2);
    wpr200v numeric(9,2);
    BEGIN
    -- work out the williams %R for 10, 20, 30, 50, 100 and 200 previous trading days.
    -- wpr = (close-highN)/(highN-lowN) * 100
    select max(wprN.high) as wpr_high, min(wprN.low) as wpr_low into wpr200 from (select high, low from quotes where date <= new_date and symb = new_symb and exch = new_exch order by date desc limit 200) as wprN;
    select max(wprN.high) as wpr_high, min(wprN.low) as wpr_low into wpr100 from (select high, low from quotes where date <= new_date and symb = new_symb and exch = new_exch order by date desc limit 100) as wprN;
    select max(wprN.high) as wpr_high, min(wprN.low) as wpr_low into wpr50 from (select high, low from quotes where date <= new_date and symb = new_symb and exch = new_exch order by date desc limit 50) as wprN;
    select max(wprN.high) as wpr_high, min(wprN.low) as wpr_low into wpr30 from (select high, low from quotes where date <= new_date and symb = new_symb and exch = new_exch order by date desc limit 30) as wprN;
    select max(wprN.high) as wpr_high, min(wprN.low) as wpr_low into wpr20 from (select high, low from quotes where date <= new_date and symb = new_symb and exch = new_exch order by date desc limit 20) as wprN;
    select max(wprN.high) as wpr_high, min(wprN.low) as wpr_low into wpr10 from (select high, low from quotes where date <= new_date and symb = new_symb and exch = new_exch order by date desc limit 10) as wprN;
    -- insert the results into indicators table
    if new_close is NULL or wpr10.wpr_high is NULL or wpr10.wpr_low is NULL then
    wpr10v := NULL;
    else
    wpr10n := new_close - wpr10.wpr_high;
    wpr10d := wpr10.wpr_high - wpr10.wpr_low;
    -- raise notice 'wpr10n = new_close - wpr10.wpr_high -> % = % - %', wpr10n, new_close, wpr10.wpr_high;
    -- raise notice 'wpr10d := wpr10.wpr_high - wpr10.wpr_low -> % = % - %', wpr10d, wpr10.wpr_high, wpr10.wpr_low;
    if wpr10d = 0 then
        wpr10v := -100;
    else
        wpr10v := wpr10n / wpr10d * 100;
    end if;
    -- raise notice '[INFO]% % wpr10v = % / % = %', new_date, new_symb, wpr10n, wpr10d, wpr10v;
    end if;
    if new_close is NULL or wpr20.wpr_high is NULL or wpr20.wpr_low is NULL then
    wpr20v := NULL;
    else
    wpr20n := new_close - wpr20.wpr_high;
    wpr20d := wpr20.wpr_high - wpr20.wpr_low;
    if wpr20d = 0 then
        wpr20v := -100;
    else
        wpr20v := wpr20n / wpr20d * 100;
    end if;
    -- raise notice '[INFO]% % wpr20v = % / % = %', new_date, new_symb, wpr20n, wpr20d, wpr20v;
    end if;
    if new_close is NULL or wpr30.wpr_high is NULL or wpr30.wpr_low is NULL then
    wpr30v := NULL;
    else
    wpr30n := new_close - wpr30.wpr_high;
    wpr30d := wpr30.wpr_high - wpr30.wpr_low;
    if wpr30d = 0 then
        wpr30v := -100;
    else
        wpr30v := wpr30n / wpr30d * 100;
    end if;
    -- raise notice '[INFO]% % wpr30v = % / % = %', new_date, new_symb, wpr30n, wpr30d, wpr30v;
    end if;
    if new_close is NULL or wpr50.wpr_high is NULL or wpr50.wpr_low is NULL then
    wpr50v := NULL;
    else
    wpr50n := new_close - wpr50.wpr_high;
    wpr50d := wpr50.wpr_high - wpr50.wpr_low;
    if wpr50d = 0 then
        wpr50v := -100;
    else
        wpr50v := wpr50n / wpr50d * 100;
    end if;
    -- raise notice '[INFO]% % wpr50v = % / % = %', new_date, new_symb, wpr50n, wpr50d, wpr50v;
    end if;
    if new_close is NULL or wpr100.wpr_high is NULL or wpr100.wpr_low is NULL then
    wpr100v := NULL;
    else
    wpr100n := new_close - wpr100.wpr_high;
    wpr100d := wpr100.wpr_high - wpr100.wpr_low;
    if wpr100d = 0 then
        wpr100v := -100;
    else
        wpr100v := wpr100n / wpr100d * 100;
    end if;
    -- raise notice '[INFO]% % wpr100v = % / % = %', new_date, new_symb, wpr100n, wpr100d, wpr100v;
    end if;
    if new_close is NULL or wpr20.wpr_high is NULL or wpr200.wpr_low is NULL then
    wpr200v := NULL;
    else
    wpr200n := new_close - wpr200.wpr_high;
    wpr200d := wpr200.wpr_high - wpr200.wpr_low;
    if wpr200d = 0 then
        wpr200v := -100;
    else
        wpr200v := wpr200n / wpr200d * 100;
    end if;
    -- raise notice '[INFO]% % wpr200v = % / % = %', new_date, new_symb, wpr200n, wpr200d, wpr200v;
    end if;
    update indicators set wpr_10 = wpr10v, wpr_20 = wpr20v, wpr_30 = wpr30v, wpr_50 = wpr50v, wpr_100 = wpr100v, wpr_200 = wpr200v where date = new_date and symb = new_symb and exch = new_exch;
    if not found then
        insert into indicators (
            date, symb, exch, wpr_10, wpr_20, wpr_30, wpr_50, wpr_100, wpr_200
            ) values (
            new_date, new_symb, new_exch, wpr10v, wpr20v, wpr30v, wpr50v, wpr100v, wpr200v
            );
    end if;
END
$$;
ALTER FUNCTION public.update_williams_pcr(new_date date, new_symb character varying, new_exch character varying, new_close numeric) OWNER TO postgres;
