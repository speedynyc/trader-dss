--
-- Name: update_moving_averages(date, character varying, character varying, numeric, numeric); Type: FUNCTION; Schema: public; Owner: postgres
--
CREATE or replace FUNCTION update_moving_averages(new_date date, new_symb character varying, new_exch character varying, new_close numeric, new_volume numeric) RETURNS void
LANGUAGE plpgsql
AS $$
DECLARE
        avg10 RECORD;
        avg20 RECORD;
        avg30 RECORD;
        avg50 RECORD;
        avg100 RECORD;
        avg200 RECORD;
        last_ma_10 RECORD;
        last_ma_20 RECORD;
        last_ma_30 RECORD;
        last_ma_50 RECORD;
        last_ma_100 RECORD;
        last_ma_200 RECORD;
        last_ema RECORD;
        last_mcad RECORD;
        v_ma_10_diff moving_averages.ma_10_diff%TYPE;
        v_ma_20_diff moving_averages.ma_20_diff%TYPE;
        v_ma_30_diff moving_averages.ma_30_diff%TYPE;
        v_ma_50_diff moving_averages.ma_50_diff%TYPE;
        v_ma_100_diff moving_averages.ma_100_diff%TYPE;
        v_ma_200_diff moving_averages.ma_200_diff%TYPE;
        v_ma_10_dir moving_averages.ma_10_dir%TYPE;
        v_ma_20_dir moving_averages.ma_20_dir%TYPE;
        v_ma_30_dir moving_averages.ma_30_dir%TYPE;
        v_ma_50_dir moving_averages.ma_50_dir%TYPE;
        v_ma_100_dir moving_averages.ma_100_dir%TYPE;
        v_ma_200_dir moving_averages.ma_200_dir%TYPE;
        v_ma_10_run moving_averages.ma_10_run%TYPE;
        v_ma_20_run moving_averages.ma_20_run%TYPE;
        v_ma_30_run moving_averages.ma_30_run%TYPE;
        v_ma_50_run moving_averages.ma_50_run%TYPE;
        v_ma_100_run moving_averages.ma_200_run%TYPE;
        v_ma_200_run moving_averages.ma_100_run%TYPE;
        v_ma_10_sum moving_averages.ma_10_sum%TYPE;
        v_ma_20_sum moving_averages.ma_20_sum%TYPE;
        v_ma_30_sum moving_averages.ma_30_sum%TYPE;
        v_ma_50_sum moving_averages.ma_50_sum%TYPE;
        v_ma_100_sum moving_averages.ma_100_sum%TYPE;
        v_ma_200_sum moving_averages.ma_200_sum%TYPE;
        v_ma_10_MAPR indicators.mapr_10%TYPE;
        v_ma_20_MAPR indicators.mapr_20%TYPE;
        v_ma_30_MAPR indicators.mapr_30%TYPE;
        v_ma_50_MAPR indicators.mapr_50%TYPE;
        v_ma_100_MAPR indicators.mapr_100%TYPE;
        v_ma_200_MAPR indicators.mapr_200%TYPE;
        v_ema_10 moving_averages.ema_10%TYPE;
        v_ema_20 moving_averages.ema_20%TYPE;
        v_ema_30 moving_averages.ema_30%TYPE;
        v_ema_50 moving_averages.ema_50%TYPE;
        v_ema_100 moving_averages.ema_100%TYPE;
        v_ema_200 moving_averages.ema_200%TYPE;
        v_ema_12 moving_averages.ema_12%TYPE;
        v_ema_26 moving_averages.ema_26%TYPE;
        v_mcad indicators.mcad%TYPE;
        v_mcad_signal indicators.mcad_signal%TYPE;
        v_mcad_histogram indicators.mcad_histogram%TYPE;
