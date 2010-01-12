--
-- Name: deviation(real, real, real); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION deviation(value real, mean real, sd real) RETURNS real
    LANGUAGE plpgsql
    AS $$
    BEGIN
        IF sd = 0 THEN
            RETURN 0;
        END IF;
        RETURN ( value - mean ) / sd;
    END;
$$;
ALTER FUNCTION public.deviation(value real, mean real, sd real) OWNER TO postgres;
