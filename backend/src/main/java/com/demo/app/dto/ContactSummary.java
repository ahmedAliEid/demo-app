package com.demo.app.dto;

import com.demo.app.domain.Contact;

public record ContactSummary(
        Long id,
        String fullName,
        String city,
        String country
) {
    public static ContactSummary from(Contact c) {
        return new ContactSummary(
                c.getId(),
                c.getFullName(),
                c.getAddress() != null ? c.getAddress().getCity() : null,
                c.getAddress() != null ? c.getAddress().getCountry() : null
        );
    }
}
