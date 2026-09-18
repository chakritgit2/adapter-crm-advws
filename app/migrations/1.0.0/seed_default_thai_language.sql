-- Seed default Thai language for every existing company.
--
-- Layer B (entity-content translation) defaults to Thai: every company that
-- does not yet have a 'th' row in tenant_languages gets one. New companies
-- created after this migration should also auto-install 'th' in the
-- company-creation service/controller.
--
-- Safe to re-run: the WHERE NOT EXISTS guard prevents duplicate inserts.

INSERT INTO tenant_languages (company_id, language_code, language_name, is_active)
SELECT c.id, 'th', 'ภาษาไทย', 1
FROM companies c
WHERE NOT EXISTS (
    SELECT 1
    FROM tenant_languages tl
    WHERE tl.company_id = c.id
      AND tl.language_code = 'th'
);
