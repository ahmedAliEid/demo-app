CREATE TABLE contacts (
    id         BIGSERIAL    PRIMARY KEY,
    full_name  VARCHAR(255) NOT NULL,
    owner_id   BIGINT       NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_contact_name_per_owner UNIQUE (owner_id, full_name)
);

CREATE INDEX idx_contacts_owner     ON contacts(owner_id);
CREATE INDEX idx_contacts_full_name ON contacts(full_name);
