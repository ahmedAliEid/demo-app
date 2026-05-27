CREATE TABLE addresses (
    id             BIGSERIAL    PRIMARY KEY,
    contact_id     BIGINT       NOT NULL UNIQUE
                                REFERENCES contacts(id) ON DELETE CASCADE,
    street_line1   VARCHAR(255) NOT NULL,
    street_line2   VARCHAR(255),
    city           VARCHAR(100) NOT NULL,
    state_province VARCHAR(100),
    postal_code    VARCHAR(20),
    country        VARCHAR(100) NOT NULL,
    phone          VARCHAR(50)
);

CREATE INDEX idx_addresses_city    ON addresses(city);
CREATE INDEX idx_addresses_country ON addresses(country);
