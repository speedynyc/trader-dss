--
-- Name: ema(real, real, integer); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION ema(v_close real, v_ema_yesterday real, v_ema_period integer) RETURNS real
    LANGUAGE plpgsql
    AS $$
    DECLARE
        v_alpha numeric(9,6);
    BEGIN
        v_alpha := 2 / (v_ema_period + 1);
        return v_ema_yesterday + (v_alpha * (v_close - v_ema_yesterday));
    END;
$$;
ALTER FUNCTION public.ema(v_close real, v_ema_yesterday real, v_ema_period integer) OWNER TO postgres;
