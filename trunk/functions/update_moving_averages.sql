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
        v_ma_10_diff numeric(9,2);
        v_ma_20_diff numeric(9,2);
        v_ma_30_diff numeric(9,2);
        v_ma_50_diff numeric(9,2);
        v_ma_100_diff numeric(9,2);
        v_ma_200_diff numeric(9,2);
        v_ma_10_dir integer;
        v_ma_20_dir integer;
        v_ma_30_dir integer;
        v_ma_50_dir integer;
        v_ma_100_dir integer;
        v_ma_200_dir integer;
        v_ma_10_run integer;
        v_ma_20_run integer;
        v_ma_30_run integer;
        v_ma_50_run integer;
        v_ma_100_run integer;
        v_ma_200_run integer;
        v_ma_10_sum numeric(12,2);
        v_ma_20_sum numeric(12,2);
        v_ma_30_sum numeric(12,2);
        v_ma_50_sum numeric(12,2);
        v_ma_100_sum numeric(12,2);
        v_ma_200_sum numeric(12,2);
        v_ma_10_MAPR numeric(9,2);
        v_ma_20_MAPR numeric(9,2);
        v_ma_30_MAPR numeric(9,2);
        v_ma_50_MAPR numeric(9,2);
        v_ma_100_MAPR numeric(9,2);
        v_ma_200_MAPR numeric(9,2);
    BEGIN
    -- work out the moving averages for 10, 20, 30, 50, 100 and 200 previous trading days.
    -- order from lagest date range to smallest to get the disk cache on our side
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg200 FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 200) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg100 FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 100) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg50 FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 50) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg30 FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 30) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg20 FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 20) AS topN;
    SELECT AVG(topN.CLOSE) AS avg_close, STDDEV(topN.CLOSE) AS stddev_close, AVG(topN.volume) AS avg_volume, STDDEV(topN.volume) AS stddev_volume INTO avg10 FROM (SELECT CLOSE, volume FROM quotes WHERE DATE <= new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 10) AS topN;
    -- find all the previous dir (direction) and run (number of days in that direction) from the simple_moving_averages table
    -- This could be done as one query, but is easier to write this way
    -- These don't need to be calculated if the direction is 0, so an optimisation could be to move them into the logic for calculating the ma_XX_dir below
    -- I don't think there will be many diffs of 0
    SELECT ma_10_sum as last_ma_sum, ma_10_dir AS last_ma_dir, ma_10_run AS last_ma_run INTO last_ma_10 FROM simple_moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_20_sum as last_ma_sum, ma_20_dir AS last_ma_dir, ma_20_run AS last_ma_run INTO last_ma_20 FROM simple_moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_30_sum as last_ma_sum, ma_30_dir AS last_ma_dir, ma_30_run AS last_ma_run INTO last_ma_30 FROM simple_moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_50_sum as last_ma_sum, ma_50_dir AS last_ma_dir, ma_50_run AS last_ma_run INTO last_ma_50 FROM simple_moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_100_sum as last_ma_sum, ma_100_dir AS last_ma_dir, ma_100_run AS last_ma_run INTO last_ma_100 FROM simple_moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
    SELECT ma_200_sum as last_ma_sum, ma_200_dir AS last_ma_dir, ma_200_run AS last_ma_run INTO last_ma_200 FROM simple_moving_averages WHERE DATE < new_date AND symb = new_symb AND exch = new_exch ORDER BY DATE DESC limit 1;
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
    -- insert the results into simple_moving_averages
    INSERT INTO simple_moving_averages (
        DATE, symb, exch,
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
        ma_100_diff, ma_200_diff
        ) VALUES (
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
        v_ma_100_diff, v_ma_200_diff
    );
    -- update the indicators table with the MAPR info
    update indicators set mapr_10 = v_ma_10_MAPR, mapr_20 = v_ma_20_MAPR, mapr_30 = v_ma_30_MAPR, mapr_50 = v_ma_50_MAPR, mapr_100 = v_ma_100_MAPR, mapr_200 = v_ma_200_MAPR where date = new_date and symb = new_symb and exch = new_exch;
    if not found then
        insert into indicators 
        ( date, symb, exch, mapr_10, mapr_20, mapr_30, mapr_50, mapr_100, mapr_200 )
        values
        ( new_date, new_symb, new_exch, v_ma_10_MAPR, v_ma_20_MAPR, v_ma_30_MAPR, v_ma_50_MAPR, v_ma_100_MAPR, v_ma_200_MAPR );
    end if;
    -- Work out how many standard deviations from the mean each of these is
    INSERT INTO standard_deviations_from_mean (
        DATE, symb, exch,
        close_sd_10, close_sd_20, close_sd_30, close_sd_50, close_sd_100, close_sd_200, volume_sd_10, volume_sd_20, volume_sd_30, volume_sd_50, volume_sd_100, volume_sd_200 )
        VALUES (
        new_date, new_symb, new_exch,
        deviation(new_close, avg10.avg_close, avg10.stddev_close), deviation(new_close, avg20.avg_close, avg20.stddev_close),
        deviation(new_close, avg30.avg_close, avg30.stddev_close), deviation(new_close, avg50.avg_close, avg50.stddev_close),
        deviation(new_close, avg100.avg_close, avg100.stddev_close), deviation(new_close, avg200.avg_close, avg200.stddev_close),
        deviation(new_volume, avg10.avg_volume, avg10.stddev_volume), deviation(new_volume, avg20.avg_volume, avg20.stddev_volume),
        deviation(new_volume, avg30.avg_volume, avg30.stddev_volume), deviation(new_volume, avg50.avg_volume, avg50.stddev_volume),
        deviation(new_volume, avg100.avg_volume, avg100.stddev_volume), deviation(new_volume, avg200.avg_volume, avg200.stddev_volume)
    );
END
$$;
ALTER FUNCTION public.update_moving_averages(new_date date, new_symb character varying, new_exch character varying, new_close numeric, new_volume numeric) OWNER TO postgres;
