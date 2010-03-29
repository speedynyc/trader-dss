--
-- Name: queries; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE queries (
    qid serial not null,
    uid integer not null,
    name character varying(100) unique NOT NULL,
    sql_select text,
    sql_from text,
    sql_where text,
    sql_order text,
    sql_order_dir char(4),
    sql_limit integer,
    chart_period integer,
    active boolean,
    unique (uid, name)
);
ALTER TABLE public.queries OWNER TO postgres;
ALTER TABLE ONLY queries ADD CONSTRAINT queries_pkey PRIMARY KEY qid;
