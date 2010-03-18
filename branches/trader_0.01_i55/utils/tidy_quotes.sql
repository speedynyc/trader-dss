CREATE OR REPLACE FUNCTION tidy_quotes(symbol character varying, exchange character varying)
  RETURNS integer AS
$BODY$
 BEGIN 
 -- delete the symbol from the derived tables
 delete from standard_deviations_from_mean where symb = symbol and exch = exchange;
 delete from quotes where symb = symbol and exch = exchange;
 delete from stock_dates where symb = symbol and exch = exchange;             
 delete from gains where symb = symbol and exch = exchange;
 delete from moving_averages where symb = symbol and exch = exchange;  
 delete from indicators where symb = symbol and exch = exchange;  
 return 0;
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION tidy_quotes(character varying, character varying) OWNER TO postgres;

