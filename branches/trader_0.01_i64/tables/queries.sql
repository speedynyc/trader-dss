--
-- Name: queries; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
--
-- Name: queries; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE queries
(
 qid serial NOT NULL,
 uid integer NOT NULL,
 "name" character varying(100) NOT NULL,
 sql_select text,
 sql_from text,
 sql_where text,
 sql_order text,
 sql_order_dir character(4),
 sql_limit integer,
 chart_period integer,
 active boolean,
 CONSTRAINT queries_pkey PRIMARY KEY (qid),
 CONSTRAINT queries_uid FOREIGN KEY (uid)
 REFERENCES users (uid) MATCH FULL
 ON UPDATE NO ACTION ON DELETE NO ACTION
 );
ALTER TABLE public.queries OWNER TO postgres;
