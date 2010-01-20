--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE users (
    uid serial not null,
    name character varying(100) unique NOT NULL,
    passwd text
);
ALTER TABLE public.users OWNER TO postgres;
ALTER TABLE ONLY users ADD CONSTRAINT users_pkey PRIMARY KEY (uid);