BEGIN
    -- work out the moving averages for 10, 20, 30, 50, 100 and 200 previous trading days.
    -- order from lagest date range to smallest to get the disk cache on our side
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg200 
        FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 200) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg100
        FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 100) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg50 
        FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 50) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg30 
        FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 30) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg20 
        FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 20) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg10 
        FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 10) AS topN;
    -- find all the previous dir (direction) and run (number of days in that direction) from the moving_averages table
    -- This could be done as one query, but is easier to write this way
    -- These don't need to be calculated if the direction is 0, so an optimisation could be to move them into the logic for calculating the ma_XX_dir below
    -- I don't think there will be many diffs of 0
    SELECT ma_10_sum as last_ma_sum, ma_10_dir AS last_ma_dir, ma_10_run AS last_ma_run INTO last_ma_10 
        FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_20_sum as last_ma_sum, ma_20_dir AS last_ma_dir, ma_20_run AS last_ma_run INTO last_ma_20 
        FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_30_sum as last_ma_sum, ma_30_dir AS last_ma_dir, ma_30_run AS last_ma_run INTO last_ma_30 
        FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_50_sum as last_ma_sum, ma_50_dir AS last_ma_dir, ma_50_run AS last_ma_run INTO last_ma_50 
        FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_100_sum as last_ma_sum, ma_100_dir AS last_ma_dir, ma_100_run AS last_ma_run INTO last_ma_100 
        FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_200_sum as last_ma_sum, ma_200_dir AS last_ma_dir, ma_200_run AS last_ma_run INTO last_ma_200 
        FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    -- calculate the difference between close and the moving averages
    v_ma_10_diff := new_close - avg10.avg_close;
    v_ma_20_diff := new_close - avg20.avg_close;
    v_ma_30_diff := new_close - avg30.avg_close;
    v_ma_50_diff := new_close - avg50.avg_close;
    v_ma_100_diff := new_close - avg100.avg_close;
    v_ma_200_diff := new_close - avg200.avg_close;
    -- calculate the direction of the difference and the number of days in that direction
    if v_ma_10_diff <> 0 then
        v_ma_10_dir := sign(v_ma_10_diff);
        if v_ma_10_dir <> last_ma_10.last_ma_dir then
            v_ma_10_run := 1;
            v_ma_10_sum := v_ma_10_diff;
        else
            v_ma_10_run := last_ma_10.last_ma_run + 1;
            v_ma_10_sum := last_ma_10.last_ma_sum + v_ma_10_diff;
        end if;
        v_ma_10_MAPR := v_ma_10_sum / v_ma_10_run;
    else
        v_ma_10_dir := 0;
        v_ma_10_run := 0;
        v_ma_10_sum := 0;
        v_ma_10_MAPR := 0;
    end if;
    if v_ma_20_diff <> 0 then
        v_ma_20_dir := sign(v_ma_20_diff);
        if v_ma_20_dir <> last_ma_20.last_ma_dir then
            v_ma_20_run := 1;
            v_ma_20_sum := v_ma_20_diff;
        else
            v_ma_20_run := last_ma_20.last_ma_run + 1;
            v_ma_20_sum := last_ma_20.last_ma_sum + v_ma_20_diff;
        end if;
        v_ma_20_MAPR := v_ma_20_sum / v_ma_20_run;
    else
        v_ma_20_dir := 0;
        v_ma_20_run := 0;
        v_ma_20_sum := 0;
        v_ma_20_MAPR := 0;
    end if;
    if v_ma_30_diff <> 0 then
        v_ma_30_dir := sign(v_ma_30_diff);
        if v_ma_30_dir <> last_ma_30.last_ma_dir then
            v_ma_30_run := 1;
            v_ma_30_sum := v_ma_30_diff;
        else
            v_ma_30_run := last_ma_30.last_ma_run + 1;
            v_ma_30_sum := last_ma_30.last_ma_sum + v_ma_30_diff;
        end if;
        v_ma_30_MAPR := v_ma_30_sum / v_ma_30_run;
    else
        v_ma_30_dir := 0;
        v_ma_30_run := 0;
        v_ma_30_sum := 0;
        v_ma_30_MAPR := 0;
    end if;
    if v_ma_50_diff <> 0 then
        v_ma_50_dir := sign(v_ma_50_diff);
        if v_ma_50_dir <> last_ma_50.last_ma_dir then
            v_ma_50_run := 1;
            v_ma_50_sum := v_ma_50_diff;
        else
            v_ma_50_run := last_ma_50.last_ma_run + 1;
            v_ma_50_sum := last_ma_50.last_ma_sum + v_ma_50_diff;
        end if;
        v_ma_50_MAPR := v_ma_50_sum / v_ma_50_run;
    else
        v_ma_50_dir := 0;
        v_ma_50_run := 0;
        v_ma_50_sum := 0;
        v_ma_50_MAPR := 0;
    end if;
    if v_ma_100_diff <> 0 then
        v_ma_100_dir := sign(v_ma_100_diff);
        if v_ma_100_dir <> last_ma_100.last_ma_dir then
            v_ma_100_run := 1;
            v_ma_100_sum := v_ma_100_diff;
        else
            v_ma_100_run := last_ma_100.last_ma_run + 1;
            v_ma_100_sum := last_ma_100.last_ma_sum + v_ma_100_diff;
        end if;
        v_ma_100_MAPR := v_ma_100_sum / v_ma_100_run;
    else
        v_ma_100_dir := 0;
        v_ma_100_run := 0;
        v_ma_100_sum := 0;
        v_ma_100_MAPR := 0;
    end if;
    if v_ma_200_diff <> 0 then
        v_ma_200_dir := sign(v_ma_200_diff);
        if v_ma_200_dir <> last_ma_200.last_ma_dir then
            v_ma_200_run := 1;
            v_ma_200_sum := v_ma_200_diff;
        else
            v_ma_200_run := last_ma_200.last_ma_run + 1;
            v_ma_200_sum := last_ma_200.last_ma_sum + v_ma_200_diff;
        end if;
        v_ma_200_MAPR := v_ma_200_sum / v_ma_200_run;
    else
        v_ma_200_dir := 0;
        v_ma_200_run := 0;
        v_ma_200_sum := 0;
        v_ma_200_MAPR := 0;
    end if;
    -- find the previous exponential moving averages etc.
    SELECT ema_10, ema_20, ema_30, ema_50, ema_100, ema_200, ema_12, ema_26 INTO last_ema FROM moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    if not found then
        v_ema_10 := new_close;
        v_ema_20 := new_close;
        v_ema_30 := new_close;
        v_ema_50 := new_close;
        v_ema_100 := new_close;
        v_ema_200 := new_close;
        v_ema_12 := new_close;
        v_ema_26 := new_close;
    else
        v_ema_10 := ema(new_close, last_ema.ema_10, 10);
        v_ema_20 := ema(new_close, last_ema.ema_20, 20);
        v_ema_30 := ema(new_close, last_ema.ema_30, 30);
        v_ema_50 := ema(new_close, last_ema.ema_50, 50);
        v_ema_100 := ema(new_close, last_ema.ema_100, 100);
        v_ema_200 := ema(new_close, last_ema.ema_200, 200);
        v_ema_12 := ema(new_close, last_ema.ema_12, 12);
        v_ema_26 := ema(new_close, last_ema.ema_26, 26);
    end if;
    v_mcad := v_ema_12 - v_ema_26;
    SELECT mcad INTO last_mcad FROM indicators WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    if not found then
        v_mcad_signal := v_mcad;
    else
        v_mcad_signal := ema(v_mcad, last_mcad.mcad, 9);
    end if;
    v_mcad_histogram := v_mcad - v_mcad_signal;
    -- insert the results into moving_averages
    BEGIN
        INSERT INTO moving_averages
            (
                date, symb, exch,
                close_ma_10, close_ma_20,
                close_ma_30, close_ma_50,
                close_ma_100, close_ma_200,
                volume_ma_10, volume_ma_20,
                volume_ma_30, volume_ma_50,
                volume_ma_100, volume_ma_200,
                ma_10_dir, ma_20_dir,
                ma_30_dir, ma_50_dir,
                ma_100_dir, ma_200_dir,
                ma_10_run, ma_20_run,
                ma_30_run, ma_50_run,
                ma_100_run, ma_200_run,
                ma_10_sum, ma_20_sum,
                ma_30_sum, ma_50_sum,
                ma_100_sum, ma_200_sum,
                ma_10_diff, ma_20_diff,
                ma_30_diff, ma_50_diff,
                ma_100_diff, ma_200_diff,
                ema_12, ema_26,
                ema_10, ema_20,
                ema_30, ema_50,
                ema_100, ema_200
            )
            VALUES
            (
                new_date, new_symb, new_exch,
                avg10.avg_close, avg20.avg_close,
                avg30.avg_close, avg50.avg_close,
                avg100.avg_close, avg200.avg_close,
                avg10.avg_volume, avg20.avg_volume,
                avg30.avg_volume, avg50.avg_volume,
                avg100.avg_volume, avg200.avg_volume,
                v_ma_10_dir, v_ma_20_dir,
                v_ma_30_dir, v_ma_50_dir,
                v_ma_100_dir, v_ma_200_dir,
                v_ma_10_run, v_ma_20_run,
                v_ma_30_run, v_ma_50_run,
                v_ma_100_run, v_ma_200_run,
                v_ma_10_sum, v_ma_20_sum,
                v_ma_30_sum, v_ma_50_sum,
                v_ma_100_sum, v_ma_200_sum,
                v_ma_10_diff, v_ma_20_diff,
                v_ma_30_diff, v_ma_50_diff,
                v_ma_100_diff, v_ma_200_diff,
                v_ema_12, v_ema_26,
                v_ema_10, v_ema_20,
                v_ema_30, v_ema_50,
                v_ema_100, v_ema_200
            );
    EXCEPTION when unique_violation THEN
        update moving_averages set
            close_ma_10 = avg10.avg_close,
            close_ma_20 = avg20.avg_close,
            close_ma_30 = avg30.avg_close,
            close_ma_50 = avg50.avg_close,
            close_ma_100 = avg100.avg_close,
            close_ma_200 = avg200.avg_close,
            volume_ma_10 = avg10.avg_volume,
            volume_ma_20 = avg20.avg_volume,
            volume_ma_30 = avg30.avg_volume,
            volume_ma_50 = avg50.avg_volume,
            volume_ma_100 = avg100.avg_volume,
            volume_ma_200 = avg200.avg_volume,
            ma_10_dir = v_ma_10_dir,
            ma_20_dir = v_ma_20_dir,
            ma_30_dir = v_ma_30_dir,
            ma_50_dir = v_ma_50_dir,
            ma_100_dir = v_ma_100_dir,
            ma_200_dir = v_ma_200_dir,
            ma_10_run = v_ma_10_run,
            ma_20_run = v_ma_20_run,
            ma_30_run = v_ma_30_run,
            ma_50_run = v_ma_50_run,
            ma_100_run = v_ma_100_run,
            ma_200_run = v_ma_200_run,
            ma_10_sum = v_ma_10_sum,
            ma_20_sum = v_ma_20_sum,
            ma_30_sum = v_ma_30_sum,
            ma_50_sum = v_ma_50_sum,
            ma_100_sum = v_ma_100_sum,
            ma_200_sum = v_ma_200_sum,
            ma_10_diff = v_ma_10_diff,
            ma_20_diff = v_ma_20_diff,
            ma_30_diff = v_ma_30_diff,
            ma_50_diff = v_ma_50_diff,
            ma_100_diff = v_ma_100_diff,
            ma_200_diff = v_ma_200_diff,
            ema_12 = v_ema_12,
            ema_26 = v_ema_26,
            ema_10 = v_ema_10,
            ema_20 = v_ema_20,
            ema_30 = v_ema_30,
            ema_50 = v_ema_50,
            ema_100 = v_ema_100,
            ema_200 = v_ema_200
            where date = new_date and symb = new_symb and exch = new_exch;
    END;
    -- update the indicators table with the MAPR info
    update indicators set mapr_10 = v_ma_10_MAPR, mapr_20 = v_ma_20_MAPR, mapr_30 = v_ma_30_MAPR, mapr_50 = v_ma_50_MAPR, mapr_100 = v_ma_100_MAPR, mapr_200 = v_ma_200_MAPR, mcad = v_mcad, mcad_signal = v_mcad_signal, mcad_histogram = v_mcad_histogram where date = new_date and symb = new_symb and exch = new_exch;
    if not found then
        insert into indicators 
            ( date, symb, exch, mapr_10, mapr_20, mapr_30, mapr_50, mapr_100, mapr_200, mcad, mcad_signal, mcad_histogram )
        values
            ( new_date, new_symb, new_exch, v_ma_10_MAPR, v_ma_20_MAPR, v_ma_30_MAPR, v_ma_50_MAPR, v_ma_100_MAPR, v_ma_200_MAPR, v_mcad, v_mcad_signal, v_mcad_histogram );
    end if;
    -- Work out how many standard deviations from the mean each of these is
    BEGIN
        INSERT INTO standard_deviations_from_mean (
            DATE, symb, exch, close_sd_10, close_sd_20, close_sd_30, close_sd_50, close_sd_100, close_sd_200, volume_sd_10, volume_sd_20, volume_sd_30, volume_sd_50, volume_sd_100, volume_sd_200 )
            VALUES (
                new_date, new_symb, new_exch,
                deviation(new_close, avg10.avg_close, avg10.stddev_close), deviation(new_close, avg20.avg_close, avg20.stddev_close),
                deviation(new_close, avg30.avg_close, avg30.stddev_close), deviation(new_close, avg50.avg_close, avg50.stddev_close),
                deviation(new_close, avg100.avg_close, avg100.stddev_close), deviation(new_close, avg200.avg_close, avg200.stddev_close),
                deviation(new_volume, avg10.avg_volume, avg10.stddev_volume), deviation(new_volume, avg20.avg_volume, avg20.stddev_volume),
                deviation(new_volume, avg30.avg_volume, avg30.stddev_volume), deviation(new_volume, avg50.avg_volume, avg50.stddev_volume),
                deviation(new_volume, avg100.avg_volume, avg100.stddev_volume), deviation(new_volume, avg200.avg_volume, avg200.stddev_volume)
            );
    EXCEPTION when unique_violation THEN
        update standard_deviations_from_mean set
            close_sd_10 = deviation(new_close, avg10.avg_close, avg10.stddev_close), 
            close_sd_20 = deviation(new_close, avg20.avg_close, avg20.stddev_close), 
            close_sd_30 = deviation(new_close, avg30.avg_close, avg30.stddev_close), 
            close_sd_50 = deviation(new_close, avg50.avg_close, avg50.stddev_close), 
            close_sd_100 = deviation(new_close, avg100.avg_close, avg100.stddev_close), 
            close_sd_200 = deviation(new_close, avg200.avg_close, avg200.stddev_close), 
            volume_sd_10 = deviation(new_volume, avg10.avg_volume, avg10.stddev_volume),
            volume_sd_20 = deviation(new_volume, avg20.avg_volume, avg20.stddev_volume),
            volume_sd_30 = deviation(new_volume, avg30.avg_volume, avg30.stddev_volume),
            volume_sd_50 = deviation(new_volume, avg50.avg_volume, avg50.stddev_volume),
            volume_sd_100 = deviation(new_volume, avg100.avg_volume, avg100.stddev_volume),
            volume_sd_200 = deviation(new_volume, avg200.avg_volume, avg200.stddev_volume)
            where date = new_date and symb = new_symb and exch = new_exch;
    END;
END
$$;
ALTER FUNCTION public.update_moving_averages(new_date date, new_symb character varying, new_exch character varying, new_close numeric, new_volume numeric) OWNER TO postgres;
