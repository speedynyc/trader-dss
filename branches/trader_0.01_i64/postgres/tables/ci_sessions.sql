--
-- Name: ci_sessions; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--
CREATE TABLE ci_sessions (
        session_id varchar(40) NOT NULL DEFAULT 0,
        ip_address varchar(16) NOT NULL DEFAULT 0,
        user_agent varchar(50) NOT NULL,
        last_activity int4 NOT NULL DEFAULT 0,
        user_data text
);
ALTER TABLE ci_sessions OWNER TO postgres;
ALTER TABLE ONLY ci_sessions ADD CONSTRAINT ci_sessions_pkey PRIMARY KEY (session_id);
