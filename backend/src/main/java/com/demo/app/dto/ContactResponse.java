package com.demo.app.dto;

import com.demo.app.domain.Contact;

import java.time.Instant;

public record ContactResponse(
        Long id,
        String fullName,
        String streetLine1,
        String streetLine2,
        String city,
        String stateProvince,
        String postalCode,
        String country,
        String phone,
        Instant createdAt,
        Instant updatedAt
) {
    public static ContactResponse from(Contact c) {
        var a = c.getAddress();
        return new ContactResponse(
                c.getId(),
                c.getFullName(),
                a != null ? a.getStreetLine1() : null,
                a != null ? a.getStreetLine2() : null,
                a != null ? a.getCity() : null,
                a != null ? a.getStateProvince() : null,
                a != null ? a.getPostalCode() : null,
                a != null ? a.getCountry() : null,
                a != null ? a.getPhone() : null,
                c.getCreatedAt(),
                c.getUpdatedAt()
        );
    }
}
