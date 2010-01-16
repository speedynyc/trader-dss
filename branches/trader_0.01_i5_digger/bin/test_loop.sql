create or replace function test_loop_1() returns integer
    as $$
    declare
        smb   record;
        cnt   integer;
    begin
        cnt := 0;
        for smb in select symb, exch from stocks where symb similar to '(A|C|D|E|F|G|H|I|J|K)%' order by symb loop
            cnt := cnt + 1;
            raise notice 'update_moving_averages % % %' , smb.symb, smb.exch, cnt;
            perform update_moving_averages(date, symb, exch, close, volume) from quotes where symb = smb.symb order by date asc;
        end loop;
        raise notice 'total %' , cnt;
    return 1;
    end;
$$ LANGUAGE plpgsql;

create or replace function test_loop_2() returns integer
    as $$
    declare
        smb   record;
        cnt   integer;
    begin
        cnt := 0;
        for smb in select symb, exch from stocks where symb similar to '(L|M|N|O|P|Q|R|S|T|U|V|W|X|Y|Z)%' order by symb loop
            cnt := cnt + 1;
            raise notice 'update_moving_averages % % %' , smb.symb, smb.exch, cnt;
            perform update_moving_averages(date, symb, exch, close, volume) from quotes where symb = smb.symb order by date asc;
        end loop;
        raise notice 'total %' , cnt;
    return 1;
    end;
$$ LANGUAGE plpgsql;
