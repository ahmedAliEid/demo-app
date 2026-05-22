package com.demo.app.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public record ContactRequest(
        @NotBlank @Size(max = 255) String fullName,
        @NotBlank @Size(max = 255) String streetLine1,
        @Size(max = 255) String streetLine2,
        @NotBlank @Size(max = 100) String city,
        @Size(max = 100) String stateProvince,
        @Size(max = 20) String postalCode,
        @NotBlank @Size(max = 100) String country,
        @Size(max = 50) String phone
) {}
