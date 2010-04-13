--
-- Name: update_derived_tables; Type: TRIGGER; Schema: public; Owner: postgres
--
CREATE TRIGGER update_derived_tables
    AFTER INSERT ON quotes
    FOR EACH ROW
    EXECUTE PROCEDURE update_derived_tables();

