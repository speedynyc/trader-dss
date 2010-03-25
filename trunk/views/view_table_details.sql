CREATE OR REPLACE VIEW view_table_details AS
SELECT pc.oid AS tbl_oid, pn.nspname AS schemaname, pc.relname::character varying AS table_name, 
    pa.attname::character varying AS column_name, pt.typname AS data_type,
    CASE
        WHEN substr(pt.typname::text, 1, 3)::name = 'int'::name THEN 'integer'::name
            WHEN pt.typname = 'bool'::name THEN 'boolean'::name
    ELSE pt.typname
    END AS udt_name, pa.attnum AS ordinal_position, 254 AS str_length,
    CASE
            WHEN pa.attnotnull THEN false
    ELSE true
    END AS nulls_allowed,
    CASE
            WHEN substr(pa.attname::text, 1, 3) = 'lu_'::text THEN true
    ELSE false
    END AS lookup,
    CASE
            WHEN pd.description::character varying IS NOT NULL THEN pd.description::character varying
    ELSE NULL::character varying
    END AS comment
    FROM ONLY pg_class pc
    JOIN ONLY pg_attribute pa ON pc.oid = pa.attrelid AND pc.relnamespace = 2200::oid AND pc.reltype > 0::oid AND (pc.relkind = 'r'::"char" OR pc.relkind = 'v'::"char")
    JOIN ONLY pg_type pt ON pa.atttypid = pt.oid
    LEFT JOIN ONLY pg_description pd ON pc.oid = pd.objoid AND pa.attnum = pd.objsubid
    LEFT JOIN pg_namespace pn ON pn.oid = pc.relnamespace
    WHERE pa.attnum > 0;
