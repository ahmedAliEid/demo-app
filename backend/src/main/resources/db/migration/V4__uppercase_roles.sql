-- Align role column values with JPA EnumType.STRING (uppercase enum constants)
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;

UPDATE users SET role = UPPER(role);

ALTER TABLE users ADD CONSTRAINT users_role_check
    CHECK (role IN ('ADMIN', 'USER'));
