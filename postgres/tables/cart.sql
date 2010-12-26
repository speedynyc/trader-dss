--
-- Name: cart ; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE cart
(
 pfid integer NOT NULL, -- Portfolio ID
 date date NOT NULL, -- the date the symb was added to the cart
 symb character varying(12) NOT NULL, -- The symbol name from the stocks table
 "comment" text, -- Any comment or notes on the symb traded
 volume numeric NOT NULL -- The quantity of the symbol to be traded (negative for short trading)
)
WITHOUT OIDS;
COMMENT ON COLUMN cart.pfid IS 'Portfolio ID';
COMMENT ON COLUMN cart.date IS 'the date the symb was added to the cart';
COMMENT ON COLUMN cart.symb IS 'The symbol name from the stocks table';
COMMENT ON COLUMN cart."comment" IS 'Any comment or notes on the symb traded';
COMMENT ON COLUMN cart.volume IS 'The quantity of the symbol to be traded (negative for short trading)';

ALTER TABLE public.cart OWNER TO postgres;
ALTER TABLE ONLY cart ADD CONSTRAINT cart_pkey PRIMARY KEY (pfid, symb, date);
