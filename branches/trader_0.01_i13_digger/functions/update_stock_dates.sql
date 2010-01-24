--
-- Name: update_stock_dates(character varying, character varying, date); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_stock_dates(new_symb character varying, new_exch character varying, new_date date) RETURNS void
LANGUAGE plpgsql
AS $$
    DECLARE
    rec_first_last RECORD;
    BEGIN
        -- Maintain the date range that this symbol has traded over so we don't have to search a big list of dates
        --   before we know if it's worth while considering a stock
        -- Also useful when working out the date ranges for new quotes to download
        select first_quote, last_quote into rec_first_last from stocks where symb = new_symb and exch = new_exch;
        IF NOT FOUND THEN
            insert into stocks ( symb, exch, name, first_quote, last_quote ) values ( new_symb, new_exch, 'Stock record created by quote insert not stock insert. Fix my name', new_date, new_date);
        ELSE
            IF new_date < rec_first_last.first_quote or rec_first_last.first_quote IS NULL THEN
                update stocks set first_quote = new_date where symb = new_symb and exch = new_exch;
            END IF;
            IF new_date > rec_first_last.last_quote or rec_first_last.last_quote IS NULL THEN
                update stocks set last_quote = new_date where symb = new_symb and exch = new_exch;
            END IF;
        END IF;
    END;
$$;
ALTER FUNCTION public.update_stock_dates(new_symb character varying, new_exch character varying, new_date date) OWNER TO postgres;
